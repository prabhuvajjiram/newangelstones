<?php
/**
 * Angel Granites Chat Widget Configuration
 * 
 * Production configuration. For local development, create a config.local.php file
 * with your local settings.
 */

// Check if this is a direct access attempt to config.php itself (not an include)
// This prevents direct access to config.php while allowing API endpoints to work
if (basename($_SERVER['SCRIPT_FILENAME']) === 'config.php' && !defined('LOCAL_ENTRY_POINT') && php_sapi_name() !== 'cli' && !isset($_GET['test'])) {
    // This is a direct access attempt to config.php itself
    header('Location: local_test.php');
    exit;
}



// RingCentral API Configuration
define('RINGCENTRAL_CLIENT_ID', 'Vq6JLatlKvVb55XVXv0B2z');
define('RINGCENTRAL_CLIENT_SECRET', 'VY19eiFTqfZcCnRpjnPeandEgWIEgb25IfvaJ2yEaT6x');
define('RINGCENTRAL_SERVER', 'https://platform.ringcentral.com');
define('RINGCENTRAL_DEFAULT_CHAT_ID', '147193044998'); // Default team chat ID for visitor conversations (angelgranites)

// JWT Authentication - This replaces the OAuth flow
define('RINGCENTRAL_JWT_TOKEN', 'eyJraWQiOiI4NzYyZjU5OGQwNTk0NGRiODZiZjVjYTk3ODA0NzYwOCIsInR5cCI6IkpXVCIsImFsZyI6IlJTMjU2In0.eyJhdWQiOiJodHRwczovL3BsYXRmb3JtLnJpbmdjZW50cmFsLmNvbS9yZXN0YXBpL29hdXRoL3Rva2VuIiwic3ViIjoiNjMzOTU1ODUwMzEiLCJpc3MiOiJodHRwczovL3BsYXRmb3JtLnJpbmdjZW50cmFsLmNvbSIsImV4cCI6Mzg5NTA3MjgzMiwiaWF0IjoxNzQ3NTg5MTg1LCJqdGkiOiJBdlNaRTFrS1RQV2hUWFZBZTNUYWFBIn0.H1e4UYqhoKWytYsf1Fv6fRI7sWli0bMRl5U8TFTVyyb6os6n4hshOUZzlmuM4JPWwiHTrAXssRzFPSFFEejj9Rdr5tXrQ3EzE8fQs5wIOJ8yyxsr9mU8VQic8ev6_TNGBLY6ZvObrMT11-6sipOdsRYok2ChxxuPHB_SoRMyxmDI-5FrzODeR0QqJ7sHAtjDkUs1x3Z9gBEtnDtxT3EWCHlV4p5hWzMAECPlLN2VStgEgy6_yiimg13tm2d-5C0unv0EAp2MovgCL15H3anez5OpwB37JMykMUAR0zZ5JqqKvgwqzdZPQv2JqrkKPhRjLHVBAaJXWhW9DbtkFEH19g');
define('RINGCENTRAL_AUTH_TYPE', 'jwt'); // Use 'jwt' instead of 'oauth'

// We're using 3-legged OAuth authentication flow
// No need to store username/password credentials

// Determine if we're in local development based on server name
$isLocalDev = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost:3000' || $_SERVER['HTTP_HOST'] == '127.0.0.1:3000'));

// Set the appropriate redirect URI based on environment
if ($isLocalDev) {
    define('RINGCENTRAL_REDIRECT_URI', 'http://localhost:3000/chat/callback.php');
    // Database Configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'angelstones_quotes_new');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('RINGCENTRAL_REDIRECT_URI', 'https://theangelstones.com/chat/callback.php');
    // Database Configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'theangel_quotes');
    define('DB_USER', 'theangel');
    define('DB_PASS', '#QSX$V032uXY');
}

// Define the URL for auth and token endpoints
define('RINGCENTRAL_AUTH_URL', RINGCENTRAL_SERVER . '/restapi/oauth/authorize');
define('RINGCENTRAL_TOKEN_URL', RINGCENTRAL_SERVER . '/restapi/oauth/token');

// RingCentral Chat ID (ID of the team chat to post messages to)
define('RINGCENTRAL_TEAM_CHAT_ID', '147193044998'); // angelgranites team chat

// Define a system flag that will be added to all messages sent by our system
// This helps the webhook identify and ignore our own messages to prevent loops
define('SYSTEM_MESSAGE_FLAG', '[SYSTEM_MESSAGE]'); // A unique string that won't appear in normal messages

// RingCentral Webhook Configuration
define('RINGCENTRAL_WEBHOOK_ID', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJvdCI6ImMiLCJvaSI6IjYxNjYxMTEwMjczIiwiaWQiOiIzMDY3MDc2NjM1In0.D2nJCD9_Yt3p41aWUcVvrDPikQkTvUGRQZrlkwTOT74');
define('RINGCENTRAL_WEBHOOK_URI', 'https://hooks.glip.com/webhook/v2/eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJvdCI6ImMiLCJvaSI6IjYxNjYxMTEwMjczIiwiaWQiOiIzMDY3MDc2NjM1In0.D2nJCD9_Yt3p41aWUcVvrDPikQkTvUGRQZrlkwTOT74');
define('RINGCENTRAL_WEBHOOK_ENABLED', true); // Set to true to enable webhook integration

// Set to true to attempt to forward messages to RingCentral (requires working OAuth)
// Set to false to only store messages in the database without forwarding to RingCentral
define('FORWARD_TO_RINGCENTRAL', true); // Set to true to enable RingCentral integration with dedicated chat rooms

// RingCentral Team Chat Configuration
define('RINGCENTRAL_TEAM_CHAT_ENABLED', true);
define('RINGCENTRAL_TEAM_CHAT_MEMBERS', json_encode([
    ['id' => '63395585031', 'email' => 'purchase@theangelstones.com']
]));

// Optional: Define extension IDs of system users if you want to filter by them as well
$systemUsers = [
    '101',  // Example extension ID 1
    //'102',    // Example extension ID 2
    //'103',    // Example extension ID 3
    //'104',    // Example extension ID 4
    //'105',    // Example extension ID 5
    // Add more as needed
];

define('SYSTEM_USERS', json_encode($systemUsers));

// Default production configuration
$config = [
    // Application Settings
    'CHAT_COOKIE_NAME' => 'angelstones_chat',
    'CHAT_COOKIE_EXPIRE' => 30 * 24 * 60 * 60, // 30 days
    'LOCAL_DEVELOPMENT' => false,
    'BASE_PATH' => __DIR__,
    
    // Error Logging
    'DISPLAY_ERRORS' => 0,
    'LOG_ERRORS' => 1,
    'ERROR_LOG' => __DIR__ . '/chat_errors.log',
    
    // Timeout settings (in seconds)
    'RESPONSE_TIMEOUT' => 60, // 1 minute timeout for rep response
    
    // Email Configuration
    'FAILOVER_EMAIL' => 'info@theangelstones.com',
    'FAILOVER_SUBJECT' => 'New Chat Message from Website Visitor',
    'MAIL_FROM' => 'info@theangelstones.com',
    'MAIL_FROM_NAME' => 'The Angel Stones Chat',
    
    // Sales Team Extensions (up to 5)
    'SALES_REPS' => [
        '101',
        '102',
        '103',
        '104',
        '105'
    ]
];

// Load local configuration if it exists
if (file_exists(__DIR__ . '/config.local.php')) {
    $localConfig = require __DIR__ . '/config.local.php';
    if (is_array($localConfig)) {
        $config = array_merge($config, $localConfig);
    }
}

// Define all configuration constants
foreach ($config as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Define derived constants if not already defined
if (!defined('RINGCENTRAL_AUTH_URL')) {
    define('RINGCENTRAL_AUTH_URL', RINGCENTRAL_SERVER . '/restapi/oauth/authorize');
}
if (!defined('RINGCENTRAL_TOKEN_URL')) {
    define('RINGCENTRAL_TOKEN_URL', RINGCENTRAL_SERVER . '/restapi/oauth/token');
}

// Set error reporting based on environment
ini_set('display_errors', DISPLAY_ERRORS);
ini_set('log_errors', LOG_ERRORS);
ini_set('error_log', ERROR_LOG);

// For backward compatibility
if (!isset($SALES_REPS) && defined('SALES_REPS')) {
    $SALES_REPS = SALES_REPS;
}

// Chat Widget Settings
define('CHAT_TITLE', 'Chat with Us');
define('CHAT_SUBTITLE', 'We\'re here to help!');
define('CHAT_PRIMARY_COLOR', '#2c3e50');
define('CHAT_SHOW_ON_LOAD', false);
define('CHAT_POSITION', 'right'); // 'left' or 'right'

// CORS Configuration (Update with your domain)
$allowedOrigins = [
    'https://angelgranites.com',
    'https://www.angelgranites.com',
    'http://localhost'
];

// Set CORS headers if origin is allowed
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
}

// Check if we're in a web environment
$isCliMode = (php_sapi_name() === 'cli');

// Handle preflight OPTIONS request (only in web mode)
if (!$isCliMode && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set timezone
date_default_timezone_set('America/New_York');

// Error reporting
error_reporting(E_ALL);

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr in $errfile on line $errline");
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        return false; // Let PHP handle errors in development
    }
    return true; // Suppress errors in production
});

// Set exception handler
set_exception_handler(function($e) {
    error_log("Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        // In development, show the error
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        // In production, show a generic error
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'An error occurred. Please try again later.'
        ]);
    }
    exit();
});

/**
 * Utility Functions
 */

// Set CORS headers for API responses
function setCorsHeaders() {
    global $allowedOrigins;
    
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
    }
}

// Set JSON content type for API responses
function setJsonHeader() {
    header('Content-Type: application/json');
}

// Send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    setJsonHeader();
    echo json_encode($data);
    exit();
}

// Sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    generateCsrfToken();
}
?>
