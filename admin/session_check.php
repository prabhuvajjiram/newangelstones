<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
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

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
