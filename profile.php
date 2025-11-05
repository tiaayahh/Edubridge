<?php
include 'includes/header.php';
include 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();
$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userRole === 'youth') {
        $fullName = sanitizeInput($_POST['full_name']);
        $dateOfBirth = sanitizeInput($_POST['date_of_birth']);
        $educationLevel = sanitizeInput($_POST['education_level']);
        $skills = sanitizeInput($_POST['skills']);
        $interests = sanitizeInput($_POST['interests']);
        $availability = sanitizeInput($_POST['availability']);
        $bio = sanitizeInput($_POST['bio']);
        
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM youth_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetch();
        
        if ($profileExists) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE youth_profiles SET full_name = ?, date_of_birth = ?, education_level = ?, skills = ?, interests = ?, availability = ?, bio = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $dateOfBirth, $educationLevel, $skills, $interests, $availability, $bio, $userId]);
        } else {
            // Create new profile
            $stmt = $pdo->prepare("INSERT INTO youth_profiles (user_id, full_name, date_of_birth, education_level, skills, interests, availability, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $fullName, $dateOfBirth, $educationLevel, $skills, $interests, $availability, $bio]);
        }
        
        $success = "Profile updated successfully!";
        
    } elseif ($userRole === 'organization') {
        $orgName = sanitizeInput($_POST['organization_name']);
        $industry = sanitizeInput($_POST['industry']);
        $description = sanitizeInput($_POST['description']);
        $website = sanitizeInput($_POST['website']);
        $contactEmail = sanitizeInput($_POST['contact_email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM organization_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetch();
        
        if ($profileExists) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE organization_profiles SET organization_name = ?, industry = ?, description = ?, website = ?, contact_email = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$orgName, $industry, $description, $website, $contactEmail, $phone, $address, $userId]);
        } else {
            // Create new profile
            $stmt = $pdo->prepare("INSERT INTO organization_profiles (user_id, organization_name, industry, description, website, contact_email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $orgName, $industry, $description, $website, $contactEmail, $phone, $address]);
        }
        
        $success = "Organization profile updated successfully!";
    }
}

// Load existing profile data
if ($userRole === 'youth') {
    $stmt = $pdo->prepare("SELECT * FROM youth_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch() ?: [];
} elseif ($userRole === 'organization') {
    $stmt = $pdo->prepare("SELECT * FROM organization_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch() ?: [];
}
?>

<div class="card">
    <h1><?php echo $userRole === 'youth' ? 'Youth Profile' : 'Organization Profile'; ?></h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php if ($userRole === 'youth'): ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo $profile['full_name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                           value="<?php echo $profile['date_of_birth'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="education_level">Education Level</label>
                <select id="education_level" name="education_level" class="form-control" required>
                    <option value="">Select Education Level</option>
                    <option value="high_school" <?php echo ($profile['education_level'] ?? '') === 'high_school' ? 'selected' : ''; ?>>High School</option>
                    <option value="undergraduate" <?php echo ($profile['education_level'] ?? '') === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                    <option value="graduate" <?php echo ($profile['education_level'] ?? '') === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
                    <option value="phd" <?php echo ($profile['education_level'] ?? '') === 'phd' ? 'selected' : ''; ?>>PhD</option>
                    <option value="other" <?php echo ($profile['education_level'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="skills">Skills (comma separated)</label>
                <textarea id="skills" name="skills" class="form-control" rows="3" 
                          placeholder="e.g., PHP, JavaScript, Project Management, Communication"><?php echo $profile['skills'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="interests">Interests (comma separated)</label>
                <textarea id="interests" name="interests" class="form-control" rows="3" 
                          placeholder="e.g., Web Development, Data Science, Community Service"><?php echo $profile['interests'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="availability">Availability</label>
                <select id="availability" name="availability" class="form-control" required>
                    <option value="">Select Availability</option>
                    <option value="full_time" <?php echo ($profile['availability'] ?? '') === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                    <option value="part_time" <?php echo ($profile['availability'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                    <option value="flexible" <?php echo ($profile['availability'] ?? '') === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio/Introduction</label>
                <textarea id="bio" name="bio" class="form-control" rows="5" 
                          placeholder="Tell us about yourself, your goals, and what you're looking for..."><?php echo $profile['bio'] ?? ''; ?></textarea>
            </div>
            
        <?php elseif ($userRole === 'organization'): ?>
            <div class="form-group">
                <label for="organization_name">Organization Name</label>
                <input type="text" id="organization_name" name="organization_name" class="form-control" 
                       value="<?php echo $profile['organization_name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="industry">Industry</label>
                    <input type="text" id="industry" name="industry" class="form-control" 
                           value="<?php echo $profile['industry'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" 
                           value="<?php echo $profile['contact_email'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control" 
                           value="<?php echo $profile['website'] ?? ''; ?>" placeholder="https://">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo $profile['phone'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="3"><?php echo $profile['address'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="description">Organization Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" 
                          placeholder="Describe your organization, mission, and values..."><?php echo $profile['description'] ?? ''; ?></textarea>
            </div>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>