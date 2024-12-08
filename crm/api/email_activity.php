<?php
require_once '../includes/config.php';
require_once '../includes/EmailManager.php';

header('Content-Type: application/json');

try {
    $emailManager = new EmailManager($pdo);
    
    // Get the last 30 days of email activity
    $activity = $emailManager->getEmailActivityStats(30);
    
    // Format data for Chart.js
    $dates = [];
    $sent = [];
    $received = [];
    
    foreach ($activity as $day) {
        $dates[] = $day['date'];
        $sent[] = $day['sent_count'];
        $received[] = $day['received_count'];
    }
    
    echo json_encode([
        'success' => true,
        'dates' => $dates,
        'sent' => $sent,
        'received' => $received
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
