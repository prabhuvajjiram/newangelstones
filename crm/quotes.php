<?php
require_once 'session_check.php';
requireLogin();

require_once 'includes/config.php';

try {
    // Base query
    $baseQuery = "
        SELECT q.*, c.name as customer_name, c.email as customer_email,
        COUNT(qi.id) as item_count,
        SUM(qi.total_price) as total_amount,
        u.username as created_by_name
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        LEFT JOIN users u ON q.created_by = u.id
    ";

    // Add WHERE clause based on user role
    if (!isAdmin()) {
        // Regular users can only see their own quotes
        $baseQuery .= " WHERE q.created_by = :user_id";
    }

    // Complete the query with GROUP BY and ORDER BY
    $baseQuery .= " GROUP BY q.id ORDER BY q.created_at DESC";

    $stmt = $pdo->prepare($baseQuery);
    
    // Bind parameters if not admin
    if (!isAdmin()) {
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }

    $stmt->execute();
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching quotes: " . $e->getMessage());
    $quotes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quotes</h2>
            <a href="quote.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Quote
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <?php if (isAdmin()): ?>
                                <th>Created By</th>
                                <?php endif; ?>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                <td>
                                    <?php if ($quote['customer_id']): ?>
                                        <a href="view_customer.php?id=<?php echo $quote['customer_id']; ?>">
                                            <?php echo htmlspecialchars($quote['customer_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($quote['customer_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $quote['item_count']; ?></td>
                                <td>$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                <?php if (isAdmin()): ?>
                                <td><?php echo htmlspecialchars($quote['created_by_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('M j, Y', strtotime($quote['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $quote['status'] === 'draft' ? 'warning' : 'success'; ?>">
                                        <?php echo ucfirst($quote['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="generate_pdf.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-secondary">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <?php if ($quote['status'] === 'draft'): ?>
                                        <a href="quote.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function sendQuote(quoteId) {
            if (confirm('Are you sure you want to send this quote to the customer?')) {
                $.post('api/send_quote.php', { quote_id: quoteId }, function(response) {
                    if (response.success) {
                        alert('Quote sent successfully!');
                        location.reload();
                    } else {
                        alert('Error sending quote: ' + response.error);
                    }
                });
            }
        }
    </script>
</body>
</html>
