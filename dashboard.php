<?php
include 'includes/header.php';
include 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();

// Get user-specific data
if ($userRole === 'youth') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as applications FROM applications WHERE user_id = ?");
    $stmt->execute([$userId]);
    $applicationCount = $stmt->fetch()['applications'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as certificates FROM certificates WHERE user_id = ?");
    $stmt->execute([$userId]);
    $certificateCount = $stmt->fetch()['certificates'];
} elseif ($userRole === 'organization') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as opportunities FROM opportunities WHERE organization_id = ?");
    $stmt->execute([$userId]);
    $opportunityCount = $stmt->fetch()['opportunities'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as applications FROM applications a 
                          JOIN opportunities o ON a.opportunity_id = o.id 
                          WHERE o.organization_id = ?");
    $stmt->execute([$userId]);
    $applicationCount = $stmt->fetch()['applications'];
}
?>

<div class="card">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo $_SESSION['username']; ?>!</p>
</div>

<div class="dashboard-grid">
    <?php if ($userRole === 'youth'): ?>
        <div class="dashboard-card">
            <h3>My Applications</h3>
            <p class="stat"><?php echo $applicationCount; ?></p>
            <a href="applications.php" class="btn btn-primary">View Applications</a>
        </div>
        
        <div class="dashboard-card">
            <h3>My Certificates</h3>
            <p class="stat"><?php echo $certificateCount; ?></p>
            <a href="certificates.php" class="btn btn-primary">View Certificates</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Find Opportunities</h3>
            <p>Discover new internships and programs</p>
            <a href="opportunities.php" class="btn btn-primary">Browse Opportunities</a>
        </div>
        
    <?php elseif ($userRole === 'organization'): ?>
        <div class="dashboard-card">
            <h3>My Opportunities</h3>
            <p class="stat"><?php echo $opportunityCount; ?></p>
            <a href="opportunities.php" class="btn btn-primary">Manage Opportunities</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Applications</h3>
            <p class="stat"><?php echo $applicationCount; ?></p>
            <a href="applications.php" class="btn btn-primary">Review Applications</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Post New Opportunity</h3>
            <p>Create internship or training program</p>
            <a href="opportunities.php?action=create" class="btn btn-primary">Create Opportunity</a>
        </div>
        
    <?php elseif ($userRole === 'admin'): ?>
        <div class="dashboard-card">
            <h3>User Management</h3>
            <p>Manage all users and profiles</p>
            <a href="admin.php?tab=users" class="btn btn-primary">Manage Users</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Opportunity Approval</h3>
            <p>Review and approve opportunities</p>
            <a href="admin.php?tab=opportunities" class="btn btn-primary">Review Opportunities</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Reports & Analytics</h3>
            <p>View platform statistics</p>
            <a href="admin.php?tab=reports" class="btn btn-primary">View Reports</a>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Activity Section -->
<div class="card">
    <h2>Recent Activity</h2>
    <div class="activity-feed">
        <?php
        // Display recent activity based on user role
        if ($userRole === 'youth') {
            $stmt = $pdo->prepare("SELECT a.*, o.title, o.type 
                                  FROM applications a 
                                  JOIN opportunities o ON a.opportunity_id = o.id 
                                  WHERE a.user_id = ? 
                                  ORDER BY a.applied_at DESC 
                                  LIMIT 5");
            $stmt->execute([$userId]);
            $applications = $stmt->fetchAll();
            
            foreach ($applications as $app): ?>
                <div class="activity-item">
                    <p>Applied for <strong><?php echo $app['title']; ?></strong> (<?php echo $app['type']; ?>)</p>
                    <small>Status: <?php echo ucfirst($app['status']); ?> • <?php echo date('M j, Y', strtotime($app['applied_at'])); ?></small>
                </div>
            <?php endforeach;
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>