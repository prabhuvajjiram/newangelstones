<?php
require_once 'includes/config.php';
requireLogin();

// Get customer ID and quote ID from URL
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$quote_id = isset($_GET['quote_id']) ? (int)$_GET['quote_id'] : 0;

// Initialize variables
$customer = null;
$quote = null;
$quote_items = [];

// Fetch existing quote data if editing
if ($quote_id) {
    $stmt = $conn->prepare("SELECT * FROM quotes WHERE id = ?");
    $stmt->bind_param('i', $quote_id);
    $stmt->execute();
    $quote = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($quote) {
        $customer_id = $quote['customer_id'];
        
        // Fetch quote items with proper field names
        $stmt = $conn->prepare("
            SELECT qi.*, 
                CASE 
                    WHEN sp.id IS NOT NULL THEN 'SERTOP'
                    WHEN mp.id IS NOT NULL THEN 'MARKER'
                    WHEN bp.id IS NOT NULL THEN 'BASE'
                    WHEN slp.id IS NOT NULL THEN 'SLANT'
                END as product_type,
                COALESCE(sp.model, mp.model, bp.model, slp.model) as model,
                CASE 
                    WHEN sp.id IS NOT NULL THEN sp.size_inches
                    WHEN mp.id IS NOT NULL THEN mp.square_feet
                    WHEN bp.id IS NOT NULL THEN bp.size_inches
                    WHEN slp.id IS NOT NULL THEN slp.model
                END as size
            FROM quote_items qi
            LEFT JOIN sertop_products sp ON qi.product_id = sp.id AND qi.product_type = 'SERTOP'
            LEFT JOIN marker_products mp ON qi.product_id = mp.id AND qi.product_type = 'MARKER'
            LEFT JOIN base_products bp ON qi.product_id = bp.id AND qi.product_type = 'BASE'
            LEFT JOIN slant_products slp ON qi.product_id = slp.id AND qi.product_type = 'SLANT'
            WHERE qi.quote_id = ?
        ");
        $stmt->bind_param('i', $quote_id);
        $stmt->execute();
        $quote_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// If quote_id is provided, fetch quote and customer details
if ($quote_id) {
    $stmt = $conn->prepare("
        SELECT q.*, c.* 
        FROM quotes q
        JOIN customers c ON q.customer_id = c.id
        WHERE q.id = ?
    ");
    $stmt->bind_param("i", $quote_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quote = $result->fetch_assoc();
    $stmt->close();

    if ($quote) {
        $customer_id = $quote['customer_id'];
        $customer = [
            'id' => $quote['customer_id'],
            'name' => $quote['name'],
            'email' => $quote['email'],
            'phone' => $quote['phone']
        ];

        // Fetch quote items
        $stmt = $conn->prepare("
            SELECT qi.*, scr.color_name
            FROM quote_items qi
            LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id
            WHERE qi.quote_id = ?
        ");
        $stmt->bind_param("i", $quote_id);
        $stmt->execute();
        $quote_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
// If only customer_id is provided, fetch customer details
else if ($customer_id) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
}

// Function to get products by type
function getProductsByType($conn, $type) {
    $table_name = strtolower($type) . '_products';
    
    // Different size field names and queries for different product types
    switch ($type) {
        case 'marker':
            $query = "SELECT id, model, square_feet, base_price FROM {$table_name} ORDER BY model";
            break;
        case 'slant':
            // Slant products don't have a size field, only model and base_price
            $query = "SELECT id, model, base_price FROM {$table_name} ORDER BY model";
            break;
        default:
            $query = "SELECT id, model, size_inches, base_price FROM {$table_name} ORDER BY model";
    }
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // For slant products, use model as the size key
        $size = isset($row['square_feet']) ? $row['square_feet'] : 
               (isset($row['size_inches']) ? $row['size_inches'] : $row['model']);
               
        if (!isset($products[$size])) {
            $products[$size] = [];
        }
        $products[$size][] = [
            'id' => $row['id'],
            'model' => $row['model'],
            'base_price' => $row['base_price'],
            'square_feet' => isset($row['square_feet']) ? $row['square_feet'] : null
        ];
    }
    
    return $products;
}

// Get products by type
$sertop_products = getProductsByType($conn, 'sertop');
$marker_products = getProductsByType($conn, 'marker');
$base_products = getProductsByType($conn, 'base');
$slant_products = getProductsByType($conn, 'slant');

// Function to output product data as JSON
function outputProductData($products) {
    return json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

// Fetch all necessary data for the form
$stone_colors = [];
$result = $conn->query("SELECT * FROM stone_color_rates ORDER BY color_name");
while ($row = $result->fetch_assoc()) {
    $stone_colors[] = $row;
}

$commission_rates = [];
$result = $conn->query("SELECT * FROM commission_rates ORDER BY rate_name");
while ($row = $result->fetch_assoc()) {
    $commission_rates[] = $row;
}

$price_components = [];
$result = $conn->query("SELECT * FROM price_components ORDER BY component_name");
while ($row = $result->fetch_assoc()) {
    $price_components[] = $row;
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
                                $result = $conn->query("SELECT id, name, email FROM customers ORDER BY name");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . ' (' . htmlspecialchars($row['email']) . ')</option>';
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
                                    <label for="productType" class="form-label">Product Type</label>
                                    <select class="form-select" id="productType" name="productType">
                                        <option value="">Select Product Type</option>
                                        <option value="sertop">SERTOP</option>
                                        <option value="marker">MARKER</option>
                                        <option value="base">BASE</option>
                                        <option value="slant">SLANT</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="productSize" class="form-label">Size</label>
                                    <select class="form-select" id="productSize" name="productSize">
                                        <option value="">Select Size</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="productModel" class="form-label">Model</label>
                                    <select class="form-select" id="productModel" name="productModel">
                                        <option value="">Select Model</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stoneColor" class="form-label">Stone Color</label>
                                    <select class="form-select" id="stoneColor" name="stoneColor">
                                        <option value="">Select Color</option>
                                        <?php foreach ($stone_colors as $color): ?>
                                            <option value="<?php echo $color['id']; ?>" 
                                                    data-price-increase="<?php echo $color['price_increase_percentage']; ?>">
                                                <?php echo htmlspecialchars($color['color_name']); ?> 
                                                (+<?php echo $color['price_increase_percentage']; ?>%)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="length" class="form-label">Length (inches)</label>
                                    <input type="number" class="form-control" id="length" name="length" step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="breadth" class="form-label">Breadth (inches)</label>
                                    <input type="number" class="form-control" id="breadth" name="breadth" step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sqft" class="form-label">Square Feet</label>
                                    <input type="text" class="form-control" id="sqft" name="sqft" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cubicFeet" class="form-label">Cubic Feet</label>
                                    <input type="text" class="form-control" id="cubicFeet" name="cubicFeet" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price</label>
                                    <input type="text" class="form-control" id="price" name="price" readonly>
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
                                <table class="table" id="cartTable">
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
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Commission Rate Selection -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="commissionRate">Commission Rate (%)</label>
                                        <select class="form-select" id="commissionRate" name="commissionRate">
                                            <option value="0">0%</option>
                                            <option value="5">5%</option>
                                            <option value="10">10%</option>
                                            <option value="15">15%</option>
                                            <option value="20">20%</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-primary" id="saveQuoteBtn">Generate Quote</button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col text-end">
                            <button type="button" class="btn btn-primary btn-lg" id="saveQuoteBtn">
                                <i class="bi bi-save"></i> Save Quote
                            </button>
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

            // Store the product data
            const productData = <?php echo json_encode([
                'sertop' => $sertop_products,
                'marker' => $marker_products,
                'base' => $base_products,
                'slant' => $slant_products
            ], JSON_PRETTY_PRINT); ?>;
            
            console.log('Product Data:', productData);
            
            function clearProductForm(clearType = false) {
                if (clearType) {
                    $('#productType').val('');
                }
                $('#productSize').val('');
                $('#productModel').val('');
                $('#length').val('');
                $('#breadth').val('');
                $('#quantity').val('1');
                $('#sqft').val('');
                $('#cubicFeet').val('');
                $('#price').val('');
            }

            function resetDropdowns() {
                const sizeSelect = $('#productSize');
                const modelSelect = $('#productModel');
                
                // Clear dropdowns
                sizeSelect.empty().append('<option value="">Select Size</option>');
                modelSelect.empty().append('<option value="">Select Model</option>');
            }

            // Add to Cart button handler
            $('#addToCartBtn').click(function(e) {
                e.preventDefault();
                
                const type = $('#productType').val();
                const size = $('#productSize').val();
                const model = $('#productModel').val();
                const colorId = $('#stoneColor').val();
                const colorName = $('#stoneColor option:selected').text();
                const length = parseFloat($('#length').val());
                const breadth = parseFloat($('#breadth').val());
                const quantity = parseInt($('#quantity').val());
                const sqft = parseFloat($('#sqft').val());
                const cubicFeet = parseFloat($('#cubicFeet').val());
                const price = parseFloat($('#price').val());

                if (!type || !size || !model || !colorId || !length || !breadth || !quantity) {
                    alert('Please fill in all required fields');
                    return;
                }

                // Check for duplicate items
                const isDuplicate = window.cart.some(item => 
                    item.type === type &&
                    item.model === model &&
                    item.size === size &&
                    item.color_id === colorId &&
                    item.length === length &&
                    item.breadth === breadth &&
                    item.totalPrice === price &&
                    item.color === colorName.trim()
                );

                if (isDuplicate) {
                    alert('This item is already in the cart. Please modify the existing item or change the specifications.');
                    return;
                }

                const cartItem = {
                    type: type,
                    size: size,
                    model: model,
                    color_id: colorId,
                    color: colorName.trim(),
                    length: length,
                    breadth: breadth,
                    dimensions: length + '" x ' + breadth + '"',
                    quantity: quantity,
                    sqft: sqft,
                    cubicFeet: cubicFeet,
                    totalPrice: price,
                    commission_rate: parseFloat($('#commissionRate').val()) || 0
                };

                // Add to cart
                window.cart.push(cartItem);

                // Update cart display
                updateCartTable();

                // Clear form including product type
                clearProductForm(true);
                resetDropdowns();
            });

            // Event handlers for dropdown population
            $('#productType').change(function() {
                const type = $(this).val();
                const sizeSelect = $('#productSize');
                const modelSelect = $('#productModel');
                
                resetDropdowns();
                clearProductForm();
                
                if (type && productData[type.toLowerCase()]) {
                    // Get unique sizes
                    const sizes = Object.keys(productData[type.toLowerCase()]);
                    sizes.sort((a, b) => parseFloat(a) - parseFloat(b)); // Sort numerically
                    
                    // Add sizes to dropdown
                    sizes.forEach(size => {
                        sizeSelect.append(`<option value="${size}">${size}"</option>`);
                    });
                }
            });

            $('#productSize').change(function() {
                const type = $('#productType').val();
                const size = $(this).val();
                const modelSelect = $('#productModel');
                
                // Clear model dropdown and calculations
                modelSelect.empty().append('<option value="">Select Model</option>');
                $('#sqft').val('');
                $('#cubicFeet').val('');
                $('#price').val('');
                
                if (type && size && productData[type.toLowerCase()][size]) {
                    const models = productData[type.toLowerCase()][size];
                    models.sort((a, b) => a.model.localeCompare(b.model)); // Sort alphabetically
                    
                    // Add models to dropdown
                    models.forEach(model => {
                        modelSelect.append(`<option value="${model.model}">${model.model}</option>`);
                    });
                }
            });

            $('#productModel').change(calculateDimensions);
            $('#length, #breadth, #quantity').on('change', calculateDimensions);

            <?php if ($quote_items): ?>
            // Load existing quote items into cart
            <?php foreach ($quote_items as $item): ?>
            window.cart.push({
                productId: <?php echo $item['product_id']; ?>,
                type: '<?php echo strtoupper($item['product_type']); ?>',
                model: '<?php echo $item['model']; ?>',
                size: '<?php echo $item['size']; ?>',
                colorId: <?php echo $item['color_id']; ?>,
                length: <?php echo $item['length']; ?>,
                breadth: <?php echo $item['breadth']; ?>,
                sqft: <?php echo $item['sqft']; ?>,
                cubicFeet: <?php echo $item['cubic_feet']; ?>,
                quantity: <?php echo $item['quantity']; ?>,
                price: <?php echo $item['price']; ?>
            });

            // Pre-select values in the form for the last item (for editing)
            $('#productType').val('<?php echo strtoupper($item['product_type']); ?>');
            
            // Wait for size options to be populated
            setTimeout(() => {
                $('#productSize').val('<?php echo $item['size']; ?>');
                
                // Wait for model options to be populated
                setTimeout(() => {
                    $('#productModel').val('<?php echo $item['model']; ?>');
                    $('#stoneColor').val(<?php echo $item['color_id']; ?>);
                    $('#length').val(<?php echo $item['length']; ?>);
                    $('#breadth').val(<?php echo $item['breadth']; ?>);
                    $('#quantity').val(<?php echo $item['quantity']; ?>);
                    
                    // Calculate dimensions
                    calculateDimensions();
                }, 100);
            }, 100);
            <?php endforeach; ?>

            // Update form fields with quote data
            $('#commissionRate').val('<?php echo $quote['commission_rate']; ?>');
            
            // Update cart display
            updateCartTable();
            <?php endif; ?>

            function calculateDimensions() {
                const type = $('#productType').val();
                const size = $('#productSize').val();
                const model = $('#productModel').val();
                console.log('Product type:', type);
                console.log('Product size:', size);
                console.log('Product model:', model);

                const length = parseFloat($('#length').val()) || 0;
                const breadth = parseFloat($('#breadth').val()) || 0;
                const quantity = parseInt($('#quantity').val()) || 1;

                if (length && breadth && quantity) {
                    // Calculate SQFT - same for all products
                    const sqft = (length * breadth) / 144;
                    console.log('Calculated SQFT:', sqft);
                    $('#sqft').val(sqft.toFixed(2));

                    // Calculate Cubic Feet
                    let height;
                    if (type.toUpperCase() === 'MARKER') {
                        height = 4; // Always use 4 inches for markers
                        console.log('Using marker height (4 inches) for type:', type);
                    } else {
                        height = parseFloat(size);
                        console.log('Using size as height for type:', type);
                    }
                    
                    const cubicFeet = ((length * breadth * height) / 1728) * quantity;
                    console.log('Cubic feet calc:', length, '*', breadth, '*', height, '/', 1728, '*', quantity, '=', cubicFeet);
                    $('#cubicFeet').val(cubicFeet.toFixed(2));

                    // Update price
                    updatePrice();
                }
            }

            function updatePrice() {
                const type = $('#productType').val();
                const size = $('#productSize').val();
                const model = $('#productModel').val();
                const colorId = $('#stoneColor').val();
                const quantity = parseInt($('#quantity').val()) || 1;
                const sqft = parseFloat($('#sqft').val()) || 0;
                
                if (!type || !size || !model) {
                    console.log('Missing required fields');
                    return;
                }

                // Get product data
                const typeData = productData[type.toLowerCase()];
                if (!typeData || !typeData[size]) {
                    console.log('No product data found');
                    return;
                }

                // Find the model data
                const modelData = typeData[size].find(m => m.model === model);
                if (!modelData) {
                    console.log('Model not found');
                    return;
                }

                // Get base price
                const basePrice = parseFloat(modelData.base_price) || 0;
                console.log('Base price:', basePrice);
                
                // Calculate total price using square feet for all product types
                // Multiply by quantity here since sqft is now per unit
                let totalPrice = sqft * basePrice * quantity;
                
                // Apply color increase if selected
                if (colorId) {
                    const colorOption = $('#stoneColor option:selected');
                    const priceIncrease = parseFloat(colorOption.data('price-increase')) || 0;
                    totalPrice = totalPrice * (1 + priceIncrease / 100);
                }
                
                // Update price field
                $('#price').val(totalPrice.toFixed(2));
            }

            function updateCartTable() {
                const tbody = $('#cartTable tbody');
                tbody.empty();
                
                let subtotal = 0;
                let commission = 0;
                
                window.cart.forEach((item, index) => {
                    const row = $('<tr>');
                    row.append($('<td>').text(item.type));
                    row.append($('<td>').text(item.model));
                    row.append($('<td>').text(item.size));
                    row.append($('<td>').text(item.color));
                    row.append($('<td>').text(item.dimensions));
                    row.append($('<td>').text(parseFloat(item.cubicFeet).toFixed(2)));
                    row.append($('<td>').text(item.quantity));
                    row.append($('<td>').text('$' + parseFloat(item.totalPrice).toFixed(2)));
                    
                    const deleteBtn = $('<button>')
                        .addClass('btn btn-danger btn-sm')
                        .html('<i class="bi bi-trash"></i>')
                        .click(() => {
                            window.cart.splice(index, 1);
                            updateCartTable();
                        });
                    
                    row.append($('<td>').append(deleteBtn));
                    tbody.append(row);
                    
                    subtotal += parseFloat(item.totalPrice);
                });
                
                // Update totals
                const commissionRate = parseFloat($('#commissionRate').val()) || 0;
                commission = subtotal * (commissionRate / 100);
                const total = subtotal + commission;
                
                $('#subtotal').text('$' + subtotal.toFixed(2));
                $('#commission').text('$' + commission.toFixed(2));
                $('#total').text('$' + total.toFixed(2));
            }

            // Commission rate change handler
            $('#commissionRate').change(function() {
                updateCartTable();
            });

            // Save quote button handler
            $('#saveQuoteBtn').click(function() {
                if (!window.cart || window.cart.length === 0) {
                    alert('Please add at least one item to the cart');
                    return;
                }

                let customerId = $('input[name="customer_id"]').val();
                if (!customerId) {
                    customerId = $('#customer').val();
                }
                
                if (!customerId) {
                    alert('Please select a customer');
                    return;
                }

                // Get commission rate from dropdown
                const commissionRate = parseFloat($('#commissionRate').val()) || 0;
                
                // Add commission rate to each item
                const itemsWithCommission = window.cart.map(item => ({
                    ...item,
                    commission_rate: commissionRate
                }));

                const data = {
                    quote_id: $('input[name="quote_id"]').val(),
                    customer_id: customerId,
                    items: itemsWithCommission,
                    commission_rate: commissionRate
                };

                // Send data to server
                $.ajax({
                    url: 'save_quote.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.success) {
                            alert('Quote saved successfully!');
                            window.location.href = 'quotes.php';
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error saving quote';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || response.error || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText || errorMessage;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
</body>
</html>