<?php
// Error reporting - at the very top
error_reporting(E_ALL);
ini_set('display_errors', 0); // Changed to 0 to prevent HTML error output
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php_errors.log');

// Log the script execution
error_log("Config.php started execution");

// Initialize global PDO variable
global $pdo;

try {
    // Session configuration
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        error_log("Session started successfully");
    }

    // Environment detection
    $server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';
    error_log("Server name: " . $server_name);
    
    $is_production = ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com');
    error_log("Is production: " . ($is_production ? 'yes' : 'no'));

    // Database configuration
    if ($is_production) {
        if (file_exists(__DIR__ . '/db_config.php')) {
            require_once __DIR__ . '/db_config.php';
            error_log("Loaded production db_config.php");
        } else {
            throw new Exception("db_config.php not found");
        }
        define('BASE_URL', 'https://www.theangelstones.com/admin/');
    } else {
        define('DB_HOST', '127.0.0.1');  // Using IP instead of localhost
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'angelstones_quotes_new');
        define('BASE_URL', 'http://localhost:3000/admin/');
        error_log("Loaded local database config");
    }

    // Create PDO connection
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        error_log("Database connection established successfully");
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed. Please try again later.");
    }

    // Make PDO available globally
    $GLOBALS['pdo'] = $pdo;

    // Google OAuth Configuration
    define('GOOGLE_CLIENT_ID', '204729895453-04n5arjok7fvjcn6dshvq4d65ssju45h.apps.googleusercontent.com');
    define('GOOGLE_CLIENT_SECRET', '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');
    define('GOOGLE_OAUTH_REDIRECT_URI', 'https://theangelstones.com/admin/gmail_callback.php');
    define('GMAIL_SENDER_EMAIL', 'info@theangelstones.com');

} catch (Exception $e) {
    error_log("Fatal error in config.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die("A configuration error occurred. Please check the error log.");
}

// Helper Functions
function getUrl($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

error_log("Config.php completed execution");
?>
