<?php
require_once '../includes/config.php';
require_once '../session_check.php';
require_once '../includes/ActivityManager.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    $activityManager = new ActivityManager($pdo);
    $result = $activityManager->exportActivities($_SESSION['user_id'], $data);
    
    echo json_encode([
        'success' => true,
        'filename' => $result['filename'],
        'record_count' => $result['record_count']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
