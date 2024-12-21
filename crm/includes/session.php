<?php
session_start();

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

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function setLoginSession($userId, $role = 'user') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['last_activity'] = time();
}

function clearLoginSession() {
    session_unset();
    session_destroy();
    // Start a new session to allow for messages
    session_start();
    $_SESSION['timeout_message'] = "Your session has expired. Please log in again.";
}

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

// Call this at the start of every admin page
if (isset($_SESSION['user_id'])) {
    checkSessionTimeout();
}

// Add JavaScript to periodically check session status
if (isset($_SESSION['user_id'])) {
    echo "<script>
        function checkSession() {
            fetch('" . ADMIN_BASE_URL . "check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        window.location.href = '" . ADMIN_BASE_URL . "login.php?timeout=1';
                    }
                });
        }
        // Check every minute
        setInterval(checkSession, 60000);
    </script>";
}
?>
