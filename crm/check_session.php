<?php
require_once 'includes/session.php';
session_start();
header('Content-Type: application/json');

$response = [
    'valid' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'extend_session') {
        if (isset($_SESSION['user_id'])) {
            // Update session last activity time
            $_SESSION['last_activity'] = time();
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

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
