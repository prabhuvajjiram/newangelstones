<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $taskManager = getCRMInstance('TaskManagement');
    $taskId = $_GET['task_id'];
    
    $task = $taskManager->getTaskDetails($taskId);
    
    if (!$task) {
        throw new Exception('Task not found');
    }

    // Output task details in HTML format
    ?>
    <div class="task-details">
        <div class="mb-3">
            <strong>Title:</strong>
            <p><?= htmlspecialchars($task['title']) ?></p>
        </div>
        
        <div class="mb-3">
            <strong>Description:</strong>
            <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
        </div>
        
        <div class="mb-3">
            <strong>Priority:</strong>
            <span class="badge bg-<?= getPriorityClass($task['priority']) ?>">
                <?= ucfirst($task['priority']) ?>
            </span>
        </div>
        
        <div class="mb-3">
            <strong>Status:</strong>
            <span class="badge bg-<?= getStatusClass($task['status']) ?>">
                <?= ucfirst($task['status']) ?>
            </span>
        </div>
        
        <div class="mb-3">
            <strong>Due Date:</strong>
            <p><?= date('F d, Y', strtotime($task['due_date'])) ?></p>
        </div>
        
        <?php if ($task['customer_id']): ?>
        <div class="mb-3">
            <strong>Related Customer:</strong>
            <p>
                <a href="../view_customer.php?id=<?= $task['customer_id'] ?>">
                    <?= htmlspecialchars($task['customer_name']) ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <strong>Assigned By:</strong>
            <p><?= htmlspecialchars($task['assigned_by_name']) ?></p>
        </div>
        
        <div class="mb-3">
            <strong>Assigned To:</strong>
            <p><?= htmlspecialchars($task['assigned_to_name']) ?></p>
        </div>
        
        <div class="mb-3">
            <strong>Created:</strong>
            <p><?= date('F d, Y g:i A', strtotime($task['created_at'])) ?></p>
        </div>
        
        <?php if ($task['updated_at']): ?>
        <div class="mb-3">
            <strong>Last Updated:</strong>
            <p><?= date('F d, Y g:i A', strtotime($task['updated_at'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}

function getPriorityClass($priority) {
    switch($priority) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'success';
        default: return 'secondary';
    }
}

function getStatusClass($status) {
    switch($status) {
        case 'completed': return 'success';
        case 'in_progress': return 'warning';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
}
?>
