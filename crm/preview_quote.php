<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'session_check.php';

requireLogin();

if (isset($_GET['quote_id']) || isset($_GET['id'])) {
    $quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : (int)$_GET['id'];
    
    // Check access rights - using customer_id instead of created_by
    $access_check_sql = "SELECT customer_id FROM quotes WHERE id = :quote_id";
    $check_stmt = $pdo->prepare($access_check_sql);
    $check_stmt->execute(['quote_id' => $quote_id]);
    $quote_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote_data) {
        die('Quote not found');
    }

    // Only allow access if user is admin or if the quote belongs to their customer
    if (!in_array('admin', $_SESSION['roles'])) {
        // Get the user's assigned customers
        $user_customers_sql = "SELECT customer_id FROM user_customers WHERE user_id = :user_id";
        $user_customers_stmt = $pdo->prepare($user_customers_sql);
        $user_customers_stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user_customers = $user_customers_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array($quote_data['customer_id'], $user_customers)) {
            die('Access denied');
        }
    }
}

$quote = null;
$items = [];
$error = null;

try {
    // Check if we're previewing a saved quote or a new quote
    if (isset($_GET['quote_id']) || isset($_GET['id'])) {
        // Get saved quote by ID
        $quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : (int)$_GET['id'];
        
        // First check if the user has access to this quote
        $access_check_sql = "
            SELECT customer_id 
            FROM quotes 
            WHERE id = :quote_id
        ";
        $check_stmt = $pdo->prepare($access_check_sql);
        $check_stmt->execute(['quote_id' => $quote_id]);
        $quote_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote_data) {
            throw new Exception('Quote not found');
        }

        // Check if user has access (admin or quote belongs to their customer)
        if (!in_array('admin', $_SESSION['roles'])) {
            // Get the user's assigned customers
            $user_customers_sql = "SELECT customer_id FROM user_customers WHERE user_id = :user_id";
            $user_customers_stmt = $pdo->prepare($user_customers_sql);
            $user_customers_stmt->execute(['user_id' => $_SESSION['user_id']]);
            $user_customers = $user_customers_stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array($quote_data['customer_id'], $user_customers)) {
                throw new Exception('Access denied');
            }
        }

        // If access check passes, get the full quote data
        $stmt = $pdo->prepare("
            SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   DATE_FORMAT(q.created_at, '%Y-%m-%d') as quote_date 
            FROM quotes q 
            JOIN customers c ON q.customer_id = c.id 
            WHERE q.id = ?
        ");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            throw new Exception('Quote not found');
        }

        // Fetch quote items
        $stmt = $pdo->prepare("
            SELECT qi.*, scr.color_name,
                   qi.length as length_inches,
                   qi.breadth as breadth_inches
            FROM quote_items qi 
            LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id 
            WHERE qi.quote_id = ?
        ");
        $stmt->execute([$quote_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Set content type for all POST responses
        header('Content-Type: application/json');
        
        // Get quote data from POST
        $quote_data = null;
        if (isset($_POST['quote_data'])) {
            $quote_data = json_decode($_POST['quote_data'], true);
        }
        
        if (!$quote_data) {
            error_log("Invalid or missing quote data");
            echo json_encode(['success' => false, 'message' => 'Invalid or missing quote data']);
            exit;
        }
        
        if (!isset($quote_data['customer_id'])) {
            error_log("Missing customer_id in quote data");
            echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
            exit;
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

        // Set quote date
        $quote_data['quote_date'] = date('Y-m-d');

        // Combine quote data with customer details
        $quote = array_merge($quote_data, $customer);
        $items = $quote_data['items'] ?? [];

        // Process items to match database structure
        foreach ($items as &$item) {
            $item['length_inches'] = $item['length'];
            $item['breadth_inches'] = $item['breadth'];
            // Use the quote's commission rate for all items if not set individually
            if (!isset($item['commission_rate']) && isset($quote_data['commission_rate'])) {
                $item['commission_rate'] = floatval($quote_data['commission_rate']);
            } elseif (!isset($item['commission_rate'])) {
                $item['commission_rate'] = 0;
            }
        }
        unset($item);

        // Save the quote to the database
        $pdo->beginTransaction();
        try {
            // Insert into quotes table
            $stmt = $pdo->prepare("
                INSERT INTO quotes (
                    customer_id, quote_number, status, total_amount, 
                    commission_rate, commission_amount, created_at, updated_at,
                    user_id
                ) VALUES (
                    :customer_id, :quote_number, 'pending', :total_amount,
                    :commission_rate, :commission_amount, NOW(), NOW(),
                    :user_id
                )
            ");

            $quote_number = generateQuoteNumber();
            $commission_rate = isset($quote_data['commission_rate']) ? floatval($quote_data['commission_rate']) : 0;
            $commission_amount = isset($quote_data['commission_amount']) ? floatval($quote_data['commission_amount']) : 0;
            $total_amount = isset($quote_data['total_amount']) ? floatval($quote_data['total_amount']) : 0;

            $stmt->execute([
                'customer_id' => $quote_data['customer_id'],
                'quote_number' => $quote_number,
                'total_amount' => $total_amount,
                'commission_rate' => $commission_rate,
                'commission_amount' => $commission_amount,
                'user_id' => $_SESSION['user_id']
            ]);

            $quote_id = $pdo->lastInsertId();
            $quote['id'] = $quote_id;
            $quote['quote_number'] = $quote_number;

            // Insert quote items
            $stmt = $pdo->prepare("
                INSERT INTO quote_items (
                    quote_id, product_type, model, size, color_id,
                    length, breadth, sqft, cubic_feet, quantity,
                    unit_price, total_price, commission_rate,
                    created_at
                ) VALUES (
                    :quote_id, :product_type, :model, :size, :color_id,
                    :length, :breadth, :sqft, :cubic_feet, :quantity,
                    :unit_price, :total_price, :commission_rate,
                    NOW()
                )
            ");

            foreach ($items as $item) {
                $stmt->execute([
                    'quote_id' => $quote_id,
                    'product_type' => $item['product_type'],
                    'model' => $item['model'],
                    'size' => $item['size'],
                    'color_id' => $item['color_id'] ?: null,
                    'length' => $item['length'] ?: 0,
                    'breadth' => $item['breadth'] ?: 0,
                    'sqft' => $item['sqft'] ?: 0,
                    'cubic_feet' => $item['cubic_feet'] ?: 0,
                    'quantity' => $item['quantity'] ?: 1,
                    'unit_price' => $item['unit_price'] ?: 0,
                    'total_price' => $item['total_price'] ?: 0,
                    'commission_rate' => $item['commission_rate'] ?: 0
                ]);
            }

            $pdo->commit();

            // Send success response for AJAX requests
            echo json_encode([
                'success' => true, 
                'quote_id' => $quote_id,
                'redirect_url' => "preview_quote.php?id=" . $quote_id
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception('Failed to save quote: ' . $e->getMessage());
        }
    } else {
        throw new Exception('Invalid request');
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in preview_quote.php: " . $e->getMessage());
    
    // Send error response for AJAX requests
    if (!empty($post_data)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}

// If we have an error and it's not an AJAX request, show it
if ($error && empty($post_data)) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Quote Preview</h2>
                <?php if (isset($quote['quote_number'])): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4>Quote #<?php echo htmlspecialchars($quote['quote_number']); ?></h4>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger" onclick="deleteQuote(<?php echo (int)$quote['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete Quote
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col text-end">
                <a href="<?php echo getUrl('quote.php'); ?>" class="btn btn-secondary me-2">Back</a>
                <?php if (isset($quote['id'])): ?>
                    <a href="<?php echo getUrl('generate_pdf.php?id=' . $quote['id']); ?>" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> Generate PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quote Details -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Quote Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($quote['customer_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($quote['customer_email']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($quote['customer_phone']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Quote Information</h6>
                        <p>
                            <strong>Date:</strong> <?php echo isset($quote['quote_date']) ? date('F j, Y', strtotime($quote['quote_date'])) : date('F j, Y'); ?><br>
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
                                <td><?php echo htmlspecialchars($item['color_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $length = isset($item['length_inches']) ? htmlspecialchars($item['length_inches']) : '0';
                                    $breadth = isset($item['breadth_inches']) ? htmlspecialchars($item['breadth_inches']) : '0';
                                    echo "{$length}\" × {$breadth}\"";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php 
                        // Calculate subtotal from items
                        $subtotal = 0;
                        foreach ($items as $item) {
                            $subtotal += $item['total_price'];
                        }
                        $quote['subtotal'] = $subtotal;
                        ?>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="7" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($quote['subtotal'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Commission (<?php echo number_format($quote['commission_rate'], 2); ?>%):</strong></td>
                                <td class="text-end">$<?php echo number_format($quote['commission_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($quote['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteQuote(quoteId) {
        if (!confirm('Are you sure you want to delete this quote? This action cannot be undone.')) {
            return;
        }

        fetch('ajax/delete_quote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ quote_id: quoteId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Quote deleted successfully');
                window.location.href = 'quotes.php';
            } else {
                alert('Error deleting quote: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the quote');
        });
    }
    </script>
</body>
</html>
