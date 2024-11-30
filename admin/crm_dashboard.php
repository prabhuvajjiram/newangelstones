<?php
require_once 'includes/config.php';
require_once 'includes/crm_functions.php';

// Initialize CRM classes
$leadManager = getCRMInstance('LeadManagement');
$campaignManager = getCRMInstance('CampaignManagement');
$taskManager = getCRMInstance('TaskManagement');
$documentManager = getCRMInstance('DocumentManagement');
$communicationManager = getCRMInstance('CommunicationManagement');

// Get top leads
$topLeads = $leadManager->getLeadsByScore(50, 5);

// Get user's tasks
$userTasks = $taskManager->getTasksByUser($_SESSION['user_id']);

// Get active campaigns
$stmt = $pdo->query("
    SELECT * FROM campaigns 
    WHERE status IN ('active', 'scheduled') 
    ORDER BY start_date DESC 
    LIMIT 5
");
$activeCampaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">CRM Dashboard</h1>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                                <i class="bi bi-plus-circle"></i> New Task
                            </button>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newCommunicationModal">
                                <i class="bi bi-chat"></i> Log Communication
                            </button>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                <i class="bi bi-file-earmark"></i> Upload Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Leads -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Leads</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($topLeads as $lead): ?>
                            <a href="view_customer.php?id=<?= $lead['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($lead['name']) ?></h6>
                                    <span class="badge bg-primary"><?= $lead['lead_score'] ?></span>
                                </div>
                                <small><?= htmlspecialchars($lead['email']) ?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">My Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userTasks as $task): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['title']) ?></td>
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
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Campaigns -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Active Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeCampaigns as $campaign): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($campaign['name']) ?></td>
                                        <td><?= ucfirst($campaign['type']) ?></td>
                                        <td><?= ucfirst($campaign['status']) ?></td>
                                        <td>
                                            <?php
                                            $progress = calculateCampaignProgress($campaign);
                                            $progressClass = getProgressClass($progress);
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar bg-<?= $progressClass ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $progress ?>%">
                                                    <?= $progress ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Communications -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Communications</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("
                            SELECT cc.*, c.name as customer_name, u.username
                            FROM customer_communications cc
                            JOIN customers c ON cc.customer_id = c.id
                            JOIN users u ON cc.user_id = u.id
                            ORDER BY cc.created_at DESC
                            LIMIT 5
                        ");
                        $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="list-group">
                            <?php foreach ($communications as $comm): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?= htmlspecialchars($comm['customer_name']) ?> - 
                                        <?= htmlspecialchars($comm['subject']) ?>
                                    </h6>
                                    <small><?= getTimeAgo($comm['created_at']) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($comm['content']) ?></p>
                                <small>By <?= htmlspecialchars($comm['username']) ?> via <?= ucfirst($comm['type']) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'modals/task_modal.php'; ?>
    <?php include 'modals/communication_modal.php'; ?>
    <?php include 'modals/document_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Helper functions
        function getPriorityClass(priority) {
            switch(priority) {
                case 'urgent': return 'danger';
                case 'high': return 'warning';
                case 'medium': return 'info';
                default: return 'secondary';
            }
        }

        function calculateCampaignProgress(campaign) {
            const start = new Date(campaign.start_date);
            const end = new Date(campaign.end_date);
            const now = new Date();
            
            if (now < start) return 0;
            if (now > end) return 100;
            
            const total = end - start;
            const current = now - start;
            return Math.round((current / total) * 100);
        }

        function getProgressClass(progress) {
            if (progress < 25) return 'info';
            if (progress < 50) return 'primary';
            if (progress < 75) return 'warning';
            return 'success';
        }

        function getTimeAgo(timestamp) {
            const now = new Date();
            const then = new Date(timestamp);
            const diff = Math.floor((now - then) / 1000); // seconds

            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            return Math.floor(diff / 86400) + ' days ago';
        }

        // Event handlers
        $(document).ready(function() {
            // Task status update
            $('.task-status').change(function() {
                const taskId = $(this).data('task-id');
                const status = $(this).val();
                
                $.post('ajax/update_task_status.php', {
                    task_id: taskId,
                    status: status
                }).done(function(response) {
                    // Handle response
                });
            });
        });
    </script>
</body>
</html><?php

// Helper functions
function getPriorityClass($priority) {
    switch($priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        default: return 'secondary';
    }
}

function calculateCampaignProgress($campaign) {
    $start = strtotime($campaign['start_date']);
    $end = strtotime($campaign['end_date']);
    $now = time();
    
    if ($now < $start) return 0;
    if ($now > $end) return 100;
    
    $total = $end - $start;
    $current = $now - $start;
    return round(($current / $total) * 100);
}

function getProgressClass($progress) {
    if ($progress < 25) return 'info';
    if ($progress < 50) return 'primary';
    if ($progress < 75) return 'warning';
    return 'success';
}

function getTimeAgo($timestamp) {
    $now = time();
    $then = strtotime($timestamp);
    $diff = $now - $then;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}
?>
