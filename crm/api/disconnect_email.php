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
        throw new Exception('Email settings ID is required');
    }
    
    $emailManager = new EmailManager($pdo);
    $emailManager->disconnectEmailAccount($data['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email account disconnected successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
