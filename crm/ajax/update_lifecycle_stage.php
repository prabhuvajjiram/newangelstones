<?php
require_once '../includes/config.php';
require_once '../session_check.php';
require_once '../includes/ContactManager.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['customer_id']) || !isset($data['stage_id'])) {
        throw new Exception('Missing required parameters');
    }
    
    $customerId = (int)$data['customer_id'];
    $stageId = (int)$data['stage_id'];
    
    // Update lifecycle stage
    $contactManager = new ContactManager($pdo);
    $success = $contactManager->updateLifecycleStage($customerId, $stageId);
    
    if ($success) {
        // Log the activity
        $stmt = $pdo->prepare("SELECT name FROM lifecycle_stages WHERE id = ?");
        $stmt->execute([$stageId]);
        $stageName = $stmt->fetchColumn();
        
        $contactManager->logActivity(
            $customerId,
            'custom',
            'Lifecycle Stage Updated',
            "Customer moved to {$stageName} stage",
            $_SESSION['user_id']
        );
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update lifecycle stage');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
