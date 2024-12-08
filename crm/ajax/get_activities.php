<?php
require_once '../includes/config.php';
require_once '../session_check.php';
require_once '../includes/ActivityManager.php';

header('Content-Type: application/json');

try {
    $activityManager = new ActivityManager($pdo);
    
    // Get filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $filters = [
        'customer_id' => isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null,
        'company_id' => isset($_GET['company_id']) ? (int)$_GET['company_id'] : null,
        'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : null,
        'importance' => isset($_GET['importance']) ? $_GET['importance'] : null,
        'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
        'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null
    ];
    
    // Get activities
    $activities = $activityManager->getActivityTimeline($filters, $page);
    
    // Get total count for pagination
    $totalCount = $activityManager->getActivityCount($filters);
    $totalPages = ceil($totalCount / 50); // 50 items per page
    
    // Get analytics if requested
    $analytics = null;
    if (isset($_GET['include_analytics'])) {
        $analytics = $activityManager->getActivityAnalytics(
            $filters['customer_id'],
            $filters['date_from'],
            $filters['date_to']
        );
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'analytics' => $analytics
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
