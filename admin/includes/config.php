<?php
// Database configuration
define('DB_HOST', '127.0.0.1');  // Using IP instead of localhost
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'angelstones_quotes_new');

// Environment detection and URL configuration
$server_name = $_SERVER['SERVER_NAME'];
$is_production = ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com');

if ($is_production) {
    define('BASE_URL', 'https://www.theangelstones.com/admin/');
} else {
    // Local development
    $port = $_SERVER['SERVER_PORT'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    define('BASE_URL', $protocol . $server_name . $port_suffix . '/admin/');
}

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
