<?php
require_once 'session_check.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

try {
    // Test database connection first
    $pdo->query("SELECT 1");

    // Initialize statistics
    $total_quotes = 0;
    $total_amount = 0;
    $pending_followups = 0;
    $approved_amount = 0;

    // Get quotes statistics based on user role
    $query = "
        SELECT 
            COUNT(*) as total_quotes,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_followups,
            COALESCE(SUM(
                (SELECT COALESCE(SUM(qi.total_price), 0) 
                FROM quote_items qi 
                WHERE qi.quote_id = q.id)
            ), 0) as total_amount,
            COALESCE(SUM(
                CASE WHEN status = 'approved' THEN 
                    (SELECT COALESCE(SUM(qi.total_price), 0) 
                    FROM quote_items qi 
                    WHERE qi.quote_id = q.id)
                ELSE 0 END
            ), 0) as approved_amount
        FROM quotes q
        WHERE 1=1
    ";

    // Staff can only see their own quotes or unassigned quotes
    if (!isAdmin()) {
        $query .= " AND (q.created_by = :user_id OR q.created_by IS NULL)";
    }

    $stmt = $pdo->prepare($query);
    if (!isAdmin()) {
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats === false) {
        throw new PDOException("Failed to fetch statistics");
    }

    // Get recent activities
    $recent_activities_query = "
        SELECT 
            q.*,
            c.name as customer_name,
            u.first_name as created_by_first_name,
            u.last_name as created_by_last_name,
            (SELECT COALESCE(SUM(qi.total_price), 0) 
             FROM quote_items qi 
             WHERE qi.quote_id = q.id) as total_amount
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN users u ON q.created_by = u.id
        WHERE 1=1
    ";

    // Staff can see their own quotes and unassigned quotes
    if (!isAdmin()) {
        $recent_activities_query .= " AND (q.created_by = :user_id OR q.created_by IS NULL)";
    }

    $recent_activities_query .= " ORDER BY q.created_at DESC LIMIT 5";

    $stmt = $pdo->prepare($recent_activities_query);
    if (!isAdmin()) {
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Get pending follow-ups from recent activities
    $followup_quotes = array_values(array_filter($recent_activities ?: [], function($quote) {
        return isset($quote['status']) && $quote['status'] === 'pending';
    }));

    // Get top customers
    $top_customers_query = "
        SELECT 
            c.*,
            COUNT(DISTINCT q.id) as quote_count,
            COALESCE(SUM(
                (SELECT COALESCE(SUM(qi.total_price), 0) 
                FROM quote_items qi 
                WHERE qi.quote_id = q.id)
            ), 0) as total_amount
        FROM customers c
        LEFT JOIN quotes q ON c.id = q.customer_id
        WHERE 1=1
    ";

    // Staff can see customers with their quotes or unassigned quotes
    if (!isAdmin()) {
        $top_customers_query .= " AND (q.created_by = :user_id OR q.created_by IS NULL)";
    }

    $top_customers_query .= " 
        GROUP BY c.id
        ORDER BY total_amount DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($top_customers_query);
    if (!isAdmin()) {
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    // Log detailed error information
    error_log("Database error in index.php: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("File: " . $e->getFile() . " on line " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    
    // Set user-friendly error message
    $error = "An error occurred while fetching dashboard data. Please check database connection.";
    
    // Initialize empty data structures
    $stats = [
        'total_quotes' => 0,
        'pending_followups' => 0,
        'total_amount' => 0,
        'approved_amount' => 0
    ];
    $recent_activities = [];
    $followup_quotes = [];
    $top_customers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Angel Stones</title>
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
        <h1 class="mb-4">Dashboard</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Generated</h5>
                        <h3 class="card-text"><?php echo number_format($stats['total_quotes'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Need Follow-up</h5>
                        <h3 class="card-text"><?php echo number_format($stats['pending_followups'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">From Approved Quotes</h5>
                        <h3 class="card-text">$<?php echo number_format($stats['approved_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Amount</h5>
                        <h3 class="card-text">$<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <p class="text-muted">No recent activities.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <a href="preview_quote.php?id=<?php echo $activity['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Quote #<?php echo htmlspecialchars($activity['id']); ?> - <?php echo htmlspecialchars($activity['customer_name']); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">Status: <?php echo ucfirst($activity['status']); ?></p>
                                        <small>Created by: <?php echo htmlspecialchars($activity['created_by_first_name'] . ' ' . $activity['created_by_last_name']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Follow-ups Needed -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Follow-ups Needed</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($followup_quotes)): ?>
                            <p class="text-muted">No pending follow-ups at this time.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($followup_quotes as $quote): ?>
                                    <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($quote['customer_name']); ?></h6>
                                            <small class="text-muted"><?php echo date('M j', strtotime($quote['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">Quote #<?php echo htmlspecialchars($quote['id']); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Top Customers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_customers)): ?>
                            <p class="text-muted">No customer data available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Quotes</th>
                                            <th>Total Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_customers as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                <td><?php echo number_format($customer['quote_count']); ?></td>
                                                <td>$<?php echo number_format($customer['total_amount'], 2); ?></td>
                                                <td>
                                                    <a href="view_customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i> View
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
