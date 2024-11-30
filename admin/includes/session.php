<?php
session_start();

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
}

// Check session timeout (30 minutes)
function checkSessionTimeout() {
    $timeout = 30 * 60; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        clearLoginSession();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Call this at the start of every admin page
if (isset($_SESSION['user_id'])) {
    checkSessionTimeout();
}
?>
