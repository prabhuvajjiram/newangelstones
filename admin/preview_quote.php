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

// Get quote items with total prices
$stmt = $conn->prepare("
    SELECT qi.*,
           scr.color_name,
           scr.price_increase_percentage,
           COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) as product_base_price,
           q.commission_rate,
           -- Calculate cubic feet
           ROUND(((qi.length * qi.breadth * qi.size) / 1728) * qi.quantity, 2) as cubic_feet,
           -- Calculate SQFT
           ROUND((qi.length * qi.breadth) / 144, 2) as sqft,
           -- Calculate base price without quantity
           ROUND(((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price), 2) as base_price,
           -- Calculate color price without quantity
           ROUND(((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (COALESCE(scr.price_increase_percentage, 0) / 100), 2) as color_price,
           -- Calculate total price before commission (base + color) * quantity
           ROUND((
               ((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (1 + COALESCE(scr.price_increase_percentage, 0) / 100)
           ) * qi.quantity, 2) as total_price_before_commission,
           -- Calculate commission on total price after quantity
           ROUND((
               ((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (1 + COALESCE(scr.price_increase_percentage, 0) / 100) * qi.quantity
           ) * (q.commission_rate / 100), 2) as commission_price,
           -- Calculate final total price including commission
           ROUND((
               ((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (1 + COALESCE(scr.price_increase_percentage, 0) / 100) * qi.quantity
           ) * (1 + q.commission_rate / 100), 2) as total_price
    FROM quote_items qi
    JOIN quotes q ON qi.quote_id = q.id
    LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id
    LEFT JOIN sertop_products sp ON qi.model = sp.model AND qi.size = sp.size_inches AND qi.product_type = 'sertop'
    LEFT JOIN base_products bp ON qi.model = bp.model AND qi.size = bp.size_inches AND qi.product_type = 'base'
    LEFT JOIN marker_products mp ON qi.model = mp.model AND qi.size = mp.square_feet AND qi.product_type = 'marker'
    LEFT JOIN slant_products slp ON qi.model = slp.model AND qi.product_type = 'slant'
    WHERE qi.quote_id = ?
");

$stmt->bind_param('i', $quote_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$total_before_commission = 0;
$total_commission = 0;
$grand_total = 0;

foreach ($items as $item) {
    $total_before_commission += $item['total_price_before_commission'];
    $total_commission += $item['commission_price'];
    $grand_total += $item['total_price'];
}

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
                <a href="generate_pdf.php?id=<?php echo htmlspecialchars($quote_id); ?>" class="btn btn-primary" target="_blank">
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
                                <th>SQFT</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                <td><?php echo htmlspecialchars($item['model']); ?></td>
                                <td><?php echo htmlspecialchars($item['size']); ?></td>
                                <td><?php echo htmlspecialchars($item['color_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['length']); ?>" × <?php echo htmlspecialchars($item['breadth']); ?>"</td>
                                <td><?php echo number_format($item['cubic_feet'], 2); ?></td>
                                <td><?php echo number_format($item['sqft'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Subtotal:</strong></td>
                                <td><strong>$<?php echo number_format($total_before_commission, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Commission (<?php echo number_format($quote['commission_rate'], 2); ?>%):</strong></td>
                                <td><strong>$<?php echo number_format($total_commission, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                            </tr>
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
                        <div class="mb-3">
                            <strong>Project Name:</strong>
                            <?php echo htmlspecialchars($quote['project_name'] ?? 'N/A'); ?>
                        </div>
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
