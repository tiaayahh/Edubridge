<?php
include 'includes/header.php';
include 'includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$userType = $_GET['type'] ?? 'youth';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $userType = sanitizeInput($_POST['user_type']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (empty($errors)) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "User with this email or username already exists";
        } else {
            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationCode = md5(uniqid(rand(), true));
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            if ($stmt->execute([$username, $email, $hashedPassword, $userType, $verificationCode])) {
                // Send verification email (simulated)
                sendNotification(null, "Verification code: $verificationCode", 'email');
                
                $_SESSION['success'] = "Registration successful! Please check your email for verification.";
                redirect('login.php');
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="card">
    <h2>Register as <?php echo ucfirst($userType); ?></h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" required 
                   value="<?php echo $_POST['username'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required 
                   value="<?php echo $_POST['email'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required 
                   minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    
    <p style="margin-top: 1rem;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>