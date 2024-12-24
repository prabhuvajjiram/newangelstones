<?php
require_once 'includes/config.php';
require_once 'session_check.php';
require_once 'includes/crm_functions.php';
require_once 'includes/ContactManager.php';

// Get customer ID from URL
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize ContactManager
$contactManager = new ContactManager($pdo);

// Get customer details with company info
$stmt = $pdo->prepare("
    SELECT c.*, ls.source_name as lead_source, camp.name as campaign_name,
           lcs.name as lifecycle_stage_name, lcs.id as lifecycle_stage_id,
           comp.name as company_name, comp.id as company_id,
           comp.industry, comp.employee_count, comp.annual_revenue
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
//$stmt = $pdo->prepare("SELECT * FROM quotes WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
//$stmt->execute([$customerId]);
//$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer's quotes with role-based filtering
$quoteQuery = "SELECT * FROM quotes WHERE customer_id = ?";
$queryParams = [$customerId];

// Add user filtering for staff role
if ($_SESSION['role'] === 'staff') {
    $quoteQuery .= " AND username = ?";
    $queryParams[] = $_SESSION['username']; // Using email/username from session
}

$quoteQuery .= " ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->prepare($quoteQuery);
$stmt->execute($queryParams);
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total quotes count
$countQuery = "SELECT COUNT(*) FROM quotes WHERE customer_id = ?";
$countParams = [$customerId];

if ($_SESSION['role'] === 'staff') {
    $countQuery .= " AND username = ?";
    $countParams[] = $_SESSION['username'];
}

$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($countParams);
$totalQuotes = $stmtCount->fetchColumn();

// Get customer's tasks
$stmt = $pdo->prepare("
    SELECT t.*, u.username 
    FROM tasks t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.customer_id = ? AND t.status != 'completed' 
    ORDER BY t.due_date ASC
");
$stmt->execute([$customerId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent communications
$stmt = $pdo->prepare("
    SELECT cc.*, u.username
    FROM customer_communications cc
    JOIN users u ON cc.user_id = u.id
    WHERE cc.customer_id = ?
    ORDER BY cc.created_at DESC
    LIMIT 5
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
    LIMIT 5
");
$stmt->execute([$customerId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate customer statistics
//$totalQuotes = count($quotes);
$totalTasks = count($tasks);
$totalCommunications = count($communications);

// Get the initials for the avatar
$initials = getInitials($customer['name']);

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

function getLeadScoreClass($score) {
    if ($score >= 70) return 'high';
    if ($score >= 40) return 'medium';
    return 'low';
}

function safeEscape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - <?= safeEscape($customer['name']) ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/customer-view.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <div class="customer-avatar">
                    <?= $initials ?>
                </div>
                <div>
                    <h1 class="h3 mb-1"><?= safeEscape($customer['name']) ?></h1>
                    <div class="d-flex align-items-center gap-3">
                        <span class="lifecycle-stage"><?= safeEscape($customer['lifecycle_stage_name']) ?></span>
                        <span class="lead-score <?= getLeadScoreClass($leadScore) ?>">
                            <i class="bi bi-graph-up"></i> <?= $leadScore ?> points
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <a href="quotes.php?customer_id=<?= $customer['id'] ?>" 
                   class="text-decoration-none"
                   data-bs-toggle="tooltip"
                   title="View all quotes for this customer">
                    <div class="card stat-card quotes h-100">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Active Quotes by you</h6>
                            <h2 class="card-title mb-0"><?= count($quotes) ?></h2>
                            <small class="text-muted">
                                <i class="bi bi-arrow-right"></i> View all quotes
                            </small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="tasks.php?customer_id=<?= $customer['id'] ?>&status=open" class="text-decoration-none">
                    <div class="card stat-card tasks h-100">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Open Tasks</h6>
                            <h2 class="card-title mb-0"><?= count($tasks) ?></h2>
                            <div class="mt-2">
                                <i class="bi bi-arrow-right"></i> View all tasks
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#activityTimeline" class="text-decoration-none" data-filter="communication">
                    <div class="card stat-card communications h-100">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Communications</h6>
                            <h2 class="card-title mb-0"><?= count($communications) ?></h2>
                            <div class="mt-2">
                                <i class="bi bi-arrow-right"></i> View all communications
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-4">
                <!-- Customer Information -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Customer Information</h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Email</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <a href="mailto:<?= safeEscape($customer['email']) ?>"><?= safeEscape($customer['email']) ?></a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Phone</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone me-2"></i>
                                <a href="tel:<?= safeEscape($customer['phone']) ?>"><?= safeEscape($customer['phone']) ?></a>
                            </div>
                        </div>
                        <?php if ($customer['company_name']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Company</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building me-2"></i>
                                <?= safeEscape($customer['company_name']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="mb-3">
                            <small class="text-muted d-block">Lead Source</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-funnel me-2"></i>
                                <?= safeEscape($customer['lead_source'] ?? 'Not specified') ?>
                            </div>
                        </div>
                        <?php if ($customer['campaign_name']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Last Campaign</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-bullseye me-2"></i>
                                <?= safeEscape($customer['campaign_name']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <select class="form-select" name="status" id="status">
                                <option value="">Select Status</option>
                                <option value="active" <?= ($customer['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($customer['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                <option value="lead" <?= ($customer['status'] === 'lead') ? 'selected' : '' ?>>Lead</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Fields -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($customFields as $field): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block"><?= safeEscape($field['display_name']) ?></small>
                            <div><?= safeEscape($field['field_value']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-8">
                <!-- Activity Timeline -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Activity Timeline</h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">All Activities</a></li>
                                <li><a class="dropdown-item" href="#">Tasks</a></li>
                                <li><a class="dropdown-item" href="#">Communications</a></li>
                                <li><a class="dropdown-item" href="#">Documents</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php foreach ($activityTimeline as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="bi <?= getActivityIcon($activity['activity_type']) ?>"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= safeEscape($activity['title']) ?></strong>
                                        <small class="text-muted">
                                            <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-0"><?= safeEscape($activity['activity_description']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tasks -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Tasks</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="bi bi-plus-lg"></i> New Task
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <p class="text-muted text-center my-4">No active tasks</p>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                            <div class="card task-card mb-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title mb-1"><?= safeEscape($task['title']) ?></h6>
                                            <p class="card-text small text-muted mb-0">
                                                Due: <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                            </p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#"><i class="bi bi-check2"></i> Mark Complete</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="bi bi-pencil"></i> Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="quick-actions">
        <button class="btn btn-primary quick-action-btn" data-bs-toggle="modal" data-bs-target="#newTaskModal" title="New Task">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button class="btn btn-success quick-action-btn" data-bs-toggle="modal" data-bs-target="#communicationModal" title="Log Communication">
            <i class="bi bi-chat-dots"></i>
        </button>
        <button class="btn btn-info quick-action-btn text-white" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal" title="Upload Document">
            <i class="bi bi-file-earmark-arrow-up"></i>
        </button>
    </div>

    <!-- Include Modals -->
    <?php 
    // Check if modal files exist before including
    $modalPath = __DIR__ . '/modals/';
    $modalFiles = [
        'task_modal.php',
        'communication_modal.php',
        'document_modal.php',
        'edit_customer_modal.php'
    ];
    
    foreach ($modalFiles as $modalFile) {
        if (file_exists($modalPath . $modalFile)) {
            include $modalPath . $modalFile;
        } else {
            error_log("Modal file not found: " . $modalPath . $modalFile);
        }
    }
    ?>

    <!-- Status Update Modal -->
    <div class="modal fade" id="updateStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">Select Status</option>
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="proposal">Proposal</option>
                                <option value="negotiation">Negotiation</option>
                                <option value="closed_won">Closed Won</option>
                                <option value="closed_lost">Closed Lost</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatus">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTaskForm">
                        <div class="form-group mb-3">
                            <label for="taskType">Task Type</label>
                            <select class="form-select" name="taskType" id="taskType">
                                <option value="">Select Type</option>
                                <option value="call">Call</option>
                                <option value="meeting">Meeting</option>
                                <option value="email">Email</option>
                                <option value="follow_up">Follow Up</option>
                            </select>
                        </div>
                        <!-- Other form fields -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTask">Save Task</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/customer-view.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
    <style>
        .form-select {
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .form-select option {
            padding: 0.5rem;
            font-weight: normal;
        }
    </style>
    <script>
        $(document).ready(function() {
            $('.form-select').on('change', function() {
                // Handle select change events
            });
        });
    </script>
</body>
</html>

<?php
function getActivityIcon($type) {
    $icons = [
        'task' => 'bi-check2-square',
        'communication' => 'bi-chat-dots',
        'document' => 'bi-file-earmark',
        'quote' => 'bi-file-text',
        'note' => 'bi-sticky',
        'email' => 'bi-envelope',
        'call' => 'bi-telephone',
        'meeting' => 'bi-calendar-event',
        'default' => 'bi-circle'
    ];
    return $icons[$type] ?? $icons['default'];
}
