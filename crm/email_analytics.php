<?php
require_once 'includes/config.php';
require_once 'includes/EmailManager.php';
require_once 'session_check.php';  

// Check if user has admin access
if (!isAdmin()) {
    header('Location: ' . getUrl('login.php'));
    exit;
}

$emailManager = new EmailManager($pdo);
$performanceStats = $emailManager->getEmailPerformanceStats();
$activityStats = $emailManager->getEmailActivityStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Analytics - Angel Stones CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Email Analytics</h1>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Total Emails Sent (30 days)</h6>
                        <h2 class="mb-0"><?= number_format($performanceStats['total_sent']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Open Rate</h6>
                        <h2 class="mb-0"><?= number_format($performanceStats['open_rate'], 1) ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Click Rate</h6>
                        <h2 class="mb-0"><?= number_format($performanceStats['click_rate'], 1) ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Avg. Open Time</h6>
                        <h2 class="mb-0"><?= round($performanceStats['avg_open_time'] / 60, 1) ?> hrs</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4">
            <!-- Email Activity Chart -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Email Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="emailActivityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Performance Chart -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Email Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="emailPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Recent Email Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Type</th>
                                        <th>Subject</th>
                                        <th>Recipient</th>
                                        <th>Status</th>
                                        <th>Opens</th>
                                        <th>Clicks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emailManager->getRecentActivity(20) as $activity): ?>
                                    <tr>
                                        <td><?= date('M j, Y g:i A', strtotime($activity['sent_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $activity['direction'] === 'sent' ? 'primary' : 'success' ?>">
                                                <?= ucfirst($activity['direction']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($activity['subject']) ?></td>
                                        <td><?= htmlspecialchars($activity['contact']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $activity['status'] === 'sent' ? 'success' : 'info' ?>">
                                                <?= ucfirst($activity['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $activity['open_count'] ?></td>
                                        <td><?= $activity['click_count'] ?></td>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Email Activity Chart
        const activityCtx = document.getElementById('emailActivityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($activityStats, 'date')) ?>,
                datasets: [{
                    label: 'Sent',
                    data: <?= json_encode(array_column($activityStats, 'sent_count')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Received',
                    data: <?= json_encode(array_column($activityStats, 'received_count')) ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }, {
                    label: 'Opened',
                    data: <?= json_encode(array_column($activityStats, 'opened_count')) ?>,
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Email Activity (Last 30 Days)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Email Performance Chart
        const performanceCtx = document.getElementById('emailPerformanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Opened', 'Clicked', 'No Action'],
                datasets: [{
                    data: [
                        <?= $performanceStats['open_rate'] ?>,
                        <?= $performanceStats['click_rate'] ?>,
                        <?= 100 - $performanceStats['open_rate'] ?>
                    ],
                    backgroundColor: [
                        'rgb(75, 192, 192)',
                        'rgb(54, 162, 235)',
                        'rgb(201, 203, 207)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
