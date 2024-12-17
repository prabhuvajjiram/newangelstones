<?php
require_once 'session_check.php';
requireLogin();  // This will redirect to login.php if not logged in

require_once 'includes/config.php';
require_once 'includes/modules/ProductCalculator.php';
require_once 'includes/modules/ProductRepository.php';
require_once 'includes/modules/QuoteRepository.php';
require_once 'includes/modules/QuoteUIHandler.php';

// Get customer ID and quote ID from URL
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;

// Initialize repositories and handlers
$productRepository = new ProductRepository($pdo);
$quoteRepository = new QuoteRepository($pdo);
$calculator = new ProductCalculator($pdo);
$uiHandler = new QuoteUIHandler($productRepository, $calculator);

// Get all necessary data
$rawProductData = $uiHandler->getInitialData();
error_log("Raw Product Data: " . print_r($rawProductData, true));

// Process the product data for the frontend
$processedProductData = [];

// Process each product type
foreach ($rawProductData as $type => $sizeGroups) {
    $processedProductData[$type] = [
        'sizes' => [],
        'models' => []
    ];
    
    // First collect all unique sizes
    foreach ($sizeGroups as $size => $models) {
        if (!in_array($size, $processedProductData[$type]['sizes'])) {
            $processedProductData[$type]['sizes'][] = $size;
        }
    }
    
    // Sort sizes numerically
    sort($processedProductData[$type]['sizes'], SORT_NUMERIC);
    
    // Then organize models by size
    foreach ($sizeGroups as $size => $models) {
        $processedProductData[$type]['models'][$size] = array_map(function($model) {
            return [
                'id' => $model['id'],
                'name' => $model['model'],
                'base_price' => $model['base_price'],
                'length' => isset($model['length_inches']) ? $model['length_inches'] : null,
                'breadth' => isset($model['breadth_inches']) ? $model['breadth_inches'] : null,
                'thickness_inches' => isset($model['thickness_inches']) ? $model['thickness_inches'] : null
            ];
        }, $models);
    }
}

error_log("Processed Product Data: " . print_r($processedProductData, true));

// Get stone colors and special monuments
$stone_colors = $quoteRepository->getStoneColors();
error_log("Stone Colors: " . print_r($stone_colors, true));

$special_monuments = $quoteRepository->getSpecialMonuments();
error_log("Special Monuments: " . print_r($special_monuments, true));

// Convert to JSON for JavaScript
$productDataJson = json_encode($processedProductData);
error_log("Product Data JSON: " . $productDataJson);

$quoteDataJson = json_encode([
    'customer_id' => $customer_id,
    'quote_id' => $quote_id,
    'stone_colors' => $stone_colors,
    'special_monuments' => $special_monuments
]);

// Get customers for dropdown
$stmt = $pdo->query("SELECT id, name, email FROM customers ORDER BY name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize error message
$error_message = '';

// Handle form submission - only if POST request and generate_quote button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_quote'])) {
    try {
        // Validate customer_id
        if (empty($_POST['customer_id'])) {
            throw new Exception("Please select a customer");
        }

        // Verify customer exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
        $stmt->execute([$_POST['customer_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Selected customer does not exist");
        }

        // Decode the JSON string from items input
        $items = json_decode($_POST['items'], true);
        
        if (!is_array($items) || empty($items)) {
            throw new Exception("Please add items to the quote");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Insert into quotes table
            $stmt = $pdo->prepare("INSERT INTO quotes (customer_id, customer_email, total_amount, commission_rate, commission_amount, status, valid_until, created_at) 
                VALUES (?, ?, ?, ?, ?, 'draft', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())");
                
            $total_amount = 0;
            $commission_rate = isset($_POST['commission_rate']) ? floatval($_POST['commission_rate']) : 0;
            
            // Calculate totals from items
            foreach ($items as $item) {
                $total_amount += floatval($item['totalPrice']);
            }
            
            $commission_amount = $total_amount * ($commission_rate / 100);
            
            $stmt->execute([
                $_POST['customer_id'],
                $_POST['customer_email'],
                $total_amount,
                $commission_rate,
                $commission_amount
            ]);
            
            $quote_id = $pdo->lastInsertId();
            
            // Insert items into quote_items table
            $stmt = $pdo->prepare("INSERT INTO quote_items (quote_id, product_type, model, size, color_id, length, breadth, sqft, cubic_feet, quantity, unit_price, total_price, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
            foreach ($items as $item) {
                $stmt->execute([
                    $quote_id,
                    $item['modelId'],
                    $item['modelName'],
                    $item['size'] ?? '',
                    $item['colorId'] ?? null,
                    $item['length'],
                    $item['width'],
                    $item['sqft'],
                    $item['cuft'],
                    $item['quantity'] ?? 1,
                    $item['basePrice'],
                    $item['totalPrice']
                ]);
            }
            
            $pdo->commit();
            
            // Redirect to preview page with the quote ID
            header("Location: preview_quote.php?id=" . $quote_id);
            exit();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        error_log("Error saving quote: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Quote - Angel Stones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .form-select, .form-control {
            border-radius: 0.5rem;
            border-color: #dee2e6;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .card {
            border-radius: 0.5rem;
            border: none;
        }
        
        .card-header {
            border-bottom: 1px solid #dee2e6;
        }
        
        .badge {
            font-size: 1rem;
            font-weight: normal;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }
        
        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Customer Selection Card -->
        <form id="quoteForm" method="post" action="preview_quote.php" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate a new quote for your customer">
            <input type="hidden" id="customer_id" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">
            <input type="hidden" id="customer_email" name="customer_email" value="">
            <input type="hidden" id="commission_rate" name="commission_rate" value="">
            <input type="hidden" id="items" name="items" value="">
            <input type="hidden" name="generate_quote" value="1">
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Select Customer</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label for="customer_select" class="form-label">Customer</label>
                            <select class="form-select form-select-lg" id="customer_select" name="customer_select">
                                <option value="">Select a customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo htmlspecialchars($customer['id']); ?>" 
                                            data-email="<?php echo htmlspecialchars($customer['email']); ?>">
                                        <?php echo htmlspecialchars($customer['name']) . ' (' . htmlspecialchars($customer['email']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group w-100">
                                <a href="create_customer.php" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-plus"></i> New Customer
                                </a>
                                <button type="button" class="btn btn-outline-secondary" id="refreshCustomers">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Selection Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Product Details</h5>
                </div>
                <div class="card-body">
                    <!-- Product Type and Options -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Select the type of product you want to quote">Product Type</label>
                            <select class="form-select" id="productType">
                                <option value="">Select Type</option>
                                <?php foreach ($processedProductData as $type => $data): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo ucfirst(htmlspecialchars($type)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Size</label>
                            <select class="form-select" id="productSize" disabled>
                                <option value="">Select Size</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Model</label>
                            <select class="form-select" id="productModel" disabled>
                                <option value="">Select Model</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Color</label>
                            <select class="form-select" id="stoneColor">
                                <option value="">Select Color</option>
                                <?php foreach ($stone_colors as $color): ?>
                                    <option value="<?php echo $color['id']; ?>" data-increase="<?php echo $color['price_increase']; ?>">
                                        <?php echo htmlspecialchars($color['color_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Special Monument</label>
                            <select class="form-select" id="specialMonument">
                                <option value="">Select Special Monument</option>
                                <?php foreach ($special_monuments as $monument): ?>
                                    <option value="<?php echo $monument['id']; ?>" 
                                            data-increase="<?php echo $monument['price_increase_percentage']; ?>">
                                        <?php echo htmlspecialchars($monument['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dimensions -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Length (inches)</label>
                            <input type="number" class="form-control" id="length" name="length" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Breadth (inches)</label>
                            <input type="number" class="form-control" id="breadth" name="breadth" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                        </div>
                    </div>

                    <!-- Calculations -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Overview of selected products and total cost">Product Summary</h6>
                                    
                                    <!-- Measurements -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Square Feet</span>
                                            <span id="sqft">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Cubic Feet</span>
                                            <span id="cubicFeet">0.00</span>
                                        </div>
                                    </div>

                                    <!-- Price Breakdown -->
                                    <div class="price-breakdown border-top pt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Base Price</span>
                                            <span id="basePrice">$0.00</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold">Total Price</span>
                                            <span class="fw-bold fs-5" id="totalPrice">$0.00</span>
                                        </div>
                                        
                                        <!-- Add to Cart Button -->
                                        <button type="button" class="btn btn-primary w-100" id="addToCartBtn">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart Section -->
                        <div class="col-md-8">
                            <!-- Cart Table -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="List of items added to the current quote">Cart Items</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>SPL MONUMENT</th>
                                                    <th>Model</th>
                                                    <th>Color</th>
                                                    <th>Dimensions</th>
                                                    <th>Qty</th>
                                                    <th>SQFT</th>
                                                    <th class="text-end">Cu.Ft</th>
                                                    <th class="text-end">Base Price</th>
                                                    <th class="text-end">Total Price</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cartTableBody">
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="7" class="text-end"><strong>Totals:</strong></td>
                                                    <td class="text-end" id="cartCubicFtTotal">0.00</td>
                                                    <td></td>
                                                    <td class="text-end"><strong id="cartTotal">$0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="11">
                                                        <div id="containerWarning" class="alert alert-warning d-none mb-0">
                                                            <i class="bi bi-exclamation-triangle"></i> 
                                                            Warning: Current items are below 90% container capacity (205-210 cubic ft). 
                                                            Consider adding more items to optimize shipping costs.
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-center py-3">
                                    <button type="button" class="btn btn-success btn-lg" id="generateQuoteBtn" disabled>
                                        <i class="bi bi-file-earmark-text"></i> Generate Quote
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Commission Modal -->
<div class="modal fade" id="commissionModal" tabindex="-1" aria-labelledby="commissionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commissionModalLabel">Set Commission Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="finalCommissionRate" class="form-label">Commission Rate (%)</label>
                    <input type="number" class="form-control" id="finalCommissionRate" min="0" max="100" value="0" step="0.01">
                </div>
                <div class="mb-3">
                    <h6>Quote Summary:</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-end" id="modalSubtotal">$0.00</td>
                            </tr>
                            <tr>
                                <td>Commission Amount:</td>
                                <td class="text-end" id="modalCommission">$0.00</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total:</td>
                                <td class="text-end" id="modalTotal">$0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="finalizeQuoteBtn">Generate Quote</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize data from PHP
    window.QUOTE_DATA = {
        productData: <?php echo $productDataJson; ?>,
        quoteData: <?php echo $quoteDataJson; ?>
    };
    
    // Debug log the data
    console.log('Initialized Quote Data:', window.QUOTE_DATA);
</script>
<script src="js/productCalculations.js"></script>
<script src="js/quote.js"></script>
</body>
</html>
