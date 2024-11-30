<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireLogin();

$quote = null;
$items = [];
$error = null;

try {
    // Check if we're previewing a saved quote or a new quote
    if (isset($_GET['id'])) {
        // Get saved quote by ID
        $quote_id = (int)$_GET['id'];
        
        // Fetch quote details
        $stmt = $pdo->prepare("SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone 
            FROM quotes q 
            JOIN customers c ON q.customer_id = c.id 
            WHERE q.id = ?");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            throw new Exception('Quote not found');
        }

        // Fetch quote items
        $stmt = $pdo->prepare("SELECT qi.*, sc.color_name 
            FROM quote_items qi 
            LEFT JOIN stone_colors sc ON qi.stone_color_id = sc.id 
            WHERE qi.quote_id = ?");
        $stmt->execute([$quote_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quote_data'])) {
        // Handle preview of unsaved quote
        $quote_data = json_decode($_POST['quote_data'], true);
        
        if (!$quote_data || !isset($quote_data['customer_id'])) {
            throw new Exception('Invalid quote data');
        }

        // Get customer details
        $stmt = $pdo->prepare("
            SELECT name as customer_name, email as customer_email, phone as customer_phone
            FROM customers 
            WHERE id = ?
        ");
        $stmt->execute([$quote_data['customer_id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new Exception('Customer not found');
        }

        // Save quote to database
        try {
            $pdo->beginTransaction();

            // Insert quote
            $stmt = $pdo->prepare("
                INSERT INTO quotes (
                    customer_id, commission_rate, total_amount, commission_amount,
                    created_at, status
                ) VALUES (
                    ?, ?, ?, ?, NOW(), 'draft'
                )
            ");
            $stmt->execute([
                $quote_data['customer_id'],
                $quote_data['commission_rate'],
                $quote_data['total_amount'],
                $quote_data['commission_amount']
            ]);

            $quote_id = $pdo->lastInsertId();

            // Insert quote items
            $stmt = $pdo->prepare("
                INSERT INTO quote_items (
                    quote_id, product_type, model, size, stone_color_id,
                    length, breadth, sqft, cubic_feet, quantity,
                    unit_price, total_price, commission_amount
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            foreach ($quote_data['items'] as $item) {
                $stmt->execute([
                    $quote_id,
                    $item['product_type'],
                    $item['model'],
                    $item['size'],
                    $item['stone_color_id'],
                    $item['length'],
                    $item['breadth'],
                    $item['sqft'],
                    $item['cubic_feet'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['commission_amount']
                ]);
            }

            $pdo->commit();

            // Redirect to the saved quote
            header("Location: preview_quote.php?id=" . $quote_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception('Failed to save quote: ' . $e->getMessage());
        }

        // Prepare quote data for display
        $quote = array_merge($customer, [
            'customer_id' => $quote_data['customer_id'],
            'commission_rate' => $quote_data['commission_rate'],
            'quote_date' => date('Y-m-d'),
            'status' => 'draft'
        ]);

        // Process items
        $items = $quote_data['items'];
        
        // Calculate totals
        $subtotal = 0;
        $total_commission = 0;
        $grand_total = 0;

        foreach ($items as &$item) {
            $subtotal += $item['base_price'] * $item['quantity'];
            $total_commission += $item['commission_amount'] * $item['quantity'];
            $grand_total += $item['total_price'] * $item['quantity'];
        }

        $quote['subtotal'] = $subtotal;
        $quote['total_commission'] = $total_commission;
        $quote['grand_total'] = $grand_total;

    } else {
        throw new Exception('No quote data provided');
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// If there's an error, show it
if ($error) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($error) . "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Preview - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Quote Preview</h2>
                <?php if (isset($quote['quote_number'])): ?>
                    <p class="text-muted">Quote #<?php echo htmlspecialchars($quote['quote_number']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <form action="generate_pdf.php" method="post" class="d-inline">
                    <input type="hidden" name="quote_data" value='<?php echo htmlspecialchars(json_encode([
                        'customer_name' => $quote['customer_name'],
                        'customer_email' => $quote['customer_email'],
                        'customer_phone' => $quote['customer_phone'],
                        'commission_rate' => $quote['commission_rate'],
                        'subtotal' => $quote['subtotal'],
                        'total_amount' => $quote['grand_total'],
                        'commission_amount' => $quote['total_commission'],
                        'items' => array_map(function($item) {
                            return [
                                'type' => $item['product_type'],
                                'model' => $item['model'],
                                'size' => $item['size'],
                                'color' => $item['stone_color_id'] ?? 'N/A',
                                'dimensions' => $item['length'] . '" × ' . $item['breadth'] . '"',
                                'quantity' => $item['quantity'],
                                'unit_price' => $item['unit_price'],
                                'total_price' => $item['total_price']
                            ];
                        }, $items)
                    ])); ?>'>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-file-pdf"></i> Generate PDF
                    </button>
                </form>
                <?php if (isset($_GET['id'])): ?>
                <form action="delete_quote.php" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this quote?');">
                    <input type="hidden" name="quote_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                    <button type="submit" class="btn btn-danger me-2">
                        <i class="bi bi-trash"></i> Delete Quote
                    </button>
                </form>
                <?php endif; ?>
                <a href="quote.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Quote Details -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quote Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($quote['customer_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($quote['customer_email']); ?><br>
                            <?php if (isset($quote['customer_phone'])): ?>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($quote['customer_phone']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Quote Information</h6>
                        <p>
                            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($quote['quote_date'])); ?><br>
                            <strong>Commission Rate:</strong> <?php echo number_format($quote['commission_rate'], 2); ?>%<br>
                            <?php if (isset($quote['status'])): ?>
                                <strong>Status:</strong> <?php echo ucfirst($quote['status']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Quote Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Model</th>
                                <th>Color</th>
                                <th>Dimensions</th>
                                <th>Qty</th>
                                <th class="text-end">Base Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                <td><?php echo htmlspecialchars($item['size']); ?></td>
                                <td><?php echo htmlspecialchars($item['model']); ?></td>
                                <td><?php echo htmlspecialchars($item['stone_color_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['length']); ?>" × <?php echo htmlspecialchars($item['breadth']); ?>"</td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['base_price'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="7" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($quote['subtotal'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Commission (<?php echo number_format($quote['commission_rate'], 2); ?>%):</strong></td>
                                <td class="text-end">$<?php echo number_format($quote['total_commission'], 2); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($quote['grand_total'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
