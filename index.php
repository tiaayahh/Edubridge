<?php
include 'includes/header.php';
include 'includes/functions.php';
?>

<div class="hero-section">
    <div class="card">
        <h1>Welcome to Youth Opportunity Platform</h1>
        <p>Connecting youth with internships, volunteering, and training opportunities</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="cta-buttons">
                <a href="register.php?type=youth" class="btn btn-primary">Join as Youth</a>
                <a href="register.php?type=organization" class="btn btn-primary">Join as Organization</a>
            </div>
        <?php else: ?>
            <div class="welcome-message">
                <h2>Welcome back, <?php echo $_SESSION['username']; ?>!</h2>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="features-section">
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Find Opportunities</h3>
            <p>Discover internships, volunteering, and training programs</p>
        </div>
        <div class="dashboard-card">
            <h3>Build Profile</h3>
            <p>Showcase your skills, education, and availability</p>
        </div>
        <div class="dashboard-card">
            <h3>Get Certified</h3>
            <p>Receive certificates for completed programs</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>