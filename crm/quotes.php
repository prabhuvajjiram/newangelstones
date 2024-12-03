<?php
require_once 'session_check.php';
requireLogin();

require_once 'includes/config.php';

try {
    $stmt = $pdo->query("
        SELECT q.*, c.name as customer_name, c.email as customer_email,
        COUNT(qi.id) as item_count,
        SUM(qi.total_price) as total_amount
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
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
    <title>All Quotes - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Quotes</h2>
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
                                <th>Project</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><?php echo $quote['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($quote['customer_name']); ?><br>
                                    <?php if (!empty($quote['customer_email'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($quote['customer_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>N/A</td>
                                <td><?php echo $quote['item_count']; ?></td>
                                <td>$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo isset($quote['status']) ? 
                                            ($quote['status'] === 'accepted' ? 'success' : 
                                            ($quote['status'] === 'sent' ? 'primary' : 
                                            ($quote['status'] === 'rejected' ? 'danger' : 'warning'))) 
                                            : 'secondary';
                                    ?>">
                                        <?php echo isset($quote['status']) ? ucfirst($quote['status']) : 'Draft'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-success" onclick="sendQuote(<?php echo $quote['id']; ?>)">
                                            <i class="bi bi-envelope"></i>
                                        </button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
