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

// Allowed Google email domains (empty array means all domains are allowed)
$ALLOWED_DOMAINS = [];

// Allowed Google email addresses (empty array means all emails are allowed)
$ALLOWED_EMAILS = [
    'prabhuvajjiram@gmail.com',
    'prabhu@theangelstones.com'
];
?>