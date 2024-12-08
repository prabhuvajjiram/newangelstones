<?php
require_once 'includes/config.php';
require_once 'session_check.php';
require_once 'includes/crm_functions.php';
require_once 'includes/ContactManager.php';

// Get customer ID from URL
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize ContactManager
$contactManager = new ContactManager($pdo);

// Get customer details
$stmt = $pdo->prepare("
    SELECT c.*, ls.source_name as lead_source, camp.name as campaign_name,
           lcs.name as lifecycle_stage_name, lcs.id as lifecycle_stage_id,
           comp.name as company_name, comp.id as company_id
    FROM customers c
    LEFT JOIN lead_sources ls ON c.lead_source_id = ls.id
    LEFT JOIN campaigns camp ON c.last_campaign_id = camp.id
    LEFT JOIN lifecycle_stages lcs ON c.lifecycle_stage_id = lcs.id
    LEFT JOIN companies comp ON c.company_id = comp.id
    WHERE c.id = ?
");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Get lifecycle stages for dropdown
$lifecycleStages = $contactManager->getLifecycleStages();

// Get custom fields
$customFields = $contactManager->getCustomFieldValues($customerId);

// Get activity timeline
$activityTimeline = $contactManager->getActivityTimeline($customerId);

// Calculate lead score
$leadScore = $contactManager->calculateLeadScore($customerId);

// Get customer's quotes
$stmt = $pdo->prepare("
    SELECT * FROM quotes 
    WHERE customer_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$customerId]);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer's communications
$stmt = $pdo->prepare("
    SELECT cc.*, u.username
    FROM customer_communications cc
    JOIN users u ON cc.user_id = u.id
    WHERE cc.customer_id = ?
    ORDER BY cc.created_at DESC
");
$stmt->execute([$customerId]);
$communications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer's documents
$stmt = $pdo->prepare("
    SELECT cd.*, u.username as uploaded_by_name
    FROM customer_documents cd
    JOIN users u ON cd.uploaded_by = u.id
    WHERE cd.customer_id = ?
    ORDER BY cd.upload_date DESC
");
$stmt->execute([$customerId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer's tasks
$stmt = $pdo->prepare("
    SELECT t.*, u.username
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    WHERE t.customer_id = ?
    ORDER BY t.due_date ASC
");
$stmt->execute([$customerId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - <?= htmlspecialchars($customer['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><?= htmlspecialchars($customer['name']) ?></h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                    <i class="bi bi-plus-circle"></i> New Task
                </button>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newCommunicationModal">
                    <i class="bi bi-chat"></i> Log Communication
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="bi bi-file-earmark"></i> Upload Document
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Customer Information -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Customer Information</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Lifecycle Stage</h6>
                            <select class="form-select" id="lifecycleStage" data-customer-id="<?= $customerId ?>">
                                <?php foreach ($lifecycleStages as $stage): ?>
                                    <option value="<?= $stage['id'] ?>" <?= $stage['id'] == $customer['lifecycle_stage_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($stage['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <h6>Lead Score</h6>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?= min(100, $leadScore) ?>%"
                                     aria-valuenow="<?= $leadScore ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= $leadScore ?> points
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>Company</h6>
                            <p><?= $customer['company_name'] ? htmlspecialchars($customer['company_name']) : 'Not assigned' ?></p>
                        </div>
                        <!-- Basic Info -->
                        <div class="mb-3">
                            <h6>Contact Details</h6>
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($customer['name']) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($customer['phone']) ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($customer['address']) ?></p>
                        </div>
                        <!-- Custom Fields -->
                        <?php if ($customFields): ?>
                            <div class="mb-3">
                                <h6>Additional Information</h6>
                                <?php 
                                $currentGroup = '';
                                foreach ($customFields as $field): 
                                    if ($currentGroup != $field['field_group']): 
                                        $currentGroup = $field['field_group'];
                                ?>
                                    <h6 class="mt-3"><?= htmlspecialchars($currentGroup) ?></h6>
                                <?php endif; ?>
                                    <p class="mb-1">
                                        <strong><?= htmlspecialchars($field['display_name']) ?>:</strong>
                                        <?= htmlspecialchars($field['field_value'] ?? 'Not set') ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Activity Timeline -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Activity Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($activityTimeline as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <i class="bi bi-<?= getActivityIcon($activity['activity_type']) ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0"><?= htmlspecialchars($activity['title']) ?></h6>
                                        <p class="text-muted mb-0">
                                            <?= date('M j, Y g:i A', strtotime($activity['activity_date'])) ?>
                                            <?php if ($activity['performed_by_name']): ?>
                                                by <?= htmlspecialchars($activity['performed_by_name']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($activity['description']): ?>
                                            <p class="mb-0"><?= htmlspecialchars($activity['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Tasks -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tasks</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
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
                                        <td><?= htmlspecialchars($task['username']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['file_name']) ?></td>
                                        <td><?= ucfirst($doc['document_type']) ?></td>
                                        <td><?= htmlspecialchars($doc['uploaded_by_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($doc['upload_date'])) ?></td>
                                        <td>
                                            <a href="download_document.php?id=<?= $doc['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
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

        <!-- Quotes -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quotes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Quote Number</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Valid Until</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quote['quote_number']) ?></td>
                                        <td>$<?= number_format($quote['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getQuoteStatusClass($quote['status']) ?>">
                                                <?= ucfirst($quote['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($quote['created_at'])) ?></td>
                                        <td><?= $quote['valid_until'] ? date('M d, Y', strtotime($quote['valid_until'])) : 'N/A' ?></td>
                                        <td>
                                            <a href="preview_quote.php?id=<?= $quote['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
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
    </div>

    <!-- Include the modals -->
    <?php include 'modals/task_modal.php'; ?>
    <?php include 'modals/communication_modal.php'; ?>
    <?php include 'modals/document_modal.php'; ?>

    <script>
        // Pre-fill customer ID in modals
        $(document).ready(function() {
            const customerId = <?= $customerId ?>;
            $('select[name="customer_id"]').val(customerId);
            
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

        // Helper functions
        function getLeadScoreClass(score) {
            if (score >= 80) return 'success';
            if (score >= 50) return 'warning';
            return 'danger';
        }

        function getStatusClass(status) {
            switch(status) {
                case 'active': return 'success';
                case 'potential': return 'info';
                case 'converted': return 'primary';
                default: return 'secondary';
            }
        }

        function getQuoteStatusClass(status) {
            switch(status) {
                case 'approved': return 'success';
                case 'pending': return 'warning';
                case 'rejected': return 'danger';
                default: return 'secondary';
            }
        }
    </script>
</body>
</html>
<?php

// Helper function for activity icons
function getActivityIcon($type) {
    switch ($type) {
        case 'email': return 'envelope';
        case 'call': return 'telephone';
        case 'meeting': return 'calendar-event';
        case 'note': return 'sticky';
        case 'task': return 'check2-square';
        case 'quote': return 'file-text';
        case 'website_visit': return 'globe';
        default: return 'circle';
    }
}
