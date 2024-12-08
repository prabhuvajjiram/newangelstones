<?php
require_once '../includes/config.php';
require_once '../session_check.php';
require_once '../includes/ActivityManager.php';

header('Content-Type: application/json');

try {
    $activityManager = new ActivityManager($pdo);
    $categories = $activityManager->getCategories();
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
