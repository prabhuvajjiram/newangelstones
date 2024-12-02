<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '1055611549976-rkle7gddvvf0jrfkc2jfqtv2ej5mhvqr.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Set the redirect URI based on environment
$server_name = $_SERVER['SERVER_NAME'];
if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
    define('GOOGLE_REDIRECT_URI', 'https://theangelstones.com/admin/auth_callback.php');
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port = $_SERVER['SERVER_PORT'];
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    define('GOOGLE_REDIRECT_URI', $protocol . $server_name . $port_suffix . '/admin/auth_callback.php');
}

// Allow only theangelstones.com domain
$ALLOWED_DOMAINS = ['theangelstones.com'];

// Also allow specific email addresses (like Gmail accounts for admins)
$ALLOWED_EMAILS = [
    'prabhuvajjiram@gmail.com',     // Allow this Gmail account
    'prabhu@theangelstones.com'     // This would be allowed anyway due to domain
];
?>