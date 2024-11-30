<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $taskManager = getCRMInstance('TaskManagement');
    
    $taskId = $_POST['task_id'];
    $status = $_POST['status'];

    if ($taskManager->updateTaskStatus($taskId, $status)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update task status');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}
