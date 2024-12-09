<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/auth_config.php';

// Store the quote ID and return URL
if (isset($_GET['quote_id'])) {
    $_SESSION['pending_quote_id'] = $_GET['quote_id'];
}
$_SESSION['auth_redirect'] = $_GET['return'] ?? 'quotes.php';

// Create Google OAuth URL specifically for email
$redirect_uri = $is_local ? 
    'http://localhost:3000/crm/email_auth_callback.php' : 
    'https://www.theangelstones.com/crm/email_auth_callback.php';

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => implode(' ', GMAIL_SCOPES),
    'access_type' => 'offline',
    'prompt' => 'consent',
    'state' => bin2hex(random_bytes(16)),
    'login_hint' => $_SESSION['email'] // Pre-fill the user's email
];

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect to Google
header('Location: ' . $auth_url);
exit;
