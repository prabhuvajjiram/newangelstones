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
        u.last_name as created_by_last_name,
        o.id as order_id,
        o.order_number
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        LEFT JOIN users u ON q.created_by = u.id
        LEFT JOIN orders o ON q.id = o.quote_id
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
            <h1 data-bs-toggle="tooltip" data-bs-placement="top" title="View all quotes for this customer">Quotes for <?php echo htmlspecialchars($customer['name']); ?></h1>
            <a href="customers.php" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Return to customer list">
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
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Unique identifier for each quote">Quote #</th>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Date when the quote was created">Date</th>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Total amount of the quote">Items</th>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Current status of the quote">Status</th>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Available actions for this quote">Created By</th>
                                <th data-bs-toggle="tooltip" data-bs-placement="top" title="Available actions for this quote">Actions</th>
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
                                        <td>
                                            <span class="badge <?php 
                                                echo match($quote['status']) {
                                                    'draft' => 'bg-secondary',
                                                    'sent' => 'bg-primary',
                                                    'accepted' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    'converted' => 'bg-info',
                                                    default => 'bg-secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($quote['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($quote['created_by_first_name'] . ' ' . $quote['created_by_last_name']); ?></td>
                                        <td>
                                            <?php if ($quote['status'] === 'converted'): ?>
                                                <a href="view_order.php?id=<?php echo htmlspecialchars($quote['order_id']); ?>" 
                                                   class="btn btn-sm btn-info me-2"
                                                   data-bs-toggle="tooltip"
                                                   title="View associated order">
                                                    <i class="bi bi-box"></i> View Order
                                                </a>
                                            <?php elseif (in_array($quote['status'], ['accepted', 'sent'])): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success me-2" 
                                                        onclick="convertToOrder(<?php echo $quote['id']; ?>)"
                                                        data-bs-toggle="tooltip"
                                                        title="Convert this quote to an order">
                                                    <i class="bi bi-cart-plus"></i> Convert to Order
                                                </button>
                                            <?php endif; ?>
                                            <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" 
                                               class="btn btn-sm btn-primary me-2"
                                               data-bs-toggle="tooltip"
                                               title="Preview this quote">
                                                <i class="bi bi-eye"></i> Preview
                                            </a>
                                            <a href="generate_pdf.php?id=<?php echo $quote['id']; ?>" 
                                               class="btn btn-sm btn-secondary"
                                               target="_blank"
                                               data-bs-toggle="tooltip"
                                               title="Generate PDF">
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
        
        <!-- Add New Quote Button -->
        <div class="mb-3">
            <a href="create_quote.php?customer_id=<?php echo $customer_id; ?>" 
               class="btn btn-primary"
               data-bs-toggle="tooltip"
               title="Create a new quote for this customer">
                <i class="bi bi-plus-circle"></i> New Quote
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });

        function convertToOrder(quoteId) {
            if (confirm('Are you sure you want to convert this quote to an order?')) {
                // Disable the button and show loading state
                const button = event.target.closest('button');
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Converting...';

                fetch('ajax/convert_quote_to_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'quote_id=' + quoteId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message with SweetAlert or similar
                        alert('Quote successfully converted to order #' + data.order_number);
                        window.location.href = 'view_order.php?id=' + data.order_id;
                    } else {
                        alert('Error: ' + data.message);
                        // Reset button state
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-cart-plus"></i> Convert to Order';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error converting quote to order. Please try again.');
                    // Reset button state
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-cart-plus"></i> Convert to Order';
                });
            }
        }
    </script>
</body>
</html>
