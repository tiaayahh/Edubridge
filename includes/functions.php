<?php
include 'config.php';

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Function to redirect user
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to generate certificate
function generateCertificate($userId, $opportunityId, $completionDate) {
    // Certificate generation logic here
    $certificateId = uniqid('CERT_');
    return $certificateId;
}

// Function to send notification
function sendNotification($userId, $message, $type = 'email') {
    // Notification logic here
    return true;
}
?>