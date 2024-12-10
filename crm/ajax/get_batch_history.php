<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Query to get batch operations history
    $query = "SELECT 
        bo.id,
        bo.operation_type,
        bo.status,
        u.username as created_by,
        bo.created_at,
        bo.completed_at
    FROM batch_operations bo
    LEFT JOIN users u ON bo.created_by = u.id
    ORDER BY bo.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($operations);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
