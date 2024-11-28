<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

// Get customer ID from URL
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if (!$customer_id) {
    header('Location: customers.php');
    exit;
}

// Get customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    $_SESSION['error'] = "Customer not found.";
    header('Location: customers.php');
    exit;
}

// Get all quotes for this customer
$stmt = $conn->prepare("
    SELECT q.*, COUNT(qi.id) as item_count 
    FROM quotes q 
    LEFT JOIN quote_items qi ON q.id = qi.quote_id 
    WHERE q.customer_id = ? 
    GROUP BY q.id 
    ORDER BY q.created_at DESC
");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$quotes = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes for <?php echo htmlspecialchars($customer['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Quotes for <?php echo htmlspecialchars($customer['name']); ?></h1>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
                        <?php if (!empty($customer['city']) || !empty($customer['state'])): ?>
                        <p><strong>Location:</strong> 
                            <?php 
                            $location = [];
                            if (!empty($customer['city'])) $location[] = htmlspecialchars($customer['city']);
                            if (!empty($customer['state'])) $location[] = htmlspecialchars($customer['state']);
                            if (!empty($customer['postal_code'])) $location[] = htmlspecialchars($customer['postal_code']);
                            echo implode(', ', $location);
                            ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quote History</h5>
            </div>
            <div class="card-body">
                <?php if ($quotes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Quote Number</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($quote = $quotes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quote['quote_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></td>
                                <td><?php echo intval($quote['item_count']); ?></td>
                                <td>$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $quote['status'] === 'pending' ? 'warning' : 
                                            ($quote['status'] === 'approved' ? 'success' : 
                                            ($quote['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                        ?>">
                                        <?php echo ucfirst(htmlspecialchars($quote['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view_quote.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="View Quote">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="quotes/<?php echo htmlspecialchars($quote['quote_number']); ?>.pdf" 
                                           class="btn btn-sm btn-secondary" title="Download PDF" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <?php if ($quote['status'] === 'pending'): ?>
                                        <a href="edit_quote.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit Quote">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    No quotes found for this customer.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Customers
            </a>
            <a href="create_quote.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Quote
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
