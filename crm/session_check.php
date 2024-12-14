<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("Session check - Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

require_once 'includes/config.php';

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Define admin base URL if not already defined
if (!defined('ADMIN_BASE_URL')) {
    $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
        define('ADMIN_BASE_URL', '/crm/');
    } else {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
        $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
        define('ADMIN_BASE_URL', $protocol . $server_name . $port_suffix . '/crm/');
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    error_log("Checking if logged in - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    error_log("Checking if admin - Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

// Function to check if user is staff
function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

// Function to require login
function requireLogin() {
    global $isAjax;
    
    if (!isLoggedIn()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Please log in to continue',
                'redirect' => ADMIN_BASE_URL . 'login.php'
            ]);
            exit;
        } else {
            // Store the current URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . ADMIN_BASE_URL . 'login.php');
            exit;
        }
    }
    return true;
}

// Function to require admin role
function requireAdmin() {
    global $isAjax;
    
    if (!isAdmin()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]);
            exit;
        } else {
            header('Location: ' . ADMIN_BASE_URL . 'unauthorized.php');
            exit;
        }
    }
    return true;
}

// Function to require staff or admin role
function requireStaffOrAdmin() {
    global $isAjax;
    
    if (!isStaff() && !isAdmin()) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Staff or admin access required'
            ]);
            exit;
        } else {
            header('Location: ' . ADMIN_BASE_URL . 'unauthorized.php');
            exit;
        }
    }
    return true;
}

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
