<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/crm_functions.php';
require_once 'session_check.php';

// Debug logging
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Username: " . ($_SESSION['username'] ?? 'not set'));
error_log("User Role: " . ($_SESSION['user_role'] ?? 'not set'));

// Helper functions
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

// Initialize Task Manager
$taskManager = getCRMInstance('TaskManagement');

// Get all tasks for the current user
$userTasks = $taskManager->getTasksByUser($_SESSION['user_id']);

// Get tasks assigned by the current user (if they're a manager)
$assignedTasks = $taskManager->getTasksAssignedBy($_SESSION['user_id']);

// Get all users for task assignment
$stmt = $pdo->query("
    SELECT id, username, role 
    FROM users 
    WHERE active = TRUE OR id IN (
        SELECT DISTINCT user_id FROM tasks
        UNION
        SELECT DISTINCT created_by FROM tasks
    )
    ORDER BY username
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Angel Stones</title>
    <!-- Add required CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Add required JS in correct order -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Task Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                <i class="bi bi-plus-circle"></i> New Task
            </button>
        </div>

        <!-- Task Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="taskFilters" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Due Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="date_from">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100" style="position: relative; z-index: 1;">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- My Tasks -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">My Tasks</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Related To</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userTasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td>
                                    <?php if ($task['customer_id']): ?>
                                        <a href="view_customer.php?id=<?= $task['customer_id'] ?>">
                                            <?= htmlspecialchars($task['customer_name']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getPriorityClass($task['priority']) ?>">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                                <td>
                                    <select class="form-select form-select-sm task-status" 
                                            data-task-id="<?= $task['id'] ?>">
                                        <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="in_progress" <?= $task['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= $task['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info view-task" 
                                            data-task-id="<?= $task['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-task" 
                                            data-task-id="<?= $task['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($assignedTasks)): ?>
        <!-- Tasks I've Assigned -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tasks I've Assigned</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Related To</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedTasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                                <td>
                                    <?php if ($task['customer_id']): ?>
                                        <a href="view_customer.php?id=<?= $task['customer_id'] ?>">
                                            <?= htmlspecialchars($task['customer_name']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getPriorityClass($task['priority']) ?>">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusClass($task['status']) ?>">
                                        <?= ucfirst($task['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info view-task" 
                                            data-task-id="<?= $task['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-task" 
                                            data-task-id="<?= $task['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Task Modal -->
    <?php include 'modals/task_modal.php'; ?>

    <!-- View Task Modal -->
    <div class="modal fade" id="viewTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Task details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/tasks.js"></script>

    <style>
    .sidebar {
        z-index: 1030;
    }
    .card {
        position: relative;
        z-index: 1;
    }
    .btn-primary {
        position: relative;
        z-index: 1;
    }
    </style>
</body>
</html>
