<?php
require_once '../includes/config.php';
require_once '../includes/EmailManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['to', 'subject', 'content', 'email_settings_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $emailManager = new EmailManager($pdo);
    
    // Add tracking pixel if HTML content
    if (strpos($data['content'], '</body>') !== false) {
        $trackingId = uniqid('track_', true);
        $trackingPixel = '<img src="' . SITE_URL . '/api/track_email.php?id=' . $trackingId . '" width="1" height="1" />';
        $data['content'] = str_replace('</body>', $trackingPixel . '</body>', $data['content']);
        $data['tracking_id'] = $trackingId;
    }
    
    // Queue the email
    $queueId = $emailManager->queueEmail($data);
    
    echo json_encode([
        'success' => true,
        'queue_id' => $queueId,
        'message' => 'Email queued successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
