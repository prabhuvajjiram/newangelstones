<?php
require_once 'includes/session.php';

header('Content-Type: application/json');

$response = [
    'valid' => false,
    'message' => ''
];

if (isset($_SESSION['user_id'])) {
    $timeout = 30 * 60; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        clearLoginSession();
        $response['message'] = 'Session expired';
    } else {
        $_SESSION['last_activity'] = time();
        $response['valid'] = true;
    }
}

echo json_encode($response);
exit;
