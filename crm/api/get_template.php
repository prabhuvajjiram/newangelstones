<?php
require_once '../includes/config.php';
require_once '../includes/EmailManager.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Template ID is required');
    }
    
    $emailManager = new EmailManager($pdo);
    $template = $emailManager->getEmailTemplate($_GET['id']);
    
    if (!$template) {
        throw new Exception('Template not found');
    }
    
    echo json_encode([
        'success' => true,
        'template' => $template
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
