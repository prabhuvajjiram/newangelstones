<?php
require_once 'session_check.php';
requireLogin();  // This will redirect to login.php if not logged in

require_once 'includes/config.php';

// Get customer ID and quote ID from URL
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;

// Initialize variables
$customer = null;
$quote = null;
$quote_items = [];

// Function to get products by type (only for sertop and base)
function getProductsByType($type = '') {
    global $pdo;
    try {
        if ($type !== 'sertop' && $type !== 'base') {
            return [];
        }

        $table_name = strtolower($type) . '_products';
        $stmt = $pdo->prepare("SELECT id, model, size_inches as size, base_price FROM {$table_name} ORDER BY model, size_inches");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize products by size
        $organized = [];
        foreach ($products as $product) {
            $size = isset($product['size']) ? (string)$product['size'] : '0';
            if (!isset($organized[$size])) {
                $organized[$size] = [];
            }
            $organized[$size][] = [
                'id' => (int)$product['id'],
                'model' => $product['model'],
                'base_price' => (float)$product['base_price']
            ];
        }
        
        return $organized;
    } catch (PDOException $e) {
        error_log("Error fetching {$type} products: " . $e->getMessage());
        return [];
    }
}

// Fetch stone colors
try {
    $stmt = $pdo->prepare("SELECT id, color_name, price_increase_percentage as price_increase FROM stone_color_rates ORDER BY color_name");
    $stmt->execute();
    $stone_colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching stone colors: " . $e->getMessage());
    $stone_colors = [];
}

// Get marker products from database
try {
    $stmt = $pdo->prepare("SELECT id, model, square_feet as size, base_price FROM marker_products ORDER BY square_feet, model");
    $stmt->execute();
    $marker_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize marker products by size
    $marker_products = [];
    foreach ($marker_results as $product) {
        $size = (string)$product['size'];
        if (!isset($marker_products[$size])) {
            $marker_products[$size] = [];
        }
        $marker_products[$size][] = [
            'id' => (int)$product['id'],
            'model' => $product['model'],
            'base_price' => (float)$product['base_price']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching marker products: " . $e->getMessage());
    $marker_products = [];
}

// Get slant products from database
try {
    $stmt = $pdo->prepare("SELECT id, model, size_inches as size, base_price FROM slant_products ORDER BY model");
    $stmt->execute();
    $slant_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize slant products by size
    $slant_products = [];
    foreach ($slant_results as $product) {
        $size = (string)$product['size'];
        if (!isset($slant_products[$size])) {
            $slant_products[$size] = [];
        }
        $slant_products[$size][] = [
            'id' => (int)$product['id'],
            'model' => $product['model'],
            'base_price' => (float)$product['base_price']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching slant products: " . $e->getMessage());
    $slant_products = [];
}

// Fetch sertop and base products from database
$sertop_products = getProductsByType('sertop');
$base_products = getProductsByType('base');

// Debug output
error_log("Initialized products: " . json_encode([
    'sertop' => $sertop_products,
    'base' => $base_products,
    'slant' => $slant_products,
    'marker' => $marker_products
]));

// Fetch existing quote data if editing
if ($quote_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($quote) {
            // Fetch quote items
            $stmt = $pdo->prepare("SELECT qi.*, scr.color_name 
                FROM quote_items qi 
                LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id 
                WHERE qi.quote_id = ?");
            $stmt->execute([$quote_id]);
            $quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching quote: " . $e->getMessage());
    }
}

// If customer_id is provided, fetch customer details
if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching customer: " . $e->getMessage());
    }
}

// Fetch commission rates
try {
    $stmt = $pdo->query("SELECT * FROM commission_rates ORDER BY rate_name");
    $commission_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching commission rates: " . $e->getMessage());
    $commission_rates = [];
}

// Fetch all necessary data for the form
$price_components = [];
try {
    $stmt = $pdo->query("SELECT * FROM price_components ORDER BY component_name");
    $price_components = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching price components: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $quote_id ? 'Edit' : 'Generate'; ?> Quote - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2><?php echo $quote_id ? 'Edit' : 'Generate'; ?> Quote</h2>
                <?php if ($customer): ?>
                    <p class="text-muted"><?php echo $quote_id ? 'Editing' : 'Creating'; ?> quote for: <?php echo htmlspecialchars($customer['name']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form id="quoteForm">
                    <?php if ($quote_id): ?>
                        <input type="hidden" name="quote_id" value="<?php echo $quote_id; ?>">
                    <?php endif; ?>
                    <?php if ($customer): ?>
                        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="customer" class="form-label">Select Customer</label>
                            <select class="form-select" id="customer" name="customer_id" required>
                                <option value="">Select a customer</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, name, email FROM customers ORDER BY name");
                                    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($customers as $customer) {
                                        echo '<option value="' . $customer['id'] . '">' . htmlspecialchars($customer['name']) . ' (' . htmlspecialchars($customer['email']) . ')</option>';
                                    }
                                } catch (PDOException $e) {
                                    error_log("Error fetching customers: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Product Selection -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Product Selection</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Product Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="SERTOP">SERTOP</option>
                                        <option value="BASE">BASE</option>
                                        <option value="MARKER">MARKER</option>
                                        <option value="SLANT">SLANT</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="size" class="form-label">Size</label>
                                    <select class="form-select" id="size" name="size" required>
                                        <option value="">Select Size</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">Model</label>
                                    <select class="form-select" id="model" name="model" required>
                                        <option value="">Select Model</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Stone Color</label>
                                    <select class="form-select" id="color" name="color" required>
                                        <option value="">Select Color</option>
                                        <?php foreach ($stone_colors as $color): ?>
                                            <option value="<?php echo htmlspecialchars($color['id']); ?>" 
                                                    data-increase="<?php echo htmlspecialchars($color['price_increase']); ?>">
                                                <?php echo htmlspecialchars($color['color_name']) . ' (+' . number_format($color['price_increase'], 2) . '%)'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="length" class="form-label">Length (inches)</label>
                                    <input type="number" class="form-control" id="length" name="length" min="0" step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="breadth" class="form-label">Breadth (inches)</label>
                                    <input type="number" class="form-control" id="breadth" name="breadth" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sqft" class="form-label">Square Feet</label>
                                    <div class="form-control bg-light" id="sqft" readonly>0.00</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cubicFeet" class="form-label">Cubic Feet</label>
                                    <div class="form-control bg-light" id="cubicFeet" readonly>0.00</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="basePrice" class="form-label">Base Price</label>
                                    <div class="form-control bg-light" id="basePrice" readonly>$0.00</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="totalPrice" class="form-label">Total Price</label>
                                    <div class="form-control bg-light" id="totalPrice" readonly>$0.00</div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-primary" id="addToCartBtn">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cart -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Cart</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="cartTable">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Model</th>
                                            <th>Color</th>
                                            <th>Dimensions</th>
                                            <th>Quantity</th>
                                            <th>Sqft</th>
                                            <th>Cubic Feet</th>
                                            <th>Base Price</th>
                                            <th>Total Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Commission Rate -->
                            <div class="mb-3">
                                <label for="commissionRate" class="form-label">Commission Rate (%)</label>
                                <select class="form-select" id="commissionRate" name="commissionRate">
                                    <?php foreach ($commission_rates as $rate): ?>
                                        <option value="<?php echo $rate['percentage']; ?>">
                                            <?php echo $rate['rate_name']; ?> (<?php echo $rate['percentage']; ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" id="generateQuoteBtn" class="btn btn-primary">
                                    Generate Quote
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
    <script>
        $(document).ready(function() {
            // Initialize cart if not exists
            window.cart = window.cart || [];

            // Initialize product data
            window.productData = {
                sertop: <?php echo json_encode($sertop_products); ?>,
                base: <?php echo json_encode($base_products); ?>,
                slant: <?php echo json_encode($slant_products); ?>,
                marker: <?php echo json_encode($marker_products); ?>
            };
            
            console.log('Initialized product data:', window.productData);

            // Function to get size display text based on product type
            function getSizeDisplayText(type, size) {
                switch(type.toLowerCase()) {
                    case 'marker':
                        return size + ' SQFT';
                    default:
                        return size + ' inch';
                }
            }

            // Type change handler
            $('#type').change(function() {
                const type = $(this).val();
                const sizeSelect = $('#size');
                
                // Reset and populate size dropdown
                sizeSelect.empty().append($('<option>', {
                    value: '',
                    text: 'Select Size'
                }));
                
                if (!type) return;
                
                const typeKey = type.toLowerCase();
                console.log('Type changed to:', typeKey);

                if (typeKey === 'marker') {
                    const sizes = Object.keys(window.productData.marker || {});
                    sizes.forEach(size => {
                        sizeSelect.append($('<option>', {
                            value: size,
                            text: size + ' SQFT'
                        }));
                    });
                } else {
                    // Handle all other products including slant
                    const products = window.productData[typeKey] || {};
                    const sizes = Object.keys(products);
                    sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
                    
                    sizes.forEach(size => {
                        sizeSelect.append($('<option>', {
                            value: size,
                            text: size + ' inch'
                        }));
                    });
                }

                // Reset other fields
                $('#model').empty().append('<option value="">Select Model</option>');
                resetFields();
            });

            // Size change handler
            $('#size').change(function() {
                const type = $('#type').val();
                const size = $(this).val();
                
                if (type && size) {
                    updateModels(type, size);
                } else {
                    $('#model').empty().append('<option value="">Select Model</option>');
                }
                resetFields();
            });

            function updateModels(type, size) {
                console.log('Updating models for:', { type, size });
                const modelSelect = $('#model');
                const typeKey = type.toLowerCase();
                
                // Reset model dropdown
                modelSelect.empty().append($('<option>', {
                    value: '',
                    text: 'Select Model'
                }));

                let products;
                switch(type.toLowerCase()) {
                    case 'sertop':
                        products = <?php echo json_encode($sertop_products); ?>;
                        break;
                    case 'base':
                        products = <?php echo json_encode($base_products); ?>;
                        break;
                    case 'marker':
                        products = <?php echo json_encode($marker_products); ?>;
                        break;
                    case 'slant':
                        products = <?php echo json_encode($slant_products); ?>;
                        break;
                }

                console.log('Models for ' + type + ' size ' + size + ':', products);
                
                if (Array.isArray(products[size])) {
                    products[size].forEach(product => {
                        modelSelect.append($('<option>', {
                            value: product.model,
                            text: product.model
                        }));
                    });
                }
            }

            // Model change handler
            $('#model').change(function() {
                calculateMeasurements();
            });

            // Input change handlers
            $('#length, #breadth, #quantity, #color').change(function() {
                calculateMeasurements();
            });

            // Separate calculation functions for each product type
            function calculateSertopMeasurements(product, length, breadth, size, quantity) {
                const sqft = (length * breadth) / 144;
                const cubicFeet = (length * breadth * parseFloat(size)) / 1728 * quantity;
                const basePrice = product.base_price * sqft;
                
                console.log('SERTOP Calculations:', {
                    length,
                    breadth,
                    size,
                    sqft,
                    cubicFeet,
                    basePrice,
                    pricePerSqft: product.base_price
                });
                
                return { sqft, cubicFeet, basePrice };
            }

            function calculateBaseMeasurements(product, length, breadth, size, quantity) {
                // For base products (same as SERTOP):
                // Square feet = L * B / 144
                // Cubic feet = L * B * size/1728 * quantity
                // Base price = price per sqft * square feet
                const sqft = (length * breadth) / 144;
                const cubicFeet = (length * breadth * parseFloat(size)) / 1728 * quantity;
                const basePrice = product.base_price * sqft;

                console.log('BASE Calculations:', {
                    length,
                    breadth,
                    size,
                    sqft,
                    cubicFeet,
                    basePrice,
                    pricePerSqft: product.base_price
                });
                
                return { sqft, cubicFeet, basePrice };
            }

            function calculateMarkerMeasurements(product, length, breadth, size, quantity) {
                // For markers:
                // Square feet = L * B / 144
                // Cubic feet = L * B * 4/1728 * quantity (fixed height of 4 inches)
                // Base price = base_price * square feet
                const sqft = (length * breadth) / 144;
                const cubicFeet = (length * breadth * 4) / 1728 * quantity;
                const basePrice = product.base_price * sqft;

                console.log('MARKER Calculations:', {
                    length,
                    breadth,
                    size,
                    sqft,
                    cubicFeet,
                    basePrice,
                    pricePerSqft: product.base_price,
                    fixedHeight: 4
                });
                
                return { sqft, cubicFeet, basePrice };
            }

            function calculateSlantMeasurements(product, length, breadth, size, quantity) {
                // For slant products:
                // Square feet = L * B / 144
                // Cubic feet = L * B * size/1728 * quantity
                // Base price = base_price * square feet
                const sqft = (length * breadth) / 144;
                const cubicFeet = (length * breadth * parseFloat(size)) / 1728 * quantity;
                const basePrice = product.base_price * sqft;

                console.log('SLANT Calculations:', {
                    length,
                    breadth,
                    size,
                    sqft,
                    cubicFeet,
                    basePrice,
                    pricePerSqft: product.base_price
                });
                
                return { sqft, cubicFeet, basePrice };
            }

            function calculateMeasurements() {
                const type = $('#type').val();
                const size = $('#size').val();
                const model = $('#model').val();
                const length = parseFloat($('#length').val()) || 0;
                const breadth = parseFloat($('#breadth').val()) || 0;
                const quantity = parseInt($('#quantity').val()) || 1;
                const colorOption = $('#color option:selected');
                const colorIncrease = parseFloat(colorOption.data('increase')) || 0;

                if (!type || !size || !model || !length || !breadth) {
                    resetCalculations();
                    return;
                }

                // Find the product and its base price
                const products = window.productData[type.toLowerCase()][size] || [];
                const product = products.find(p => p.model === model);

                if (!product) {
                    resetCalculations();
                    return;
                }

                // Calculate measurements based on product type
                let measurements;
                switch (type.toLowerCase()) {
                    case 'sertop':
                        measurements = calculateSertopMeasurements(product, length, breadth, size, quantity);
                        break;
                    case 'base':
                        measurements = calculateBaseMeasurements(product, length, breadth, size, quantity);
                        break;
                    case 'marker':
                        measurements = calculateMarkerMeasurements(product, length, breadth, size, quantity);
                        break;
                    case 'slant':
                        measurements = calculateSlantMeasurements(product, length, breadth, size, quantity);
                        break;
                    default:
                        resetCalculations();
                        return;
                }

                // Apply color increase and quantity to get total price
                const colorMultiplier = 1 + (colorIncrease / 100);
                const totalPrice = measurements.basePrice * colorMultiplier * quantity;

                // Log final calculations
                console.log('Final Calculations:', {
                    type,
                    measurements,
                    colorMultiplier,
                    totalPrice,
                    product
                });

                // Update display
                $('#sqft').text(measurements.sqft.toFixed(2));
                $('#cubicFeet').text(measurements.cubicFeet.toFixed(2));
                $('#basePrice').text('$' + measurements.basePrice.toFixed(2));
                $('#totalPrice').text('$' + totalPrice.toFixed(2));
            }

            function resetCalculations() {
                $('#sqft').text('0.00');
                $('#cubicFeet').text('0.00');
                $('#basePrice').text('$0.00');
                $('#totalPrice').text('$0.00');
            }

            // Add to Cart button handler
            $('#addToCartBtn').click(function(e) {
                e.preventDefault();
                
                const type = $('#type').val();
                const size = $('#size').val();
                const model = $('#model').val();
                const colorId = parseInt($('#color').val()) || 0;
                const length = parseFloat($('#length').val()) || 0;
                const breadth = parseFloat($('#breadth').val()) || 0;
                const quantity = parseInt($('#quantity').val()) || 1;

                if (!type || !model || !length || !breadth) {
                    alert('Please fill in all required fields');
                    return;
                }

                const colorName = colorId ? $('#color option:selected').text() : 'Black (+0.00%)';
                
                const item = {
                    type: type,
                    size: size,
                    model: model,
                    color_id: colorId,
                    color_name: colorName,
                    length: length,
                    breadth: breadth,
                    quantity: quantity,
                    sqft: parseFloat($('#sqft').text()),
                    cubic_feet: parseFloat($('#cubicFeet').text()),
                    base_price: parseFloat($('#basePrice').text().slice(1)),
                    total_price: parseFloat($('#totalPrice').text().slice(1))
                };

                // Add to cart array
                window.cart.push(item);
                
                // Update cart table
                updateCartTable();
                
                // Reset form
                resetFields();
                $('#type').val('').trigger('change');
                $('#color').val($('#color option:first').val());
            });

            function updateCartTable() {
                const tbody = $('#cartTable tbody');
                tbody.empty();
                
                let subtotal = 0;
                window.cart.forEach((item, index) => {
                    const row = $('<tr>');
                    row.append($('<td>').text(item.type));
                    row.append($('<td>').text(item.size));
                    row.append($('<td>').text(item.model));
                    row.append($('<td>').text(item.color_name));
                    row.append($('<td>').text(item.length + ' × ' + item.breadth));
                    row.append($('<td>').text(item.quantity));
                    row.append($('<td>').text(item.sqft.toFixed(2)));
                    row.append($('<td>').text(item.cubic_feet.toFixed(2)));
                    row.append($('<td>').text('$' + item.base_price.toFixed(2)));
                    row.append($('<td>').text('$' + item.total_price.toFixed(2)));
                    
                    const removeBtn = $('<button>')
                        .addClass('btn btn-sm btn-danger')
                        .html('<i class="bi bi-trash"></i>')
                        .click(() => {
                            window.cart.splice(index, 1);
                            updateCartTable();
                        });
                    
                    row.append($('<td>').append(removeBtn));
                    tbody.append(row);
                    
                    subtotal += parseFloat(item.total_price);
                });
                
                // Update totals
                const commissionRate = parseFloat($('#commissionRate').val()) || 0;
                const commission = subtotal * (commissionRate / 100);
                const total = subtotal + commission;
                
                $('#subtotal').html('$' + subtotal.toFixed(2));
                $('#commission').html('$' + commission.toFixed(2));
                $('#total').html('$' + total.toFixed(2));
            }

            function resetFields() {
                $('#length').val('');
                $('#breadth').val('');
                $('#quantity').val('1');
                $('#sqft').text('0.00');
                $('#cubicFeet').text('0.00');
                $('#basePrice').text('$0.00');
                $('#totalPrice').text('$0.00');
            }

            // Save and Generate Quote button handler
            $('#generateQuoteBtn').click(function(e) {
                e.preventDefault();
                
                if (window.cart.length === 0) {
                    alert('Please add items to the cart before generating a quote');
                    return;
                }

                const quoteData = {
                    customer_id: $('#customer').val() || <?php echo $customer_id ?: 0; ?>,
                    items: window.cart,
                    commission_rate: $('#commissionRate').val()
                };

                $.ajax({
                    url: 'save_quote.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(quoteData),
                    success: function(response) {
                        if (response.success) {
                            window.open('generate_pdf.php?id=' + response.quote_id, '_blank');
                        } else {
                            alert('Error saving quote: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error saving quote: ' + error);
                    }
                });
            });

            // Event handlers for dropdown population
            $('#type').change(function() {
                const type = $(this).val();
                const sizeSelect = $('#size');
                sizeSelect.empty();
                
                // Add default option
                sizeSelect.append($('<option>', {
                    value: '',
                    text: 'Select Size'
                }));
                
                if (type) {
                    let products;
                    switch(type.toLowerCase()) {
                        case 'marker':
                            products = <?php echo json_encode($marker_products); ?>;
                            break;
                        case 'sertop':
                            products = <?php echo json_encode($sertop_products); ?>;
                            break;
                        case 'base':
                            products = <?php echo json_encode($base_products); ?>;
                            break;
                        case 'slant':
                            products = <?php echo json_encode($slant_products); ?>;
                            break;
                    }
                    
                    if (products) {
                        const sizes = Object.keys(products);
                        console.log('Available sizes:', sizes);
                        
                        sizes.sort((a, b) => parseFloat(a) - parseFloat(b));
                        
                        sizes.forEach(size => {
                            sizeSelect.append($('<option>', {
                                value: size,
                                text: getSizeDisplayText(type, size)
                            }));
                        });
                    }
                }
                
                // Reset dependent fields
                $('#model').empty().append('<option value="">Select Model</option>');
                resetFields();
            });

            $('#size').change(function() {
                const type = $('#type').val();
                const size = $(this).val();
                
                if (type && size) {
                    updateModels(type, size);
                } else {
                    $('#model').empty().append('<option value="">Select Model</option>');
                }
                resetFields();
            });

            function updateModels(type, size) {
                const modelSelect = $('#model');
                modelSelect.empty();
                
                // Add default option
                modelSelect.append($('<option>', {
                    value: '',
                    text: 'Select Model'
                }));

                let products;
                switch(type.toLowerCase()) {
                    case 'sertop':
                        products = <?php echo json_encode($sertop_products); ?>;
                        break;
                    case 'base':
                        products = <?php echo json_encode($base_products); ?>;
                        break;
                    case 'marker':
                        products = <?php echo json_encode($marker_products); ?>;
                        break;
                    case 'slant':
                        products = <?php echo json_encode($slant_products); ?>;
                        break;
                }

                console.log('Models for ' + type + ' size ' + size + ':', products);
                
                if (Array.isArray(products[size])) {
                    products[size].forEach(product => {
                        modelSelect.append($('<option>', {
                            value: product.model,
                            text: product.model
                        }));
                    });
                }
            }

            <?php if ($quote_items): ?>
            // Load existing quote items into cart
            <?php foreach ($quote_items as $item): ?>
            window.cart.push({
                type: '<?php echo strtoupper($item['product_type']); ?>',
                size: '<?php echo $item['size']; ?>',
                model: '<?php echo $item['model']; ?>',
                color_id: <?php echo $item['color_id']; ?>,
                length: <?php echo $item['length']; ?>,
                breadth: <?php echo $item['breadth']; ?>,
                quantity: <?php echo $item['quantity']; ?>,
                cubic_feet: <?php echo $item['cubic_feet']; ?>,
                base_price: <?php echo $item['base_price']; ?>,
                total_price: <?php echo $item['total_price']; ?>
            });

            // Pre-select values in the form for the last item (for editing)
            $('#type').val('<?php echo strtoupper($item['product_type']); ?>');
            
            // Wait for size options to be populated
            setTimeout(() => {
                $('#size').val('<?php echo $item['size']; ?>');
                
                // Wait for model options to be populated
                setTimeout(() => {
                    $('#model').val('<?php echo $item['model']; ?>');
                    $('#color').val(<?php echo $item['color_id']; ?>);
                    $('#length').val(<?php echo $item['length']; ?>);
                    $('#breadth').val(<?php echo $item['breadth']; ?>);
                    $('#quantity').val(<?php echo $item['quantity']; ?>);
                    
                    // Calculate dimensions
                    updatePrice();
                }, 100);
            }, 100);
            <?php endforeach; ?>

            // Update form fields with quote data
            $('#commissionRate').val('<?php echo $quote['commission_rate']; ?>');
            
            // Update cart display
            updateCartTable();
            <?php endif; ?>
        });
    </script>
</body>
</html>
