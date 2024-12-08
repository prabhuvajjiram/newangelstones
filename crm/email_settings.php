<?php
require_once 'includes/config.php';
require_once 'session_check.php';
require_once 'includes/EmailManager.php';

$emailManager = new EmailManager($pdo);
$emailSettings = $emailManager->getEmailSettings($_SESSION['user_id']);
$emailTemplates = $emailManager->getEmailTemplates();

// Get email statistics
$stats = $emailManager->getEmailStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - Angel Stones CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Email Settings -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Email Integration</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#connectEmailModal">
                            <i class="bi bi-plus-lg"></i> Connect Email
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($emailSettings)): ?>
                            <p class="text-muted">No email accounts connected. Click "Connect Email" to get started.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Email Address</th>
                                            <th>Provider</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($emailSettings as $setting): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($setting['email_address']) ?></td>
                                            <td><i class="bi bi-<?= $setting['email_provider'] ?>"></i> <?= ucfirst($setting['email_provider']) ?></td>
                                            <td>
                                                <?php if ($setting['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="disconnectEmail(<?= $setting['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email Templates -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Email Templates</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                            <i class="bi bi-plus-lg"></i> New Template
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emailTemplates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td><?= htmlspecialchars($template['category']) ?></td>
                                        <td><?= date('M j, Y', strtotime($template['updated_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editTemplate(<?= $template['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?= $template['id'] ?>)">
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
            </div>

            <!-- Email Analytics -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Email Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Emails Sent (Last 30 Days)</h6>
                                    <h3><?= number_format($stats['sent_30_days']) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Average Response Time</h6>
                                    <h3><?= $stats['avg_response_time'] ?> hrs</h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Open Rate</h6>
                                    <h3><?= number_format($stats['open_rate'], 1) ?>%</h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Response Rate</h6>
                                    <h3><?= number_format($stats['response_rate'], 1) ?>%</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Email Activity Chart -->
                        <div class="mt-4">
                            <canvas id="emailActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Email Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Email Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($emailManager->getRecentActivity() as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon bg-<?= $activity['type'] === 'sent' ? 'primary' : 'success' ?>">
                                    <i class="bi bi-envelope<?= $activity['type'] === 'sent' ? '-arrow-up' : '-arrow-down' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1"><?= htmlspecialchars($activity['subject']) ?></h6>
                                    <p class="mb-0 text-muted">
                                        <?= $activity['type'] === 'sent' ? 'To: ' : 'From: ' ?>
                                        <?= htmlspecialchars($activity['contact']) ?>
                                    </p>
                                    <small class="text-muted"><?= $activity['time_ago'] ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Connect Email Modal -->
    <div class="modal fade" id="connectEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Connect Email Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <a href="gmail_auth.php" class="btn btn-outline-danger">
                            <i class="bi bi-google me-2"></i> Connect Gmail Account
                        </a>
                        <a href="outlook_auth.php" class="btn btn-outline-primary">
                            <i class="bi bi-microsoft me-2"></i> Connect Outlook Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="templateForm">
                    <div class="modal-body">
                        <input type="hidden" name="template_id" id="template_id">
                        <div class="mb-3">
                            <label class="form-label">Template Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="general">General</option>
                                <option value="quote">Quote Follow-up</option>
                                <option value="task">Task Related</option>
                                <option value="welcome">Welcome Email</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" name="content" rows="10" required></textarea>
                            <small class="text-muted">Available variables: {customer_name}, {company_name}, {quote_number}, {task_title}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/email-settings.js"></script>
</body>
</html>
