<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get quote ID from URL
$quote_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$quote_id) {
    die("Error: Invalid quote ID.");
}

try {
    // Debug output
   // echo "<pre>";
    //echo "Debug Information:\n";
    //echo "Quote ID: " . $quote_id . "\n";
    //echo "User Role: " . $_SESSION['role'] . "\n";
    //echo "User Email: " . $_SESSION['email'] . "\n";

    // Get quote details with permission check
    $query = "
        SELECT 
            q.*,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            DATE_FORMAT(q.created_at, '%Y-%m-%d') as quote_date,
            u.first_name as created_by_first_name,
            u.last_name as created_by_last_name,
            u.email as user_email
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN users u ON q.username = u.email
        WHERE q.id = :quote_id
    ";

    if (!isAdmin()) {
        $query .= " AND q.username = :username";
    }

    //echo "\nQuote Query:\n" . $query . "\n";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        die("Error preparing quote query: " . print_r($pdo->errorInfo(), true));
    }

    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    if (!isAdmin()) {
        $stmt->bindParam(':username', $_SESSION['email'], PDO::PARAM_STR);
    }

    $success = $stmt->execute();
    if (!$success) {
        die("Error executing quote query: " . print_r($stmt->errorInfo(), true));
    }

    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        die("Error: Quote not found or you don't have permission to view it.");
    }

    //echo "\nQuote Data:\n";
    //print_r($quote);

    // Get quote items with product details and color
    $items_query = "
        SELECT 
            qi.*,
            scr.color_name,
            CASE 
                WHEN qi.product_type = 'sertop' AND qi.special_monument_id IS NOT NULL 
                THEN (SELECT sp_value FROM special_monument WHERE id = qi.special_monument_id)
                ELSE NULL
            END as special_monument_details,
            CASE qi.product_type
                WHEN 'sertop' THEN (SELECT model FROM sertop_products WHERE id = qi.model)
                WHEN 'slant' THEN (SELECT model FROM slant_products WHERE id = qi.model)
                WHEN 'marker' THEN (SELECT model FROM marker_products WHERE id = qi.model)
                WHEN 'base' THEN (SELECT model FROM base_products WHERE id = qi.model)
            END as model_name
        FROM quote_items qi
        LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id
        WHERE qi.quote_id = :quote_id
        ORDER BY qi.id ASC
    ";
    
    // echo "\nItems Query:\n" . $items_query . "\n";
    
    $stmt = $pdo->prepare($items_query);
    if (!$stmt) {
        die("Error preparing items query: " . print_r($pdo->errorInfo(), true));
    }

    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    
    $success = $stmt->execute();
    if (!$success) {
        die("Error executing items query: " . print_r($stmt->errorInfo(), true));
    }

    $quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
   // echo "\nQuote Items Data:\n";
    //print_r($quote_items);
    //echo "</pre>";

    // Calculate totals
    $subtotal = array_sum(array_column($quote_items, 'total_price'));
    $commission_amount = ($subtotal * $quote['commission_rate']) / 100;
    $total = $subtotal + $commission_amount;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Continue with HTML output only if no errors occurred
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote #<?php echo htmlspecialchars($quote['quote_number']); ?> - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col">
                <h2>Quote #<?php echo htmlspecialchars($quote['quote_number']); ?></h2>
                <p class="text-muted mb-0">
                    Created on <?php echo date('F j, Y', strtotime($quote['quote_date'])); ?>
                    <?php if (isAdmin()): ?>
                    by <?php echo htmlspecialchars($quote['created_by_first_name'] . ' ' . $quote['created_by_last_name']); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-auto">
                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteQuoteModal">
                        <i class="bi bi-trash"></i> Delete Quote
                    </button>
                    <a href="quotes.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Quotes
                    </a>
                </div>
                <?php if ($quote['status'] === 'pending' && ($quote['username'] === $_SESSION['email'] || isAdmin())): ?>
                    <a href="quote.php?edit=<?php echo $quote_id; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Quote
                    </a>
                <?php endif; ?>
                <a href="generate_pdf.php?id=<?php echo $quote_id; ?>" class="btn btn-success" target="_blank">
                    <i class="bi bi-file-pdf"></i> Generate PDF
                </a>
                <?php if ($quote['customer_email']): ?>
                <a href="mailto:<?php echo htmlspecialchars($quote['customer_email']); ?>?subject=Quote #<?php echo $quote['quote_number']; ?>" class="btn btn-info">
                    <i class="bi bi-envelope"></i> Email Customer
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person"></i> Customer Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($quote['customer_name']); ?></p>
                        <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($quote['customer_email']); ?></p>
                        <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($quote['customer_phone']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle"></i> Quote Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $quote['status'] === 'pending' ? 'warning' : ($quote['status'] === 'approved' ? 'success' : 'danger'); ?>">
                                <?php echo ucfirst(htmlspecialchars($quote['status'])); ?>
                            </span>
                        </p>
                        <?php if (!empty($quote['valid_until'])): ?>
                        <p class="mb-2"><strong>Valid Until:</strong> <?php echo date('F j, Y', strtotime($quote['valid_until'])); ?></p>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <p class="mb-0"><strong>Commission Rate:</strong> <?php echo number_format($quote['commission_rate'], 1); ?>%</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check"></i> Quote Items
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Model</th>
                                <th>Color</th>
                                <th>Dimensions</th>
                                <th>Cu.ft</th>
                                <th>Qty</th>
                                <th class="text-end">Base Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quote_items)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No items found in this quote.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quote_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['model_name'] ?? $item['model']); ?></td>
                                    <td><?php echo htmlspecialchars($item['color_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                        $length = isset($item['length']) ? htmlspecialchars($item['length']) : '0';
                                        $breadth = isset($item['breadth']) ? htmlspecialchars($item['breadth']) : '0';
                                        $size = isset($item['size']) ? htmlspecialchars($item['size']) : '0';
                                        echo "{$length}\" × {$breadth}\" × {$size}\"";
                                        if (!empty($item['special_monument_details'])) {
                                            echo "<br><small class='text-muted'>Special: " . htmlspecialchars($item['special_monument_details']) . "</small>";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($item['cubic_feet']) ? number_format($item['cubic_feet'], 2) : '0.00'; ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="7" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <?php if (isAdmin()): ?>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Commission (<?php echo number_format($quote['commission_rate'], 1); ?>%):</strong></td>
                                <td class="text-end">$<?php echo number_format($commission_amount, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($quote['notes'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-sticky"></i> Notes
                </h5>
            </div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($quote['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Quote Modal -->
    <div class="modal fade" id="deleteQuoteModal" tabindex="-1" aria-labelledby="deleteQuoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteQuoteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this quote? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form action="delete_quote.php" method="POST">
                        <input type="hidden" name="quote_id" value="<?php echo htmlspecialchars($quote['id']); ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Quote</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
