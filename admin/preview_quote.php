<?php
require_once 'includes/config.php';
requireLogin();

// Get quote ID from URL
$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$quote_id) {
    die('Quote ID is required');
}

// Get quote details
$stmt = $conn->prepare("
    SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
    FROM quotes q
    JOIN customers c ON q.customer_id = c.id
    WHERE q.id = ?
");

if (!$stmt) {
    die('Failed to prepare quote query: ' . $conn->error);
}

$stmt->bind_param('i', $quote_id);
$stmt->execute();
$quote = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quote) {
    die('Quote not found');
}

// Get quote items
$stmt = $conn->prepare("
    SELECT qi.*, scr.color_name
    FROM quote_items qi
    LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id
    WHERE qi.quote_id = ?
");

$stmt->bind_param('i', $quote_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Quote #<?php echo htmlspecialchars($quote['quote_number']); ?> - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Quote Preview</h2>
                <p class="text-muted">Quote #<?php echo htmlspecialchars($quote['quote_number']); ?></p>
            </div>
            <div class="col text-end">
                <a href="<?php echo htmlspecialchars($quote['pdf_file']); ?>" class="btn btn-primary" target="_blank">
                    <i class="bi bi-file-pdf"></i> View PDF
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Customer Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($quote['customer_name']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($quote['customer_email']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($quote['customer_phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Quote Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Model</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Dimensions</th>
                                <th>Cubic Feet</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($items as $item) {
                                $subtotal += $item['price'];
                            }
                            
                            // Calculate commission for each item
                            $commission_rate = $quote['commission_rate'];
                            $total_commission = $subtotal * ($commission_rate / 100);
                            
                            foreach ($items as $item): 
                                // Calculate proportional commission for this item
                                $item_commission = ($item['price'] / $subtotal) * $total_commission;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                <td><?php echo htmlspecialchars($item['model']); ?></td>
                                <td><?php echo htmlspecialchars($item['size']); ?></td>
                                <td><?php echo htmlspecialchars($item['color_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['length']); ?>" × <?php echo htmlspecialchars($item['breadth']); ?>"</td>
                                <td><?php echo number_format($item['cubic_feet'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>$<?php echo number_format($item['price'] + $item_commission, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Subtotal:</strong></td>
                                <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                            </tr>
                            <?php if ($quote['commission_rate'] > 0): 
                                $commission = $subtotal * ($quote['commission_rate'] / 100);
                                $total = $subtotal + $commission;
                            ?>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Commission Rate:</strong></td>
                                <td><?php echo number_format($quote['commission_rate'], 2); ?>%</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Commission Amount:</strong></td>
                                <td>$<?php echo number_format($commission, 2); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="7" class="text-end"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            </tr>
                            <?php else: ?>
                            <tr class="table-primary">
                                <td colspan="7" class="text-end"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Additional Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Project Name:</strong> <?php echo htmlspecialchars($quote['project_name']); ?></p>
                        <p><strong>Created At:</strong> <?php echo date('F j, Y g:i A', strtotime($quote['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
