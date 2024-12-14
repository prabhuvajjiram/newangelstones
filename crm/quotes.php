<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';

// Debug session
error_log("Debug: Session data in quotes.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

// Verify database connection
try {
    $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    error_log("Database connection verified");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

try {
    // Enable error logging
    error_log("Starting quotes fetch process");

    // Base query with all necessary joins
    $baseQuery = "
        SELECT 
            q.id,
            q.quote_number,
            q.customer_id,
            q.created_at,
            q.status,
            q.username,
            c.name as customer_name,
            c.email as customer_email,
            COUNT(DISTINCT qi.id) as item_count,
            COALESCE(SUM(qi.total_price), 0) as total_amount,
            u.first_name as created_by_first_name,
            u.last_name as created_by_last_name,
            DATE_FORMAT(q.created_at, '%Y-%m-%d') as formatted_date,
            o.order_id as order_id,
            o.order_number
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        LEFT JOIN users u ON q.username = u.email
        LEFT JOIN orders o ON q.id = o.quote_id
        WHERE 1=1
    ";

    error_log("Base query created");

    // Staff can only see their own quotes
    if (!isAdmin()) {
        if (!isset($_SESSION['email'])) {
            throw new Exception("User email not found in session");
        }
        $baseQuery .= " AND q.username = :username";
        error_log("Added staff restriction for username: " . $_SESSION['email']);
    }

    // Complete the query with GROUP BY and ORDER BY
    $baseQuery .= " GROUP BY q.id, q.quote_number, q.customer_id, q.created_at, q.status, q.username, 
                    c.name, c.email, u.first_name, u.last_name, o.order_id, o.order_number 
                    ORDER BY q.created_at DESC";

    error_log("Preparing query: " . $baseQuery);

    $stmt = $pdo->prepare($baseQuery);
    if (!$stmt) {
        error_log("Query preparation failed: " . print_r($pdo->errorInfo(), true));
        throw new PDOException("Failed to prepare query");
    }
    
    // Bind parameters if not admin
    if (!isAdmin()) {
        $userEmail = $_SESSION['email'];
        error_log("Binding username parameter: " . $userEmail);
        $stmt->bindValue(':username', $userEmail, PDO::PARAM_STR);
    }

    $success = $stmt->execute();
    if (!$success) {
        error_log("Query execution failed: " . print_r($stmt->errorInfo(), true));
        throw new PDOException("Failed to execute query: " . implode(" ", $stmt->errorInfo()));
    }

    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Successfully fetched " . count($quotes) . " quotes");

} catch (PDOException $e) {
    error_log("Database error in quotes.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    $quotes = [];
} catch (Exception $e) {
    error_log("Unexpected error in quotes.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = "An unexpected error occurred: " . $e->getMessage();
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
                                <th>Project</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Created</th>
                                <?php if (isAdmin()): ?>
                                <th>Created By</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '9' : '8'; ?>" class="text-center">No quotes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($quote['customer_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($quote['customer_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($quote['project_name'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $quote['item_count']; ?></span></td>
                                        <td>$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($quote['formatted_date'])); ?></td>
                                        <?php if (isAdmin()): ?>
                                        <td><?php echo htmlspecialchars($quote['created_by_first_name'] . ' ' . $quote['created_by_last_name']); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($quote['status']) {
                                                    'draft' => 'secondary',
                                                    'sent' => 'primary',
                                                    'accepted' => 'success',
                                                    'rejected' => 'danger',
                                                    'converted' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($quote['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="View Quote">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($quote['status'] !== 'Converted' && $quote['status'] !== 'Cancelled'): ?>
                                                    <button class="btn btn-sm btn-success convert-to-order" data-quote-id="<?php echo $quote['id']; ?>" data-bs-toggle="tooltip" title="Convert to Order">
                                                        <i class="bi bi-arrow-right-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-info generate-pdf" data-quote-id="<?php echo $quote['id']; ?>" data-bs-toggle="tooltip" title="Generate PDF">
                                                    <i class="bi bi-file-pdf"></i>
                                                </button>
                                                <?php if ($quote['order_id']): ?>
                                                    <a href="view_order.php?id=<?php echo $quote['order_id']; ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="View Order">
                                                        <i class="bi bi-box"></i>
                                                    </a>
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
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Convert to Order functionality
            $('.convert-to-order').click(function() {
                const quoteId = $(this).data('quote-id');
                const button = $(this);
                
                if (confirm('Are you sure you want to convert this quote to an order?')) {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                    
                    $.ajax({
                        url: 'ajax/convert_quote_to_order.php',
                        method: 'POST',
                        data: { quote_id: quoteId },
                        success: function(response) {
                            if (response.success) {
                                alert('Quote successfully converted to order!');
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                                button.prop('disabled', false).html('<i class="bi bi-arrow-right-circle"></i>');
                            }
                        },
                        error: function() {
                            alert('Error converting quote to order');
                            button.prop('disabled', false).html('<i class="bi bi-arrow-right-circle"></i>');
                        }
                    });
                }
            });

            // Generate PDF functionality
            $('.generate-pdf').click(function() {
                const quoteId = $(this).data('quote-id');
                window.location.href = `generate_pdf.php?id=${quoteId}`;
            });
        });
    </script>
</body>
</html>
