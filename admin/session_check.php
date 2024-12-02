<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// Define admin base URL if not already defined
if (!defined('ADMIN_BASE_URL')) {
    $server_name = $_SERVER['SERVER_NAME'];
    if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
        define('ADMIN_BASE_URL', '/admin/');
    } else {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $port = $_SERVER['SERVER_PORT'];
        $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
        define('ADMIN_BASE_URL', $protocol . $server_name . $port_suffix . '/admin/');
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin');
}

// Function to check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}

// Function to check if user is staff
function isStaff() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the current URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
}

// Function to require admin role
function requireAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
    if (!isAdmin()) {
        header('Location: ' . ADMIN_BASE_URL . 'index.php?error=unauthorized');
        exit();
    }
}

// Function to require staff or admin role
function requireStaffOrAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
    if (!isAdmin() && !isStaff()) {
        header('Location: ' . ADMIN_BASE_URL . 'index.php?error=unauthorized');
        exit();
    }
}

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
