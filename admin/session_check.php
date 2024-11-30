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

// Function to require admin role
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'index.php?error=unauthorized');
        exit;
    }
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
