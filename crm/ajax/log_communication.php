<?php
require_once '../includes/config.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO customer_communications (
            customer_id, user_id, type, subject, 
            content, status
        ) VALUES (
            :customer_id, :user_id, :type, :subject, 
            :content, 'pending'
        )
    ");
    
    $result = $stmt->execute([
        'customer_id' => $_POST['customer_id'],
        'user_id' => $_SESSION['user_id'],
        'type' => $_POST['type'],
        'subject' => $_POST['subject'],
        'content' => $_POST['content']
    ]);

    if ($result) {
        // Update customer's last_contact_date
        $updateStmt = $pdo->prepare("
            UPDATE customers 
            SET last_contact_date = CURRENT_TIMESTAMP 
            WHERE id = :customer_id
        ");
        $updateStmt->execute(['customer_id' => $_POST['customer_id']]);
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to log communication');
    }
} catch (Exception $e) {
    error_log("Communication logging error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to log communication']);
}
