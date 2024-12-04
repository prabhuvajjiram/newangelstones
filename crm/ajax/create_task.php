<?php
require_once '../includes/config.php';
require_once '../session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    // Validate required fields
    $required_fields = ['title', 'priority', 'due_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate due date format
    $due_date = DateTime::createFromFormat('Y-m-d', $_POST['due_date']);
    if (!$due_date) {
        throw new Exception("Invalid due date format");
    }

    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            title, description, customer_id, user_id,
            created_by, priority, due_date, status
        ) VALUES (
            :title, :description, :customer_id, :user_id,
            :created_by, :priority, :due_date, 'pending'
        )
    ");
    
    $params = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
        'user_id' => $_SESSION['user_id'],
        'created_by' => $_SESSION['user_id'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date']
    ];
    
    $result = $stmt->execute($params);

    if ($result) {
        $task_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'task_id' => $task_id,
            'message' => 'Task created successfully'
        ]);
    } else {
        throw new Exception('Failed to create task: ' . implode(', ', $stmt->errorInfo()));
    }
} catch (Exception $e) {
    error_log("Task creation error: " . $e->getMessage() . "\nPOST data: " . print_r($_POST, true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create task: ' . $e->getMessage()
    ]);
}
