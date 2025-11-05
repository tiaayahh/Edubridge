<?php
// Database configuration for MariaDB
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change if different
define('DB_PASS', ''); // Add your MariaDB password
define('DB_NAME', 'edubridge_platform');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session start
session_start();