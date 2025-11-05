<?php
include 'includes/header.php';
include 'includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    redirect('login.php');
}

$tab = $_GET['tab'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_opportunity'])) {
        $opportunityId = $_POST['opportunity_id'];
        $stmt = $pdo->prepare("UPDATE opportunities SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$opportunityId]);
        $_SESSION['success'] = "Opportunity approved successfully!";
    } elseif (isset($_POST['reject_opportunity'])) {
        $opportunityId = $_POST['opportunity_id'];
        $stmt = $pdo->prepare("DELETE FROM opportunities WHERE id = ?");
        $stmt->execute([$opportunityId]);
        $_SESSION['success'] = "Opportunity rejected and deleted!";
    } elseif (isset($_POST['verify_user'])) {
        $userId = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "User verified successfully!";
    }
}
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <ul class="admin-menu">
            <li><a href="?tab=dashboard" class="<?php echo $tab === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="?tab=users" class="<?php echo $tab === 'users' ? 'active' : ''; ?>">User Management</a></li>
            <li><a href="?tab=opportunities" class="<?php echo $tab === 'opportunities' ? 'active' : ''; ?>">Opportunity Approval</a></li>
            <li><a href="?tab=certificates" class="<?php echo $tab === 'certificates' ? 'active' : ''; ?>">Certificates</a></li>
            <li><a href="?tab=reports" class="<?php echo $tab === 'reports' ? 'active' : ''; ?>">Reports & Analytics</a></li>
        </ul>
    </div>

    <div class="admin-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
            <div class="card">
                <h2>Admin Dashboard</h2>
                <div class="stats-grid">
                    <?php
                    // Get statistics
                    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
                    $totalUsers = $stmt->fetch()['total_users'];
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as total_opportunities FROM opportunities");
                    $totalOpportunities = $stmt->fetch()['total_opportunities'];
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as pending_approval FROM opportunities WHERE is_approved = 0");
                    $pendingApproval = $stmt->fetch()['pending_approval'];
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as total_applications FROM applications");
                    $totalApplications = $stmt->fetch()['total_applications'];
                    ?>
                    
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Opportunities</h3>
                        <p class="stat-number"><?php echo $totalOpportunities; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Pending Approval</h3>
                        <p class="stat-number"><?php echo $pendingApproval; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Applications</h3>
                        <p class="stat-number"><?php echo $totalApplications; ?></p>
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'users'): ?>
            <div class="card">
                <h2>User Management</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Verified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                            $users = $stmt->fetchAll();
                            
                            foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td>
                                        <?php if ($user['is_verified']): ?>
                                            <span class="badge badge-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$user['is_verified']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="verify_user" class="btn btn-success btn-sm">Verify</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="?tab=users&action=view&id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($tab === 'opportunities'): ?>
            <div class="card">
                <h2>Opportunity Approval</h2>
                <div class="opportunity-grid">
                    <?php
                    $stmt = $pdo->query("SELECT o.*, u.username as organization_name 
                                        FROM opportunities o 
                                        JOIN users u ON o.organization_id = u.id 
                                        WHERE o.is_approved = 0 
                                        ORDER BY o.created_at DESC");
                    $opportunities = $stmt->fetchAll();
                    
                    if (empty($opportunities)): ?>
                        <div class="alert alert-info">
                            No opportunities pending approval.
                        </div>
                    <?php else: 
                        foreach ($opportunities as $opportunity): ?>
                            <div class="opportunity-card">
                                <h3><?php echo htmlspecialchars($opportunity['title']); ?></h3>
                                <p><strong>Organization:</strong> <?php echo htmlspecialchars($opportunity['organization_name']); ?></p>
                                <p><strong>Type:</strong> <?php echo ucfirst($opportunity['type']); ?></p>
                                <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($opportunity['deadline'])); ?></p>
                                <p class="opportunity-description"><?php echo htmlspecialchars(substr($opportunity['description'], 0, 150)); ?>...</p>
                                
                                <div class="opportunity-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="opportunity_id" value="<?php echo $opportunity['id']; ?>">
                                        <button type="submit" name="approve_opportunity" class="btn btn-success">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="opportunity_id" value="<?php echo $opportunity['id']; ?>">
                                        <button type="submit" name="reject_opportunity" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this opportunity?')">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>

        <?php elseif ($tab === 'reports'): ?>
            <div class="card">
                <h2>Reports & Analytics</h2>
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>User Registration Trend</h3>
                        <div class="chart-placeholder">
                            <p>Chart showing user registration over time</p>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Opportunity Types</h3>
                        <div class="chart-placeholder">
                            <p>Pie chart of opportunity types</p>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Application Status</h3>
                        <div class="chart-placeholder">
                            <p>Bar chart of application statuses</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-container {
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
}

.admin-sidebar {
    flex: 0 0 250px;
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.admin-content {
    flex: 1;
}

.admin-menu {
    list-style: none;
}

.admin-menu li {
    margin-bottom: 0.5rem;
}

.admin-menu a {
    display: block;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #333;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.admin-menu a:hover,
.admin-menu a.active {
    background-color: #667eea;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin: 0.5rem 0 0 0;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
}

.role-youth { background-color: #e3f2fd; color: #1976d2; }
.role-organization { background-color: #f3e5f5; color: #7b1fa2; }
.role-admin { background-color: #e8f5e8; color: #388e3c; }

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success { background-color: #d4edda; color: #155724; }
.badge-warning { background-color: #fff3cd; color: #856404; }

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.report-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-placeholder {
    height: 200px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    margin-top: 1rem;
}
</style>

<?php include 'includes/footer.php'; ?>