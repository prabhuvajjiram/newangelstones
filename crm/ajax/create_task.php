<?php
require_once '../includes/config.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            title, description, customer_id, user_id,
            created_by, priority, due_date, status
        ) VALUES (
            :title, :description, :customer_id, :user_id,
            :created_by, :priority, :due_date, 'pending'
        )
    ");
    
    $result = $stmt->execute([
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
        'user_id' => $_SESSION['user_id'],
        'created_by' => $_SESSION['user_id'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date']
    ]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to create task');
    }
} catch (Exception $e) {
    error_log("Task creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create task']);
}
