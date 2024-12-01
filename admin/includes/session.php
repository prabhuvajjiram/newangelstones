<?php
session_start();

// Define base URL for admin
define('ADMIN_BASE_URL', '/admin/');

// Include session functions
require_once __DIR__ . '/session_functions.php';

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
