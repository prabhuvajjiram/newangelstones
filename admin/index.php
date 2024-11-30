<?php
require_once 'includes/config.php';
require_once 'session_check.php';

// Get statistics
$stats = [];
try {
    if (!isset($pdo)) {
        throw new Exception("Database connection not established");
    }
    
    $stats['total_customers'] = $pdo->query("SELECT COUNT(*) as count FROM customers")->fetchColumn();
    $stats['total_quotes'] = $pdo->query("SELECT COUNT(*) as count FROM quotes")->fetchColumn();
    $stats['pending_quotes'] = $pdo->query("SELECT COUNT(*) as count FROM quotes WHERE status = 'pending'")->fetchColumn();
    $result = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM quotes WHERE status = 'approved'");
    $stats['total_revenue'] = $result->fetchColumn();

    // Get recent activities (last 30 days)
    $stmt = $pdo->query("
        SELECT q.*, c.name as customer_name, c.phone as customer_phone 
        FROM quotes q 
        JOIN customers c ON q.customer_id = c.id 
        WHERE q.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY q.created_at DESC 
        LIMIT 5
    ");
    $recent_quotes = $stmt->fetchAll();

    // Get top customers
    $stmt = $pdo->query("
        SELECT 
            c.*, 
            COUNT(q.id) as quote_count, 
            COALESCE(SUM(q.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN quotes q ON c.id = q.customer_id
        GROUP BY c.id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $top_customers = $stmt->fetchAll();

    // Get follow-ups needed (pending quotes older than 7 days)
    $stmt = $pdo->query("
        SELECT q.*, c.name as customer_name, c.phone as customer_phone
        FROM quotes q
        JOIN customers c ON q.customer_id = c.id
        WHERE q.status = 'pending' 
        AND q.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY q.created_at ASC
    ");
    $follow_ups = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error in index.php: " . $e->getMessage());
    $error = "An error occurred while fetching dashboard data. Please check the error logs.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angel Stones - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .activity-item {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .follow-up-item {
            border-left: 3px solid #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Customers</h5>
                        <h2><?php echo number_format($stats['total_customers']); ?></h2>
                        <p class="mb-0"><i class="bi bi-people"></i> Active Leads</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Quotes</h5>
                        <h2><?php echo number_format($stats['total_quotes']); ?></h2>
                        <p class="mb-0"><i class="bi bi-file-text"></i> Generated</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pending Quotes</h5>
                        <h2><?php echo number_format($stats['pending_quotes']); ?></h2>
                        <p class="mb-0"><i class="bi bi-clock"></i> Need Follow-up</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2>$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                        <p class="mb-0"><i class="bi bi-graph-up"></i> From Approved Quotes</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_quotes as $quote): ?>
                            <div class="activity-item">
                                <h6 class="mb-1">New Quote for <?php echo htmlspecialchars($quote['customer_name']); ?></h6>
                                <p class="text-muted mb-1">
                                    <small>
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('M j, Y g:i A', strtotime($quote['created_at'])); ?>
                                    </small>
                                </p>
                                <p class="mb-0">
                                    Amount: $<?php echo number_format($quote['total_amount'], 2); ?> | 
                                    Status: <span class="badge bg-<?php echo $quote['status'] === 'pending' ? 'warning' : ($quote['status'] === 'approved' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($quote['status']); ?>
                                    </span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Follow-ups Needed -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-telephone"></i> Follow-ups Needed</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($follow_ups)): ?>
                            <p class="text-muted">No pending follow-ups at this time.</p>
                        <?php else: ?>
                            <?php foreach ($follow_ups as $quote): ?>
                                <div class="activity-item follow-up-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($quote['customer_name']); ?></h6>
                                    <p class="text-muted mb-1">
                                        <small>
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($quote['customer_phone']); ?><br>
                                            Quote created: <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                                        </small>
                                    </p>
                                    <div class="mt-2">
                                        <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View Quote
                                        </a>
                                        <a href="tel:<?php echo $quote['customer_phone']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-telephone"></i> Call
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-star"></i> Top Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Total Quotes</th>
                                <th>Total Spent</th>
                                <th>Last Quote</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </td>
                                    <td><?php echo (int)$customer['quote_count']; ?></td>
                                    <td>$<?php echo number_format((float)$customer['total_spent'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <a href="view_quotes.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-file-text"></i> Quotes
                                        </a>
                                        <a href="quote.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-plus"></i> New Quote
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
