<?php
session_start();
require_once 'includes/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Get the correct base URL
$server_name = $_SERVER['SERVER_NAME'];
if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
    $base_url = '/crm';
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port = $_SERVER['SERVER_PORT'];
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    $base_url = $protocol . $server_name . $port_suffix . '/crm';
}

// Redirect to login page with correct path
header('Location: ' . $base_url . '/login.php');
exit();
