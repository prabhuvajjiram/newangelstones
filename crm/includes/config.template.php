<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Gmail OAuth2 credentials
define('GMAIL_CLIENT_ID', 'your_gmail_client_id');
define('GMAIL_CLIENT_SECRET', 'your_gmail_client_secret');
define('GMAIL_REDIRECT_URI', 'https://your-domain.com/crm/email_auth_callback.php');

// OAuth2 endpoints
define('GMAIL_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GMAIL_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GMAIL_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo');

// Required scopes for Gmail access
define('GMAIL_SCOPES', [
    'https://mail.google.com/',
    'https://www.googleapis.com/auth/userinfo.email'
]);

// Site configuration
define('SITE_URL', 'https://your-domain.com');
define('CRM_PATH', '/crm');

// Initialize database connection
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
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check the configuration.");
}

// Time zone setting
date_default_timezone_set('America/New_York');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
