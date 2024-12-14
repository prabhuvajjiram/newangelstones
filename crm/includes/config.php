<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ini_set('display_errors', 0);
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'A server error occurred: ' . $error['message']
            ]);
            exit;
        }
    });
}

// Environment detection
$server_name = $_SERVER['SERVER_NAME'];
$is_production = ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com');

// Database configuration
if ($is_production) {
    if (file_exists(__DIR__ . '/db_config.php')) {
        require_once __DIR__ . '/db_config.php';
        // Set database variables from constants
        $db_host = DB_HOST;
        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASS;
    } else {
        throw new Exception("db_config.php not found");
    }
    define('BASE_URL', 'https://www.theangelstones.com');
} else {
    // Local development database credentials
    $db_host = '127.0.0.1';
    $db_name = 'angelstones_quotes_new';
    $db_user = 'root';
    $db_pass = '';
    
    // Local development URL with port 3000
    define('BASE_URL', 'http://localhost:3000');
}

// Load Gmail OAuth2 configuration
require_once __DIR__ . '/auth_config.php';

// Gmail OAuth2 endpoints (same for both environments)
define('GMAIL_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GMAIL_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GMAIL_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo');

// Required scopes for Gmail access
if (!defined('GMAIL_SCOPES')) {
    define('GMAIL_SCOPES', [
        'https://mail.google.com/',
        'https://www.googleapis.com/auth/userinfo.email'
    ]);
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
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit;
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Could not connect to the database. Please try again later.");
    }
}

// Define application paths
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('PDF_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'pdf_quotes');
define('PDF_URL', BASE_URL . '/crm/pdf_quotes');
define('CRM_PATH', '/crm');

// Create PDF directory if it doesn't exist
if (!file_exists(PDF_DIR)) {
    mkdir(PDF_DIR, 0777, true);
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
    if (empty($path)) {
        return rtrim(BASE_URL, '/') . '/crm';
    }
    return rtrim(BASE_URL, '/') . '/crm/' . ltrim($path, '/');
}
