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
    
    if (!isset($data['id'])) {
        throw new Exception('Template ID is required');
    }
    
    $emailManager = new EmailManager($pdo);
    $emailManager->deleteEmailTemplate($data['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Template deleted successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
