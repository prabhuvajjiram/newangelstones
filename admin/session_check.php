<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// Debug logging
error_log("Session data: " . print_r($_SESSION, true));

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

// Function to check if user has a specific role
function hasRole($role) {
    error_log("Checking for role: " . $role);
    error_log("Available roles: " . print_r($_SESSION['roles'] ?? [], true));
    return isset($_SESSION['roles']) && in_array($role, $_SESSION['roles']);
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to check if user is staff
function isStaff() {
    return hasRole('staff');
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        error_log("User not logged in, redirecting to login");
        // Store the current URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
}

// Function to require admin role
function requireAdmin() {
    if (!isLoggedIn()) {
        error_log("User not logged in, redirecting to login");
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
    if (!isAdmin()) {
        error_log("User is not admin, unauthorized");
        header('Location: ' . ADMIN_BASE_URL . 'index.php?error=unauthorized');
        exit();
    }
}

// Function to require staff or admin role
function requireStaffOrAdmin() {
    if (!isLoggedIn()) {
        error_log("User not logged in, redirecting to login");
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
    if (!isAdmin() && !isStaff()) {
        error_log("User is not staff or admin, unauthorized");
        header('Location: ' . ADMIN_BASE_URL . 'index.php?error=unauthorized');
        exit();
    }
}

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
