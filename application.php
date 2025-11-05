<?php
include 'includes/header.php';
include 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();
$opportunityId = $_GET['opportunity'] ?? '';
$action = $_GET['action'] ?? '';

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply'])) {
        $opportunityId = $_POST['opportunity_id'];
        $coverLetter = sanitizeInput($_POST['cover_letter']);
        
        // Check if already applied
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND opportunity_id = ?");
        $stmt->execute([$userId, $opportunityId]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "You have already applied for this opportunity.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, opportunity_id, cover_letter) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $opportunityId, $coverLetter]);
            $_SESSION['success'] = "Application submitted successfully!";
        }
        
    } elseif (isset($_POST['update_status'])) {
        $applicationId = $_POST['application_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $applicationId]);
        $_SESSION['success'] = "Application status updated!";
        
        // Send notification to youth
        $stmt = $pdo->prepare("SELECT user_id FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();
        
        if ($application) {
            sendNotification($application['user_id'], "Your application status has been updated to: $status", 'email');
        }
    }
}

// Get applications based on user role
if ($userRole === 'youth') {
    $stmt = $pdo->prepare("SELECT a.*, o.title, o.type, o.deadline, u.username as organization_name 
                          FROM applications a 
                          JOIN opportunities o ON a.opportunity_id = o.id 
                          JOIN users u ON o.organization_id = u.id 
                          WHERE a.user_id = ? 
                          ORDER BY a.applied_at DESC");
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll();
    
} elseif ($userRole === 'organization') {
    if ($opportunityId) {
        $stmt = $pdo->prepare("SELECT a.*, u.username as applicant_name, u.email, yp.full_name, yp.skills 
                              FROM applications a 
                              JOIN users u ON a.user_id = u.id 
                              LEFT JOIN youth_profiles yp ON u.id = yp.user_id 
                              WHERE a.opportunity_id = ? 
                              ORDER BY a.applied_at DESC");
        $stmt->execute([$opportunityId]);
        $applications = $stmt->fetchAll();
        
        // Get opportunity details
        $stmt = $pdo->prepare("SELECT title FROM opportunities WHERE id = ?");
        $stmt->execute([$opportunityId]);
        $opportunity = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT a.*, o.title as opportunity_title, u.username as applicant_name 
                              FROM applications a 
                              JOIN opportunities o ON a.opportunity_id = o.id 
                              JOIN users u ON a.user_id = u.id 
                              WHERE o.organization_id = ? 
                              ORDER BY a.applied_at DESC");
        $stmt->execute([$userId]);
        $applications = $stmt->fetchAll();
    }
}
?>

<div class="card">
    <h1>
        <?php 
        if ($userRole === 'youth') {
            echo 'My Applications';
        } elseif ($userRole === 'organization') {
            if ($opportunityId) {
                echo "Applications for: " . htmlspecialchars($opportunity['title'] ?? '');
            } else {
                echo 'All Applications';
            }
        }
        ?>
    </h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($userRole === 'youth'): ?>
        <?php if (empty($applications)): ?>
            <div class="alert alert-info">
                You haven't applied for any opportunities yet. <a href="opportunities.php">Browse opportunities</a> to get started!
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <h3><?php echo htmlspecialchars($app['title']); ?></h3>
                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>
                        
                        <div class="application-details">
                            <p><strong>Organization:</strong> <?php echo htmlspecialchars($app['organization_name']); ?></p>
                            <p><strong>Type:</strong> <?php echo ucfirst($app['type']); ?></p>
                            <p><strong>Applied:</strong> <?php echo date('M j, Y', strtotime($app['applied_at'])); ?></p>
                            
                            <?php if ($app['cover_letter']): ?>
                                <div class="cover-letter">
                                    <strong>Cover Letter:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($userRole === 'organization'): ?>
        <?php if (empty($applications)): ?>
            <div class="alert alert-info">
                No applications received yet.
            </div>
        <?php else: ?>
            <div class="applications-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Opportunity</th>
                            <th>Applied Date</th>
                            <th>Skills</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['full_name'] ?? $app['applicant_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($app['email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php if (isset($app['opportunity_title'])): ?>
                                        <?php echo htmlspecialchars($app['opportunity_title']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($opportunity['title'] ?? ''); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <?php if ($app['skills']): ?>
                                        <div class="skills-tags">
                                            <?php 
                                            $skills = explode(',', $app['skills']);
                                            foreach (array_slice($skills, 0, 3) as $skill): ?>
                                                <span class="skill-tag"><?php echo trim($skill); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($skills) > 3): ?>
                                                <span class="skill-tag">+<?php echo count($skills) - 3; ?> more</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="application-actions">
                                        <button onclick="viewApplication(<?php echo $app['id']; ?>)" 
                                                class="btn btn-primary btn-sm">View</button>
                                        
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="status" value="shortlisted">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">Shortlist</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" name="update_status" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php elseif ($app['status'] === 'shortlisted'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">Accept</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.applications-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.application-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.application-header h3 {
    margin: 0;
    color: #333;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-pending { background-color: #fff3cd; color: #856404; }
.status-shortlisted { background-color: #d1ecf1; color: #0c5460; }
.status-accepted { background-color: #d4edda; color: #155724; }
.status-rejected { background-color: #f8d7da; color: #721c24; }

.cover-letter {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 4px solid #667eea;
}

.skills-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.skill-tag {
    background: #e9ecef;
    color: #495057;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.application-actions {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}
</style>

<script>
function viewApplication(applicationId) {
    // In a real application, this would fetch and display full application details
    showNotification('Loading application details...', 'info');
    
    // Simulate loading application details
    setTimeout(() => {
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
            <div style="background: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <h2 style="color: #667eea; margin-bottom: 1rem;">Application Details</h2>
                <p>Full application details for ID: ${applicationId}</p>
                <div style="margin-top: 2rem; text-align: center;">
                    <button onclick="this.closest('.application-modal').remove()" class="btn btn-primary">Close</button>
                </div>
            </div>
        `;
        
        modal.className = 'application-modal';
        document.body.appendChild(modal);
    }, 500);
}
</script>

<?php include 'includes/footer.php'; ?>