<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Ensure user has appropriate permissions
if (!isLoggedIn() || (!isStaff() && !isAdmin())) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Create batch operation record
    $stmt = $pdo->prepare("
        INSERT INTO batch_operations 
        (operation_type, status, created_by) 
        VALUES 
        ('quantity_adjustment', 'pending', ?)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $batchId = $pdo->lastInsertId();

    // Insert batch operation items
    $stmt = $pdo->prepare("
        INSERT INTO batch_operation_items 
        (batch_id, item_type, item_id, warehouse_id, adjustment_type, quantity, reason) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($input['items'] as $itemId) {
        $stmt->execute([
            $batchId,
            $input['type'],
            $itemId,
            $input['warehouse_id'],
            $input['adjustment_type'],
            $input['quantity'],
            $input['reason']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'batch_id' => $batchId]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}