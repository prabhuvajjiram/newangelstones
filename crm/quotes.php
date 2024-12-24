<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';

// Debug session and role
error_log("Debug: Session data in quotes.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));
error_log("Is Admin: " . (isAdmin() ? 'Yes' : 'No'));

// Verify database connection
try {
    $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    error_log("Database connection verified");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

try {
    // Base query with role-based filtering
    $query = "SELECT * FROM quotes WHERE 1=1";
    $params = array();
    
    // If not admin, only show user's own quotes
    if (!isAdmin()) {
        $query .= " AND username = :username";
        $params[':username'] = $_SESSION['email'];
    }
    
    $query .= " ORDER BY created_at DESC";
    
    // Prepare and execute the query with parameters
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Now get related data for all quotes efficiently
    $quoteIds = array_column($quotes, 'id');
    $customerIds = array_unique(array_column($quotes, 'customer_id'));
    $userEmails = array_unique(array_filter(array_column($quotes, 'username')));

    // Get all customers in one query if we have customer IDs
    $customersById = [];
    if (!empty($customerIds)) {
        $customerQuery = "SELECT id, name, email FROM customers WHERE id IN (" . implode(',', array_map('intval', $customerIds)) . ")";
        $customerStmt = $pdo->query($customerQuery);
        $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
        $customersById = array_column($customers, null, 'id');
    }

    // Get all users in one query if we have user emails
    $usersByEmail = [];
    if (!empty($userEmails)) {
        $userQuery = "SELECT email, first_name, last_name FROM users WHERE email IN (" . 
            implode(',', array_map(function($email) use ($pdo) {
                return $pdo->quote($email);
            }, $userEmails)) . ")";
        $userStmt = $pdo->query($userQuery);
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
        $usersByEmail = array_column($users, null, 'email');
    }

    // Get all quote items in one query if we have quote IDs
    $itemsByQuoteId = [];
    if (!empty($quoteIds)) {
        $itemsQuery = "SELECT quote_id, 
                              COUNT(*) as item_count,
                              COALESCE(SUM(total_price), 0) as total_amount,
                              COALESCE(SUM(cubic_feet), 0) as total_cubic_feet
                       FROM quote_items 
                       WHERE quote_id IN (" . implode(',', array_map('intval', $quoteIds)) . ")
                       GROUP BY quote_id";
        $itemsStmt = $pdo->query($itemsQuery);
        $quoteItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        $itemsByQuoteId = array_column($quoteItems, null, 'quote_id');
    }

    // Get all orders in one query if we have quote IDs
    $ordersByQuoteId = [];
    if (!empty($quoteIds)) {
        $ordersQuery = "SELECT quote_id, order_id, order_number FROM orders WHERE quote_id IN (" . implode(',', array_map('intval', $quoteIds)) . ")";
        $ordersStmt = $pdo->query($ordersQuery);
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        $ordersByQuoteId = array_column($orders, null, 'quote_id');
    }

    // Enrich quotes with related data
    foreach ($quotes as &$quote) {
        $quote['customer'] = isset($quote['customer_id']) ? ($customersById[$quote['customer_id']] ?? null) : null;
        $quote['user'] = isset($quote['username']) ? ($usersByEmail[$quote['username']] ?? null) : null;
        $quote['items'] = $itemsByQuoteId[$quote['id']] ?? [
            'item_count' => 0,
            'total_amount' => 0,
            'total_cubic_feet' => 0
        ];
        $quote['order'] = $ordersByQuoteId[$quote['id']] ?? null;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $quotes = [];
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $quotes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Quotes - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>All Quotes</h1>
            <a href="quote.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> New Quote
            </a>
        </div>
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="searchForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customerName" name="customerName" placeholder="Search by customer name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quote Number</label>
                        <input type="text" class="form-control" id="quoteNumber" name="quoteNumber" placeholder="Search by quote number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="dateFrom" name="dateFrom">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="dateTo" name="dateTo">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Customer</th>
                                <th class="text-center">Items</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Cu.Ft</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($quotes)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No quotes found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <td>
                                        <a href="preview_quote.php?id=<?php echo htmlspecialchars($quote['id']); ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($quote['quote_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($quote['customer']): ?>
                                            <?php echo htmlspecialchars($quote['customer']['name']); ?>
                                            <?php if ($quote['customer']['email']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($quote['customer']['email']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No customer assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($quote['items']['item_count']); ?></td>
                                    <td class="text-end">$<?php echo number_format($quote['items']['total_amount'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($quote['items']['total_cubic_feet'], 2); ?></td>
                                    <td>
                                        <?php if ($quote['user']): ?>
                                            <?php echo htmlspecialchars($quote['user']['first_name'] . ' ' . $quote['user']['last_name']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($quote['username']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($quote['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($quote['status']) {
                                                'pending' => 'warning',
                                                'sent' => 'info',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                'Converted' => 'primary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($quote['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="preview_quote.php?id=<?php echo htmlspecialchars($quote['id']); ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Quote">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($quote['order']): ?>
                                                <a href="view_order.php?id=<?php echo htmlspecialchars($quote['order']['order_id']); ?>" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="View Order #<?php echo htmlspecialchars($quote['order']['order_number']); ?>">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (isAdmin() || $_SESSION['email'] === $quote['username']): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger delete-quote" 
                                                        data-quote-id="<?php echo htmlspecialchars($quote['id']); ?>"
                                                        title="Delete Quote">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/utilities.js"></script>
    <script src="js/quotes.js"></script>
</body>
</html>
