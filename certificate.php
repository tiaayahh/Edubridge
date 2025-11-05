<?php
include 'includes/header.php';
include 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Generate certificate (admin only)
if ($userRole === 'admin' && isset($_POST['generate_certificate'])) {
    $userId = $_POST['user_id'];
    $opportunityId = $_POST['opportunity_id'];
    $completionDate = $_POST['completion_date'];
    
    $certificateId = generateCertificate($userId, $opportunityId, $completionDate);
    $_SESSION['success'] = "Certificate generated successfully! ID: $certificateId";
}

// Get user certificates
if ($userRole === 'youth') {
    $stmt = $pdo->prepare("SELECT c.*, o.title as opportunity_title, o.type, u.username as organization_name 
                          FROM certificates c 
                          JOIN opportunities o ON c.opportunity_id = o.id 
                          JOIN users u ON o.organization_id = u.id 
                          WHERE c.user_id = ? 
                          ORDER BY c.issue_date DESC");
    $stmt->execute([$userId]);
    $certificates = $stmt->fetchAll();
} elseif ($userRole === 'admin') {
    $stmt = $pdo->query("SELECT c.*, o.title as opportunity_title, u.username as youth_name, org.username as organization_name 
                        FROM certificates c 
                        JOIN opportunities o ON c.opportunity_id = o.id 
                        JOIN users u ON c.user_id = u.id 
                        JOIN users org ON o.organization_id = org.id 
                        ORDER BY c.issue_date DESC");
    $certificates = $stmt->fetchAll();
}
?>

<div class="card">
    <h1>Certificates</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($userRole === 'youth'): ?>
        <?php if (empty($certificates)): ?>
            <div class="alert alert-info">
                You don't have any certificates yet. Complete opportunities to earn certificates!
            </div>
        <?php else: ?>
            <div class="certificates-grid">
                <?php foreach ($certificates as $cert): ?>
                    <div class="certificate-card">
                        <div class="certificate-header">
                            <h3>Certificate of Completion</h3>
                            <div class="certificate-id">ID: <?php echo $cert['certificate_id']; ?></div>
                        </div>
                        
                        <div class="certificate-body">
                            <p>This certifies that</p>
                            <h4><?php echo $_SESSION['username']; ?></h4>
                            <p>has successfully completed the</p>
                            <h5><?php echo htmlspecialchars($cert['opportunity_title']); ?></h5>
                            <p>offered by <?php echo htmlspecialchars($cert['organization_name']); ?></p>
                        </div>
                        
                        <div class="certificate-footer">
                            <div class="completion-date">
                                Completed on: <?php echo date('F j, Y', strtotime($cert['completion_date'])); ?>
                            </div>
                            <div class="certificate-actions">
                                <button onclick="previewCertificate('<?php echo $cert['certificate_id']; ?>')" 
                                        class="btn btn-primary btn-sm">Preview</button>
                                <button onclick="downloadCertificate('<?php echo $cert['certificate_id']; ?>')" 
                                        class="btn btn-success btn-sm">Download</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($userRole === 'admin'): ?>
        <div class="admin-certificates">
            <div class="card">
                <h2>Generate New Certificate</h2>
                <form method="POST" class="certificate-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="user_id">Youth User</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                <option value="">Select Youth User</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'youth'");
                                $youthUsers = $stmt->fetchAll();
                                foreach ($youthUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="opportunity_id">Opportunity</label>
                            <select id="opportunity_id" name="opportunity_id" class="form-control" required>
                                <option value="">Select Opportunity</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, title FROM opportunities WHERE is_approved = 1");
                                $opportunities = $stmt->fetchAll();
                                foreach ($opportunities as $opp): ?>
                                    <option value="<?php echo $opp['id']; ?>"><?php echo htmlspecialchars($opp['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="completion_date">Completion Date</label>
                        <input type="date" id="completion_date" name="completion_date" class="form-control" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <button type="submit" name="generate_certificate" class="btn btn-primary">Generate Certificate</button>
                </form>
            </div>
            
            <div class="card">
                <h2>All Certificates</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Certificate ID</th>
                                <th>Youth</th>
                                <th>Opportunity</th>
                                <th>Organization</th>
                                <th>Completion Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td><?php echo $cert['certificate_id']; ?></td>
                                    <td><?php echo htmlspecialchars($cert['youth_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['opportunity_title']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['organization_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($cert['completion_date'])); ?></td>
                                    <td>
                                        <button onclick="previewCertificate('<?php echo $cert['certificate_id']; ?>')" 
                                                class="btn btn-primary btn-sm">View</button>
                                        <button onclick="downloadCertificate('<?php echo $cert['certificate_id']; ?>')" 
                                                class="btn btn-success btn-sm">Download</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Certificates are available for youth who complete opportunities.
        </div>
    <?php endif; ?>
</div>

<style>
.certificates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.certificate-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border: 2px solid #667eea;
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.certificate-header {
    border-bottom: 2px solid #667eea;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.certificate-header h3 {
    color: #667eea;
    margin: 0;
}

.certificate-id {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.5rem;
}

.certificate-body h4 {
    color: #333;
    margin: 1rem 0;
    font-size: 1.5rem;
}

.certificate-body h5 {
    color: #667eea;
    margin: 1rem 0;
    font-size: 1.25rem;
}

.certificate-footer {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.completion-date {
    color: #666;
    font-size: 0.875rem;
}

.certificate-actions {
    display: flex;
    gap: 0.5rem;
}

.admin-certificates .card {
    margin-bottom: 2rem;
}

.certificate-form {
    max-width: 600px;
}
</style>

<script>
function previewCertificate(certificateId) {
    // Create a modal to preview certificate
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '1000';
    
    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%;">
            <h2 style="color: #667eea; text-align: center; margin-bottom: 2rem;">Certificate Preview</h2>
            <div style="text-align: center; padding: 2rem; border: 2px solid #667eea; border-radius: 10px;">
                <h3 style="color: #333;">Certificate of Completion</h3>
                <p>Certificate ID: ${certificateId}</p>
                <p>This is a preview of the certificate.</p>
                <p style="margin-top: 2rem; color: #666;">Full certificate would include user details, opportunity details, and official signatures.</p>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <button onclick="this.closest('.certificate-modal').remove()" class="btn btn-primary">Close</button>
                <button onclick="downloadCertificate('${certificateId}')" class="btn btn-success">Download PDF</button>
            </div>
        </div>
    `;
    
    modal.className = 'certificate-modal';
    document.body.appendChild(modal);
}

function downloadCertificate(certificateId) {
    // Simulate certificate download
    showNotification('Preparing certificate download...', 'success');
    
    // In a real application, this would generate and download a PDF
    setTimeout(() => {
        showNotification('Certificate download started!', 'success');
        
        // Create a temporary link to simulate download
        const link = document.createElement('a');
        link.href = '#'; // In real app, this would be the PDF URL
        link.download = `certificate_${certificateId}.pdf`;
        link.click();
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>