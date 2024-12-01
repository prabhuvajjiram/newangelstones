<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment detection
$server_name = $_SERVER['SERVER_NAME'];
$is_production = ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com');

// Database configuration
if ($is_production) {
    if (file_exists(__DIR__ . '/db_config.php')) {
        require_once __DIR__ . '/db_config.php';
        error_log("Loaded production db_config.php");
        // Set database variables from constants
        $db_host = DB_HOST;
        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASS;
    } else {
        throw new Exception("db_config.php not found");
    }
    define('BASE_URL', 'https://www.theangelstones.com/admin/');
} else {
    // Local development database credentials
    $db_host = '127.0.0.1';
    $db_name = 'angelstones_quotes_new';
    $db_user = 'root';
    $db_pass = '';
    
    // Local development URL
    $port = $_SERVER['SERVER_PORT'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    define('BASE_URL', $protocol . $server_name . $port_suffix . '/admin/');
}

// Create PDO database connection
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed: Database error occurred");
}

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

/**
 * Get the full URL for a path
 * @param string $path The path relative to the admin directory
 * @return string The full URL
 */
function getUrl($path = '') {
    if (!defined('BASE_URL')) {
        // Fallback if BASE_URL is not defined
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $server = $_SERVER['HTTP_HOST'];
        $port = $_SERVER['SERVER_PORT'];
        $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
        $admin_path = '/admin/';
        return $protocol . $server . $port_suffix . $admin_path . ltrim($path, '/');
    }
    return BASE_URL . ltrim($path, '/');
}

// Google OAuth configuration
$google_client_id = 'your_google_client_id';
$google_client_secret = 'your_google_client_secret';
$google_redirect_uri = 'http://localhost:3000/admin/oauth2callback.php';
