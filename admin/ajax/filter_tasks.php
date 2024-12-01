<?php
require_once '../includes/config.php';
require_once '../includes/crm_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $taskManager = getCRMInstance('TaskManagement');
    
    // Build filter conditions
    $filters = [];
    if (!empty($_POST['status'])) {
        $filters['status'] = $_POST['status'];
    }
    if (!empty($_POST['priority'])) {
        $filters['priority'] = $_POST['priority'];
    }
    if (!empty($_POST['daterange'])) {
        $dates = explode(' - ', $_POST['daterange']);
        if (count($dates) === 2) {
            $filters['start_date'] = date('Y-m-d', strtotime($dates[0]));
            $filters['end_date'] = date('Y-m-d', strtotime($dates[1]));
        }
    }

    // Get filtered tasks
    $myTasks = $taskManager->getTasksByUser($_SESSION['user_id'], $filters);
    $assignedTasks = $taskManager->getTasksAssignedBy($_SESSION['user_id'], $filters);

    // Prepare HTML for my tasks
    $myTasksHtml = '';
    foreach ($myTasks as $task) {
        $myTasksHtml .= '
        <tr>
            <td>' . htmlspecialchars($task['title']) . '</td>
            <td>' . ($task['customer_id'] ? '<a href="view_customer.php?id=' . $task['customer_id'] . '">' . htmlspecialchars($task['customer_name']) . '</a>' : '') . '</td>
            <td><span class="badge bg-' . getPriorityClass($task['priority']) . '">' . ucfirst($task['priority']) . '</span></td>
            <td>' . date('M d, Y', strtotime($task['due_date'])) . '</td>
            <td>
                <select class="form-select form-select-sm task-status" data-task-id="' . $task['id'] . '">
                    <option value="pending"' . ($task['status'] == 'pending' ? ' selected' : '') . '>Pending</option>
                    <option value="in_progress"' . ($task['status'] == 'in_progress' ? ' selected' : '') . '>In Progress</option>
                    <option value="completed"' . ($task['status'] == 'completed' ? ' selected' : '') . '>Completed</option>
                </select>
            </td>
            <td>
                <button class="btn btn-sm btn-info view-task" data-task-id="' . $task['id'] . '"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-danger delete-task" data-task-id="' . $task['id'] . '"><i class="bi bi-trash"></i></button>
            </td>
        </tr>';
    }

    // Prepare HTML for assigned tasks
    $assignedTasksHtml = '';
    foreach ($assignedTasks as $task) {
        $assignedTasksHtml .= '
        <tr>
            <td>' . htmlspecialchars($task['title']) . '</td>
            <td>' . htmlspecialchars($task['assigned_to_name']) . '</td>
            <td>' . ($task['customer_id'] ? '<a href="view_customer.php?id=' . $task['customer_id'] . '">' . htmlspecialchars($task['customer_name']) . '</a>' : '') . '</td>
            <td><span class="badge bg-' . getPriorityClass($task['priority']) . '">' . ucfirst($task['priority']) . '</span></td>
            <td>' . date('M d, Y', strtotime($task['due_date'])) . '</td>
            <td><span class="badge bg-' . getStatusClass($task['status']) . '">' . ucfirst($task['status']) . '</span></td>
            <td>
                <button class="btn btn-sm btn-info view-task" data-task-id="' . $task['id'] . '"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-danger delete-task" data-task-id="' . $task['id'] . '"><i class="bi bi-trash"></i></button>
            </td>
        </tr>';
    }

    echo json_encode([
        'success' => true,
        'myTasks' => $myTasksHtml,
        'assignedTasks' => $assignedTasksHtml
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
