<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Process the form submission
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data received');
        }
        
        // Validate required fields
        if (!isset($data['product']) || !isset($data['product']['type'])) {
            throw new Exception('Missing required product information');
        }
        
        // Get base price for the selected product
        $type = strtolower($data['product']['type']);
        $size = $data['product']['size'];
        $model = $data['product']['model'];
        $length = $data['product']['length'];
        $breadth = $data['product']['breadth'];
        $quantity = $data['product']['quantity'];
        
        $basePrice = 0;
        $stmt = null;
        
        // Get color price increase percentage
        $colorStmt = $conn->prepare("SELECT price_increase_percentage FROM stone_color_rates WHERE id = ?");
        $colorId = $data['product']['colorId'];
        $colorStmt->bind_param("i", $colorId);
        
        if (!$colorStmt->execute()) {
            throw new Exception('Failed to get color price increase');
        }
        
        $colorResult = $colorStmt->get_result();
        if (!$colorRow = $colorResult->fetch_assoc()) {
            throw new Exception('Color not found');
        }
        
        $priceIncreasePercentage = $colorRow['price_increase_percentage'];
        
        // Calculate price based on product type
        switch($type) {
            case 'sertop':
                $stmt = $conn->prepare("SELECT base_price FROM sertop_products WHERE id = ?");
                $productId = $data['product']['id'];
                $stmt->bind_param("i", $productId);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to get sertop product price');
                }
                $result = $stmt->get_result();
                if (!$row = $result->fetch_assoc()) {
                    throw new Exception('Sertop product not found');
                }
                $basePrice = $row['base_price'];
                $subtotal = $basePrice * $quantity;
                break;
                
            case 'base':
                $stmt = $conn->prepare("SELECT base_price FROM base_products WHERE id = ?");
                $productId = $data['product']['id'];
                $stmt->bind_param("i", $productId);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to get base product price');
                }
                $result = $stmt->get_result();
                if (!$row = $result->fetch_assoc()) {
                    throw new Exception('Base product not found');
                }
                $basePrice = $row['base_price'];
                $subtotal = $basePrice * $quantity;
                break;
                
            case 'marker':
                $stmt = $conn->prepare("SELECT base_price FROM marker_products WHERE id = ?");
                $productId = $data['product']['id'];
                $stmt->bind_param("i", $productId);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to get marker product price');
                }
                $result = $stmt->get_result();
                if (!$row = $result->fetch_assoc()) {
                    throw new Exception('Marker product not found');
                }
                $basePrice = $row['base_price'];
                $subtotal = $basePrice * $quantity;
                break;
                
            case 'slant':
                $stmt = $conn->prepare("SELECT base_price FROM slant_products WHERE id = ?");
                $productId = $data['product']['id'];
                $stmt->bind_param("i", $productId);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to get slant product price');
                }
                $result = $stmt->get_result();
                if (!$row = $result->fetch_assoc()) {
                    throw new Exception('Slant product not found');
                }
                $basePrice = $row['base_price'];
                $subtotal = $basePrice * $quantity;
                break;
                
            default:
                throw new Exception('Invalid product type');
        }
        
        // Apply color price increase
        $increase = $subtotal * ($priceIncreasePercentage / 100);
        $totalPrice = $subtotal + $increase;
        
        // Calculate commission
        $commissionPercentage = $data['commission']['percentage'];
        $commission = $totalPrice * ($commissionPercentage / 100);
        
        echo json_encode([
            'success' => true,
            'basePrice' => $basePrice,
            'subtotal' => $subtotal,
            'colorIncrease' => $increase,
            'priceWithColor' => $totalPrice,
            'commission' => $commission,
            'total' => $totalPrice + $commission
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Fetch stone colors
$colors = [];
$result = $conn->query("SELECT * FROM stone_color_rates ORDER BY color_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $colors[] = $row;
    }
}

// Fetch commission rates
$commission_rates = [];
$result = $conn->query("SELECT * FROM commission_rates ORDER BY rate_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $commission_rates[] = $row;
    }
}

// Fetch product data for each type
$sertopProducts = $conn->query("SELECT size_inches, model, length_inches, breadth_inches, base_price FROM sertop_products ORDER BY size_inches, model");
$baseProducts = $conn->query("SELECT size_inches, model, length_inches, breadth_inches, base_price FROM base_products ORDER BY size_inches, model");
$markerProducts = $conn->query("SELECT square_feet, model, length_inches, breadth_inches, base_price FROM marker_products ORDER BY square_feet, model");
$slantProducts = $conn->query("SELECT model, length_inches, breadth_inches, base_price FROM slant_products ORDER BY model");

// Helper function to output product data as JavaScript array
function outputProductData($result, $sizeField = 'size_inches') {
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $size = isset($row[$sizeField]) ? $row[$sizeField] : 'default';
            if (!isset($data[$size])) {
                $data[$size] = [];
            }
            $data[$size][] = [
                'model' => $row['model'],
                'length' => $row['length_inches'],
                'breadth' => $row['breadth_inches'],
                'base_price' => $row['base_price']
            ];
        }
    }
    return json_encode($data);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Quote - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Angel Stones</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="quote.php">Generate Quote</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Generate Quote</h2>
                <form id="quoteForm">
                    <!-- Customer Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customerName" class="form-label">Customer Name *</label>
                                    <input type="text" class="form-control" id="customerName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="requestedBy" class="form-label">Requested By</label>
                                    <input type="text" class="form-control" id="requestedBy">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phoneNumber" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phoneNumber" pattern="[0-9\-\+\s]*">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="projectName" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="projectName">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Selection -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Product Selection</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="productType" class="form-label">Product Type</label>
                                    <select class="form-select" id="productType" required>
                                        <option value="">Select Product Type</option>
                                        <option value="sertop">SERTOP</option>
                                        <option value="base">BASE</option>
                                        <option value="marker">MARKER</option>
                                        <option value="slant">SLANT</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="productSize" class="form-label">Size</label>
                                    <select class="form-select" id="productSize" required>
                                        <option value="">Select Size</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="productModel" class="form-label">Model</label>
                                    <select class="form-select" id="productModel" required>
                                        <option value="">Select Model</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stoneColor" class="form-label">Stone Color</label>
                                    <select class="form-select" id="stoneColor" required>
                                        <option value="">Select Color</option>
                                        <?php foreach ($colors as $color): ?>
                                            <option value="<?php echo htmlspecialchars($color['id']); ?>" 
                                                    data-increase="<?php echo htmlspecialchars($color['price_increase_percentage']); ?>">
                                                <?php echo htmlspecialchars($color['color_name']); ?> 
                                                (<?php echo number_format($color['price_increase_percentage'], 1); ?>% increase)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="length" class="form-label">Length (inches)</label>
                                    <input type="number" class="form-control" id="length" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="breadth" class="form-label">Breadth (inches)</label>
                                    <input type="number" class="form-control" id="breadth" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" min="1" value="1" required>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success" id="addToCart">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Cart</h5>
                        </div>
                        <div class="card-body">
                            <div id="cartItems">
                                <p class="text-muted">No items in cart</p>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="commission" class="form-label">Commission Rate</label>
                                    <select class="form-select" id="commission" required>
                                        <option value="">Select Commission Rate</option>
                                        <option value="0" data-percentage="0">0%</option>
                                        <?php
                                        $result = $conn->query("SELECT * FROM commission_rates ORDER BY percentage");
                                        if ($result) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['id'] . '" data-percentage="' . $row['percentage'] . '">' . $row['percentage'] . '%</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="saveButton">Save Quote</button>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Quote Preview</h5>
                    </div>
                    <div class="card-body">
                        <div id="quotePreview">
                            <p class="text-muted">Fill out the form to preview your quote.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store the product data
            const productData = {
                sertop: <?php echo outputProductData($sertopProducts); ?>,
                base: <?php echo outputProductData($baseProducts); ?>,
                marker: <?php echo outputProductData($markerProducts, 'square_feet'); ?>,
                slant: <?php echo outputProductData($slantProducts); ?>
            };

            let cartItems = [];

            // Add event listeners for customer information fields
            $('#customerName, #requestedBy, #phoneNumber, #projectName').on('input', function() {
                updateQuotePreview();
            });

            // Update sizes when product type changes
            $('#productType').change(function() {
                const type = $(this).val();
                const $sizeSelect = $('#productSize');
                const $modelSelect = $('#productModel');
                
                // Reset both size and model dropdowns
                $sizeSelect.empty().append('<option value="">Select Size</option>');
                $modelSelect.empty().append('<option value="">Select Model</option>');
                
                // Reset length and breadth
                $('#length').val('');
                $('#breadth').val('');
                
                if (type && productData[type]) {
                    // For SLANT products, only show models
                    if (type === 'slant') {
                        $sizeSelect.prop('disabled', true);
                        productData[type]['default'].forEach(item => {
                            $modelSelect.append(`<option value="${item.model}" 
                                data-length="${item.length}" 
                                data-breadth="${item.breadth}">
                                ${item.model}
                            </option>`);
                        });
                    } else {
                        $sizeSelect.prop('disabled', false);
                        // Add size options
                        Object.keys(productData[type]).forEach(size => {
                            const displaySize = type === 'marker' ? `${size} sq.ft` : 
                                     type === 'slant' ? 'N/A' : `${size} inch`;
                    
                            $sizeSelect.append(`<option value="${size}">${displaySize}</option>`);
                        });
                    }
                }
            });

            // Update models when size changes
            $('#productSize').change(function() {
                const type = $('#productType').val();
                const size = $(this).val();
                const $modelSelect = $('#productModel');
                
                $modelSelect.empty().append('<option value="">Select Model</option>');
                
                // Reset length and breadth
                $('#length').val('');
                $('#breadth').val('');
                
                if (type && size && productData[type] && productData[type][size]) {
                    productData[type][size].forEach(item => {
                        $modelSelect.append(`<option value="${item.model}" 
                            data-length="${item.length}" 
                            data-breadth="${item.breadth}">
                            ${item.model}
                        </option>`);
                    });
                }
            });

            // Update length and breadth when model changes
            $('#productModel').change(function() {
                const $selected = $(this).find('option:selected');
                if ($selected.val()) {
                    $('#length').val($selected.data('length'));
                    $('#breadth').val($selected.data('breadth'));
                }
            });

            // Add to cart button handler
            $('#addToCart').click(function() {
                const type = $('#productType').val();
                const size = $('#productSize').val();
                const model = $('#productModel').val();
                const colorSelect = $('#stoneColor option:selected');
                const colorId = colorSelect.val();
                const colorName = colorSelect.text().split('(')[0].trim();
                const priceIncrease = parseFloat(colorSelect.data('increase')) || 0;
                const quantity = parseInt($('#quantity').val());
                const length = parseFloat($('#length').val());
                const breadth = parseFloat($('#breadth').val());

                if (!type || !size || !model || !colorId || !quantity || isNaN(quantity) || quantity <= 0) {
                    alert('Please fill in all required fields');
                    return;
                }

                // Add item to cart
                cartItems.push({
                    type: type,
                    size: size,
                    model: model,
                    colorId: colorId,
                    colorName: colorName,
                    priceIncrease: priceIncrease,
                    quantity: quantity,
                    length: length,
                    breadth: breadth
                });

                // Update cart display
                updateCart();
                
                // Clear form
                $('#productSize').val('');
                $('#productModel').val('');
                $('#stoneColor').val('');
                $('#quantity').val('1');
                $('#length').val('');
                $('#breadth').val('');
            });

            function updateCart() {
                const cartBody = $('#cartItems');
                cartBody.empty();

                cartItems.forEach((item, index) => {
                    cartBody.append(`
                        <tr>
                            <td>${item.type.toUpperCase()} - ${item.size}" - ${item.model}</td>
                            <td>${item.colorName} (${item.priceIncrease}% increase)</td>
                            <td>${item.quantity}</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(${index})">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    `);
                });

                // Calculate subtotal and update preview
                calculateSubtotal();
                updateQuotePreview();
            }

            function removeFromCart(index) {
                cartItems.splice(index, 1);
                updateCart();
            }

            function calculateSubtotal() {
                let subtotal = 0;
                cartItems.forEach(item => {
                    // Get base price based on product type and size
                    let basePrice = 0;
                    const products = productData[item.type];
                    if (products) {
                        const sizeProducts = item.type === 'slant' ? products['default'] : products[item.size];
                        if (sizeProducts) {
                            const product = sizeProducts.find(p => p.model === item.model);
                            if (product) {
                                basePrice = parseFloat(product.base_price);
                                item.basePrice = basePrice; // Store the base price in the item
                            }
                        }
                    }
                    
                    // Apply color price increase
                    const priceWithColor = basePrice + (basePrice * (item.priceIncrease / 100));
                    
                    // Calculate item total
                    subtotal += priceWithColor * item.quantity;
                });
                $('#subtotal').text(subtotal.toFixed(2));
            }

            function updateQuotePreview() {
                const preview = $('#quotePreview');
                const customerName = $('#customerName').val();
                const phoneNumber = $('#phoneNumber').val();
                const requestedBy = $('#requestedBy').val();
                const projectName = $('#projectName').val();

                if (cartItems.length === 0) {
                    preview.html('<p class="text-muted">Fill out the form to preview your quote.</p>');
                    return;
                }

                let html = `
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Customer Information:</h6>
                        <p class="mb-1">Name: ${customerName || 'Not provided'}</p>
                        <p class="mb-1">Phone: ${phoneNumber || 'Not provided'}</p>
                        ${requestedBy ? `<p class="mb-1">Requested By: ${requestedBy}</p>` : ''}
                        ${projectName ? `<p class="mb-1">Project: ${projectName}</p>` : ''}
                    </div>

                    <div class="mb-4">
                        <h6 class="font-weight-bold">Items:</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                cartItems.forEach(item => {
                    const basePrice = item.basePrice || 0;
                    const priceWithColor = basePrice + (basePrice * (item.priceIncrease / 100));
                    const total = priceWithColor * item.quantity;

                    html += `
                        <tr>
                            <td>${item.type.toUpperCase()} - ${item.size}" - ${item.model}</td>
                            <td>${item.quantity}</td>
                            <td>$${total.toFixed(2)}</td>
                        </tr>
                    `;
                });

                const subtotal = parseFloat($('#subtotal').text());
                html += `
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end">
                        <h6 class="font-weight-bold">Total: $${subtotal.toFixed(2)}</h6>
                    </div>
                `;

                preview.html(html);
            }

            // Function to save quote
            function saveQuote() {
                // Prevent duplicate submissions
                if ($('#saveButton').prop('disabled')) {
                    return;
                }

                // Validate customer information
                const customer = {
                    name: $('#customerName').val().trim(),
                    requestedBy: $('#requestedBy').val().trim(),
                    phoneNumber: $('#phoneNumber').val().trim(),
                    projectName: $('#projectName').val().trim()
                };

                if (!customer.name) {
                    alert('Please enter customer name');
                    return;
                }

                if (cartItems.length === 0) {
                    alert('Please add at least one item to the cart');
                    return;
                }

                $('#saveButton').prop('disabled', true);
                
                // Calculate final pricing
                let subtotal = 0;
                cartItems.forEach(item => {
                    // Get base price based on product type and size
                    let basePrice = 0;
                    const products = productData[item.type];
                    if (products) {
                        const sizeProducts = item.type === 'slant' ? products['default'] : products[item.size];
                        if (sizeProducts) {
                            const product = sizeProducts.find(p => p.model === item.model);
                            if (product) {
                                basePrice = parseFloat(product.base_price);
                                item.basePrice = basePrice; // Store the base price in the item
                            }
                        }
                    }
                    
                    // Apply color price increase
                    const priceWithColor = basePrice + (basePrice * (item.priceIncrease / 100));
                    
                    // Calculate item total
                    subtotal += priceWithColor * item.quantity;
                });

                // Calculate commission
                const commissionRate = parseFloat($('#commission option:selected').data('percentage')) || 0;
                const commission = subtotal * (commissionRate / 100);
                const total = subtotal + commission;

                // Prepare data for saving
                const quoteData = {
                    customer: customer,
                    items: cartItems.map(item => ({
                        type: item.type,
                        size: item.size,
                        model: item.model,
                        colorId: item.colorId,
                        colorName: item.colorName,
                        length: item.length,
                        breadth: item.breadth,
                        quantity: item.quantity,
                        basePrice: item.basePrice,
                        priceIncrease: item.priceIncrease
                    })),
                    pricing: {
                        subtotal: subtotal,
                        commissionRate: commissionRate,
                        commission: commission,
                        total: total
                    }
                };

                console.log('Sending quote data:', quoteData);

                // Save quote
                $.ajax({
                    url: 'save_quote.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(quoteData),
                    success: function(response) {
                        $('#saveButton').prop('disabled', false);
                        if (response.success) {
                            alert('Quote saved successfully!');
                            if (response.pdfUrl) {
                                // Convert relative URL to absolute URL
                                const pdfUrl = window.location.origin + response.pdfUrl;
                                window.open(pdfUrl, '_blank');
                            }
                            // Clear the form
                            $('#quoteForm')[0].reset();
                            cartItems = [];
                            updateCart();
                        } else {
                            alert('Error: ' + (response.error || 'Failed to save quote'));
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#saveButton').prop('disabled', false);
                        let errorMessage;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.error || error;
                        } catch (e) {
                            errorMessage = error;
                        }
                        alert('Error saving quote: ' + errorMessage);
                        console.error('Error response:', xhr.responseText);
                    }
                });
            }

            // Handle form submission
            $('#quoteForm').on('submit', function(e) {
                e.preventDefault();
                saveQuote();
            });

            // Handle save button click
            $('#saveButton').on('click', function(e) {
                e.preventDefault();
                saveQuote();
            });
        });
    </script>
</body>
</html>
