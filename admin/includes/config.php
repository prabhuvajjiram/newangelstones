<?php
// Database configuration
define('DB_HOST', '127.0.0.1');  // Using IP instead of localhost
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'angelstones_quotes_new');

// Environment detection
$is_local = ($_SERVER['SERVER_PORT'] == '3000');
define('BASE_URL', $is_local ? 'http://localhost:3000/admin/' : '/admin/');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Function to get URL
function getUrl($path = '') {
    return BASE_URL . ltrim($path, '/');
}
?>