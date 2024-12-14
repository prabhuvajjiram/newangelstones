<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '204729895453-5pm7f5oalknp8vl1q9sqf658j6ce996o.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Determine if we're in a local environment
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

// Set the redirect URIs based on environment
if ($is_local) {
    define('GOOGLE_REDIRECT_URI', 'http://localhost:3000/crm/auth_callback.php');
    define('GOOGLE_EMAIL_REDIRECT_URI', 'http://localhost:3000/crm/email_auth_callback.php');
} else {
    define('GOOGLE_REDIRECT_URI', 'https://www.theangelstones.com/crm/auth_callback.php');
    define('GOOGLE_EMAIL_REDIRECT_URI', 'https://www.theangelstones.com/crm/email_auth_callback.php');
}

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session management functions
function redirectToLogin() {
    header("Location: " . getUrl('login.php'));
    exit();
}

function checkSession() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        redirectToLogin();
    }
}

// Add these scopes
if (!defined('GMAIL_SCOPES')) {
    define('GMAIL_SCOPES', [
        'https://www.googleapis.com/auth/gmail.send',
        'https://www.googleapis.com/auth/gmail.compose',
        'https://www.googleapis.com/auth/userinfo.email'
    ]);
}