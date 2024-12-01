<?php
if (!function_exists('isLoggedIn')) {
    // Function to check if user is logged in
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    // Function to check if user is admin
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('requireLogin')) {
    // Function to require login
    function requireLogin() {
        if (!isLoggedIn()) {
            // Store the current URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . ADMIN_BASE_URL . 'login.php');
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
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
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('setLoginSession')) {
    function setLoginSession($userId, $role = 'user') {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['last_activity'] = time();
    }
}

if (!function_exists('clearLoginSession')) {
    function clearLoginSession() {
        session_unset();
        session_destroy();
        // Start a new session to allow for messages
        session_start();
        $_SESSION['timeout_message'] = "Your session has expired. Please log in again.";
    }
}

if (!function_exists('checkSessionTimeout')) {
    function checkSessionTimeout() {
        $timeout = 30 * 60; // 30 minutes in seconds
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            clearLoginSession();
            // Use JavaScript to redirect to ensure all resources are properly unloaded
            echo "<script>window.location.href = '" . ADMIN_BASE_URL . "login.php?timeout=1';</script>";
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
