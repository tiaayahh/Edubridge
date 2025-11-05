<?php
include 'includes/header.php';
include 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$userRole = getUserRole();
$action = $_GET['action'] ?? '';
$opportunityId = $_GET['id'] ?? '';
$errors = [];
$success = '';

// Handle opportunity creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $type = sanitizeInput($_POST['type']);
    $requirements = sanitizeInput($_POST['requirements']);
    $skillsRequired = sanitizeInput($_POST['skills_required']);
    $duration = sanitizeInput($_POST['duration']);
    $deadline = sanitizeInput($_POST['deadline']);
    
    if (empty($title) || empty($description) || empty($type) || empty($deadline)) {
        $errors[] = "Please fill in all required fields";
    }
    
    if (empty($errors)) {
        if ($action === 'edit' && $opportunityId) {
            // Update existing opportunity
            $stmt = $pdo->prepare("UPDATE opportunities SET title = ?, description = ?, type = ?, requirements = ?, skills_required = ?, duration = ?, deadline = ? WHERE id = ? AND organization_id = ?");
            $stmt->execute([$title, $description, $type, $requirements, $skillsRequired, $duration, $deadline, $opportunityId, $userId]);
            $success = "Opportunity updated successfully!";
        } else {
            // Create new opportunity
            $stmt = $pdo->prepare("INSERT INTO opportunities (organization_id, title, description, type, requirements, skills_required, duration, deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $description, $type, $requirements, $skillsRequired, $duration, $deadline]);
            $success = "Opportunity created successfully! It will be visible after admin approval.";
        }
    }
}

// Handle opportunity deletion
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM opportunities WHERE id = ? AND organization_id = ?");
    $stmt->execute([$deleteId, $userId]);
    $success = "Opportunity deleted successfully!";
}

// Load opportunity for editing
if ($action === 'edit' && $opportunityId) {
    $stmt = $pdo->prepare("SELECT * FROM opportunities WHERE id = ? AND organization_id = ?");
    $stmt->execute([$opportunityId, $userId]);
    $opportunity = $stmt->fetch();
    
    if (!$opportunity) {
        $errors[] = "Opportunity not found or you don't have permission to edit it";
        $action = 'create';
    }
}
?>

<div class="card">
    <h1>
        <?php 
        if ($action === 'create') echo 'Create New Opportunity';
        elseif ($action === 'edit') echo 'Edit Opportunity';
        else echo 'Opportunities';
        ?>
    </h1>
    
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
    
    <?php if ($userRole === 'organization' && ($action === 'create' || $action === 'edit')): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Opportunity Title *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?php echo $opportunity['title'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="type">Opportunity Type *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="internship" <?php echo ($opportunity['type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                    <option value="volunteering" <?php echo ($opportunity['type'] ?? '') === 'volunteering' ? 'selected' : ''; ?>>Volunteering</option>
                    <option value="training" <?php echo ($opportunity['type'] ?? '') === 'training' ? 'selected' : ''; ?>>Training Program</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="5" required
                          placeholder="Describe the opportunity, responsibilities, and benefits..."><?php echo $opportunity['description'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="requirements">Requirements</label>
                <textarea id="requirements" name="requirements" class="form-control" rows="3"
                          placeholder="List any specific requirements or qualifications needed..."><?php echo $opportunity['requirements'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="skills_required">Skills Required</label>
                <textarea id="skills_required" name="skills_required" class="form-control" rows="3"
                          placeholder="List desired skills (comma separated)..."><?php echo $opportunity['skills_required'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" class="form-control" 
                           value="<?php echo $opportunity['duration'] ?? ''; ?>" placeholder="e.g., 3 months, 6 weeks">
                </div>
                
                <div class="form-group">
                    <label for="deadline">Application Deadline *</label>
                    <input type="date" id="deadline" name="deadline" class="form-control" 
                           value="<?php echo $opportunity['deadline'] ?? ''; ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?php echo $action === 'edit' ? 'Update Opportunity' : 'Create Opportunity'; ?>
            </button>
            <a href="opportunities.php" class="btn btn-secondary">Cancel</a>
        </form>
        
    <?php elseif ($userRole === 'organization'): ?>
        <div class="opportunity-actions">
            <a href="?action=create" class="btn btn-primary">Create New Opportunity</a>
        </div>
        
        <h2>My Opportunities</h2>
        <div class="opportunity-grid">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM opportunities WHERE organization_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $opportunities = $stmt->fetchAll();
            
            if (empty($opportunities)): ?>
                <div class="alert alert-info">
                    You haven't created any opportunities yet. <a href="?action=create">Create your first opportunity</a>.
                </div>
            <?php else: 
                foreach ($opportunities as $opp): ?>
                    <div class="opportunity-card">
                        <h3><?php echo htmlspecialchars($opp['title']); ?></h3>
                        <div class="opportunity-meta">
                            <span class="type-badge type-<?php echo $opp['type']; ?>"><?php echo ucfirst($opp['type']); ?></span>
                            <span class="status-badge <?php echo $opp['is_approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $opp['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                            </span>
                        </div>
                        <p class="opportunity-description"><?php echo htmlspecialchars(substr($opp['description'], 0, 150)); ?>...</p>
                        <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($opp['deadline'])); ?></p>
                        
                        <div class="opportunity-actions">
                            <a href="?action=edit&id=<?php echo $opp['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="?delete=<?php echo $opp['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this opportunity?')">Delete</a>
                            <a href="applications.php?opportunity=<?php echo $opp['id']; ?>" class="btn btn-success btn-sm">View Applications</a>
                        </div>
                    </div>
                <?php endforeach;
            endif; ?>
        </div>
        
    <?php else: // Youth view ?>
        <h2>Available Opportunities</h2>
        
        <div class="filters">
            <button class="filter-btn btn btn-outline active" data-filter="all">All</button>
            <button class="filter-btn btn btn-outline" data-filter="internship">Internships</button>
            <button class="filter-btn btn btn-outline" data-filter="volunteering">Volunteering</button>
            <button class="filter-btn btn btn-outline" data-filter="training">Training</button>
        </div>
        
        <div class="opportunity-grid">
            <?php
            $stmt = $pdo->query("SELECT o.*, u.username as organization_name 
                                FROM opportunities o 
                                JOIN users u ON o.organization_id = u.id 
                                WHERE o.is_approved = 1 AND o.is_active = 1 AND o.deadline >= CURDATE() 
                                ORDER BY o.created_at DESC");
            $opportunities = $stmt->fetchAll();
            
            if (empty($opportunities)): ?>
                <div class="alert alert-info">
                    No opportunities available at the moment. Please check back later.
                </div>
            <?php else: 
                foreach ($opportunities as $opp): ?>
                    <div class="opportunity-card" data-type="<?php echo $opp['type']; ?>">
                        <h3><?php echo htmlspecialchars($opp['title']); ?></h3>
                        <div class="opportunity-meta">
                            <span class="organization"><?php echo htmlspecialchars($opp['organization_name']); ?></span>
                            <span class="type-badge type-<?php echo $opp['type']; ?>"><?php echo ucfirst($opp['type']); ?></span>
                        </div>
                        <p class="opportunity-description"><?php echo htmlspecialchars(substr($opp['description'], 0, 150)); ?>...</p>
                        <div class="opportunity-details">
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($opp['duration']); ?></p>
                            <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($opp['deadline'])); ?></p>
                        </div>
                        
                        <div class="opportunity-actions">
                            <button type="button" class="btn btn-primary" 
                                    onclick="applyForOpportunity(<?php echo $opp['id']; ?>)">Apply Now</button>
                            <a href="opportunity-details.php?id=<?php echo $opp['id']; ?>" class="btn btn-outline">View Details</a>
                        </div>
                    </div>
                <?php endforeach;
            endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.filters {
    margin-bottom: 2rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-outline {
    background: transparent;
    border: 2px solid #667eea;
    color: #667eea;
}

.btn-outline.active {
    background: #667eea;