<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $taskManager = getCRMInstance('TaskManagement');
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'customer_id' => $_POST['customer_id'] ?: null,
        'quote_id' => $_POST['quote_id'] ?? null,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date']
    ];

    if ($taskManager->createTask($data)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to create task');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
