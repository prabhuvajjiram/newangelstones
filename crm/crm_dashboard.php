<?php
require_once 'includes/config.php';
require_once 'session_check.php';

// Require staff or admin access
requireStaffOrAdmin();

try {
    // Get top leads
    $stmt = $pdo->query("
        SELECT c.*, COUNT(q.id) as quote_count 
        FROM customers c 
        LEFT JOIN quotes q ON c.id = q.customer_id 
        GROUP BY c.id 
        ORDER BY quote_count DESC, c.last_contact_date DESC 
        LIMIT 5
    ");
    $topLeads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user's tasks
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as customer_name 
        FROM tasks t 
        LEFT JOIN customers c ON t.customer_id = c.id 
        WHERE t.user_id = ? AND t.status != 'completed' 
        ORDER BY t.due_date ASC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent communications
    $stmt = $pdo->prepare("
        SELECT cc.*, c.name as customer_name 
        FROM customer_communications cc 
        LEFT JOIN customers c ON cc.customer_id = c.id 
        ORDER BY cc.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentCommunications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error in CRM Dashboard: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    $error = "An error occurred while loading the dashboard. Please try again later. Error: " . $e->getMessage();
}

// Initialize arrays if not set
if (!isset($userTasks)) $userTasks = [];
if (!isset($recentCommunications)) $recentCommunications = [];
if (!isset($topLeads)) $topLeads = [];

// Debug information
error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Top Leads count: " . count($topLeads));
error_log("User Tasks count: " . count($userTasks));
error_log("Communications count: " . count($recentCommunications));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .quick-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .quick-actions .btn {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <!-- Quick Actions Section -->
        <div class="quick-actions">
            <h5 class="mb-3">Quick Actions</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                <i class="bi bi-plus-circle"></i> Create Task
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#communicationModal">
                <i class="bi bi-chat-dots"></i> Log Communication
            </button>
            <a href="customers.php" class="btn btn-info">
                <i class="bi bi-people"></i> View All Customers
            </a>
        </div>

        <div class="row g-4">
            <!-- Top Leads -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Top Leads</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Quotes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topLeads)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No leads found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topLeads as $lead): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($lead['quote_count']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                onclick="createTask(<?php echo $lead['id']; ?>, '<?php echo addslashes($lead['name']); ?>')">
                                                            <i class="bi bi-plus-circle"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                onclick="logCommunication(<?php echo $lead['id']; ?>, '<?php echo addslashes($lead['name']); ?>')">
                                                            <i class="bi bi-chat-dots"></i>
                                                        </button>
                                                        <a href="view_customer.php?id=<?php echo $lead['id']; ?>" 
                                                           class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks and Communications -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">My Tasks</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="bi bi-plus-circle"></i> New Task
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userTasks)): ?>
                            <p class="text-center text-muted">No tasks found. Click "New Task" to create one.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($userTasks as $task): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                            <small class="text-muted">Due: <?php echo date('M j, Y', strtotime($task['due_date'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($task['description']); ?></p>
                                        <?php if ($task['customer_name']): ?>
                                            <small class="text-muted">Customer: <?php echo htmlspecialchars($task['customer_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Communications</h5>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#communicationModal">
                            <i class="bi bi-chat-dots"></i> New Communication
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentCommunications)): ?>
                            <p class="text-center text-muted">No communications found. Click "New Communication" to log one.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recentCommunications as $comm): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($comm['subject']); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($comm['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($comm['content']); ?></p>
                                        <?php if ($comm['customer_name']): ?>
                                            <small class="text-muted">Customer: <?php echo htmlspecialchars($comm['customer_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include 'modals/task_modal.php'; ?>
    <?php include 'modals/communication_modal.php'; ?>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/crm.js"></script>
    <script>
        function createTask(customerId, customerName) {
            document.getElementById('task_customer_id').value = customerId;
            document.getElementById('task_customer_name').value = customerName;
            new bootstrap.Modal(document.getElementById('newTaskModal')).show();
        }

        function logCommunication(customerId, customerName) {
            document.getElementById('communication_customer_id').value = customerId;
            document.getElementById('communication_customer_name').value = customerName;
            new bootstrap.Modal(document.getElementById('communicationModal')).show();
        }
    </script>
</body>
</html>
