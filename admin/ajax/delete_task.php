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
    
    // Check if user has permission to delete this task
    $task = $taskManager->getTaskDetails($taskId);
    if (!$task || ($task['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
        throw new Exception('Permission denied');
    }

    if ($taskManager->deleteTask($taskId)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete task');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
