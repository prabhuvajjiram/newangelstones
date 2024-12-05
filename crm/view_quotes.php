<?php
require_once __DIR__ . '/includes/config.php';
require_once 'session_check.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get customer ID from URL
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if (!$customer_id) {
    header('Location: customers.php');
    exit;
}

try {
    // Get customer details (all users can see all customers)
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['error'] = "Customer not found.";
        header('Location: customers.php');
        exit;
    }

    // Get quotes for this customer based on user role
    $query = "
        SELECT q.*, c.name as customer_name, c.email as customer_email, 
        COUNT(qi.id) as item_count,
        u.first_name as created_by_first_name,
        u.last_name as created_by_last_name
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        LEFT JOIN users u ON q.created_by = u.id
        WHERE q.customer_id = :customer_id
    ";

    // Staff can only see their own quotes
    if (!isAdmin()) {
        $query .= " AND q.created_by = :user_id";
    }

    $query .= " GROUP BY q.id ORDER BY q.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    
    if (!isAdmin()) {
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }

    $stmt->execute();
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching quotes: " . $e->getMessage());
    $error = "Failed to fetch quotes";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quotes - <?php echo htmlspecialchars($customer['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Quotes for <?php echo htmlspecialchars($customer['name']); ?></h1>
            <a href="customers.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Customers
            </a>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Customer Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quote History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No quotes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($quote['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($quote['item_count']); ?></td>
                                        <td><?php echo htmlspecialchars($quote['status']); ?></td>
                                        <td><?php echo htmlspecialchars($quote['created_by_first_name'] . ' ' . $quote['created_by_last_name']); ?></td>
                                        <td>
                                            <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="generate_pdf.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="bi bi-file-pdf"></i> PDF
                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
