<?php
session_start();
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

// Get success message from session if it exists
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Process global markup update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_markup']) && isset($_POST['global_markup'])) {
    try {
        $global_markup = floatval($_POST['global_markup']);
        
        // Update all product tables
        $tables = ['sertop_products', 'base_products', 'marker_products', 'slant_products'];
        $pdo->beginTransaction();
        
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("UPDATE $table SET markup_percentage = ?, base_price = ROUND(supplier_price * (1 + ?/100), 2)");
            $stmt->execute([$global_markup, $global_markup]);
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "All products have been updated successfully with $global_markup% markup.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error updating products: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Process individual product updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['markup_percentage'])) {
    try {
        $product_id = intval($_POST['product_id']);
        $markup_percentage = floatval($_POST['markup_percentage']);
        $supplier_price = floatval($_POST['supplier_price']);
        
        // Determine which table to update based on the form submission
        $table = null;
        if (isset($_POST['update_sertop'])) $table = 'sertop_products';
        elseif (isset($_POST['update_base'])) $table = 'base_products';
        elseif (isset($_POST['update_marker'])) $table = 'marker_products';
        elseif (isset($_POST['update_slant'])) $table = 'slant_products';
        
        if ($table) {
            $stmt = $pdo->prepare("UPDATE $table SET markup_percentage = ?, supplier_price = ?, base_price = ROUND(? * (1 + ?/100), 2) WHERE id = ?");
            $stmt->execute([$markup_percentage, $supplier_price, $supplier_price, $markup_percentage, $product_id]);
            
            $_SESSION['success_message'] = "Product updated successfully.";
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating product: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch products with properly calculated base_price
$sertop_products = $pdo->query("SELECT *, ROUND(supplier_price * (1 + markup_percentage/100), 2) as base_price FROM sertop_products")->fetchAll();
$base_products = $pdo->query("SELECT *, ROUND(supplier_price * (1 + markup_percentage/100), 2) as base_price FROM base_products")->fetchAll();
$marker_products = $pdo->query("SELECT *, ROUND(supplier_price * (1 + markup_percentage/100), 2) as base_price FROM marker_products")->fetchAll();
$slant_products = $pdo->query("SELECT *, ROUND(supplier_price * (1 + markup_percentage/100), 2) as base_price FROM slant_products")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .price-preview {
            background-color: #e8f4f8 !important;
            animation: highlight 1s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: #ffffff !important; }
            50% { background-color: #e8f4f8 !important; }
            100% { background-color: #e8f4f8 !important; }
        }
        
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        
        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        
        .input-group {
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .alert {
            border: none;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .markup-controls {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Product Management</h2>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Markup Settings -->
        <div class="markup-controls">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="btn-group" role="group" aria-label="Markup Mode">
                        <input type="radio" class="btn-check" name="markup_mode" id="individual_mode" value="individual" checked>
                        <label class="btn btn-outline-primary" for="individual_mode">
                            <i class="bi bi-pencil-square me-1"></i>Individual
                        </label>
                        
                        <input type="radio" class="btn-check" name="markup_mode" id="global_mode" value="global">
                        <label class="btn btn-outline-primary" for="global_mode">
                            <i class="bi bi-globe me-1"></i>Global
                        </label>
                    </div>
                </div>
                <div class="col-md-8">
                    <form id="markup-form" method="post" class="d-flex gap-2" onsubmit="return handleFormSubmit(event)">
                        <div class="input-group" style="max-width: 200px;">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-percent"></i>
                            </span>
                            <input type="number" name="global_markup" id="global_markup" 
                                   class="form-control" step="0.01" min="0" value="0" 
                                   placeholder="Enter markup" disabled required>
                        </div>
                        <button type="button" id="preview_markup" class="btn btn-secondary btn-sm" disabled>
                            <i class="bi bi-eye me-1"></i>Preview
                        </button>
                        <button type="submit" name="update_markup" id="update_markup" class="btn btn-primary btn-sm" disabled>
                            <i class="bi bi-check2-circle me-1"></i>Update All
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product Tables -->
        <?php foreach (['SERTOP' => $sertop_products, 'BASE' => $base_products, 
                       'MARKER' => $marker_products, 'SLANT' => $slant_products] as $type => $products): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><?php echo $type; ?> Products</h4>
                <span class="badge bg-primary"><?php echo count($products); ?> items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Supplier Price</th>
                                <th>Markup %</th>
                                <th>Final Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <form method="post" class="product-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td><?php echo htmlspecialchars($product['size_inches']); ?> inch</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="supplier_price" 
                                                   value="<?php echo $product['supplier_price']; ?>" 
                                                   class="form-control price-input" 
                                                   step="0.01" required>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="markup_percentage" 
                                                   value="<?php echo $product['markup_percentage']; ?>" 
                                                   class="form-control markup-input" 
                                                   step="0.01" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control final-price" 
                                                   value="<?php echo number_format($product['base_price'], 2); ?>" 
                                                   readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_<?php echo strtolower($type); ?>" 
                                                class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-save"></i>
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const markupModeRadios = document.querySelectorAll('input[name="markup_mode"]');
            const globalMarkupInput = document.getElementById('global_markup');
            const updateMarkupBtn = document.getElementById('update_markup');
            const previewMarkupBtn = document.getElementById('preview_markup');
            const markupInputs = document.querySelectorAll('.markup-input');
            const loadingOverlay = document.querySelector('.loading-overlay');
            let previewActive = false;
            
            function showLoading() {
                loadingOverlay.style.display = 'flex';
            }
            
            function hideLoading() {
                loadingOverlay.style.display = 'none';
            }
            
            function calculateFinalPrice(supplierPrice, markupPercentage) {
                supplierPrice = parseFloat(supplierPrice) || 0;
                markupPercentage = parseFloat(markupPercentage) || 0;
                return supplierPrice * (1 + (markupPercentage / 100));
            }
            
            function formatPrice(price) {
                return parseFloat(price).toFixed(2);
            }
            
            function updateFinalPrice(form) {
                const priceInput = form.querySelector('.price-input');
                const markupInput = form.querySelector('.markup-input');
                const finalPriceInput = form.querySelector('.final-price');
                
                if (priceInput && markupInput && finalPriceInput) {
                    const supplierPrice = parseFloat(priceInput.value) || 0;
                    const markupPercentage = parseFloat(markupInput.value) || 0;
                    const finalPrice = calculateFinalPrice(supplierPrice, markupPercentage);
                    finalPriceInput.value = formatPrice(finalPrice);
                }
            }
            
            function clearPreview() {
                document.querySelectorAll('form.product-form').forEach(form => {
                    const markupInput = form.querySelector('.markup-input');
                    const finalPriceInput = form.querySelector('.final-price');
                    if (markupInput && markupInput.dataset.originalValue) {
                        markupInput.value = markupInput.dataset.originalValue;
                        delete markupInput.dataset.originalValue;
                    }
                    if (finalPriceInput) {
                        finalPriceInput.classList.remove('price-preview');
                        updateFinalPrice(form);
                    }
                    const saveBtn = form.querySelector('button[type="submit"]');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                    }
                });
                previewActive = false;
                updateMarkupBtn.disabled = true;
            }
            
            function updateAllFinalPrices(markupPercentage, preview = false) {
                if (!preview) {
                    clearPreview();
                    return;
                }
                
                document.querySelectorAll('form.product-form').forEach(form => {
                    const priceInput = form.querySelector('.price-input');
                    const markupInput = form.querySelector('.markup-input');
                    const finalPriceInput = form.querySelector('.final-price');
                    const saveBtn = form.querySelector('button[type="submit"]');
                    
                    if (priceInput && markupInput && finalPriceInput) {
                        const supplierPrice = parseFloat(priceInput.value) || 0;
                        const finalPrice = calculateFinalPrice(supplierPrice, markupPercentage);
                        
                        markupInput.dataset.originalValue = markupInput.value;
                        markupInput.value = markupPercentage;
                        finalPriceInput.value = formatPrice(finalPrice);
                        finalPriceInput.classList.add('price-preview');
                        if (saveBtn) {
                            saveBtn.disabled = true;
                        }
                    }
                });
                
                previewActive = true;
                updateMarkupBtn.disabled = false;
            }
            
            // Preview button click handler
            previewMarkupBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showLoading();
                
                try {
                    const globalMarkup = parseFloat(globalMarkupInput.value) || 0;
                    updateAllFinalPrices(globalMarkup, true);
                } catch (error) {
                    console.error('Preview error:', error);
                } finally {
                    hideLoading();
                }
            });
            
            // Global markup input handler
            globalMarkupInput.addEventListener('input', function() {
                updateMarkupBtn.disabled = true;
                if (previewActive) {
                    clearPreview();
                }
            });
            
            // Mode change handler
            markupModeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const mode = this.value;
                    toggleMarkupMode(mode);
                });
            });
            
            function toggleMarkupMode(mode) {
                const isGlobal = mode === 'global';
                globalMarkupInput.disabled = !isGlobal;
                previewMarkupBtn.disabled = !isGlobal;
                updateMarkupBtn.disabled = true;
                
                if (previewActive) {
                    clearPreview();
                }
                
                markupInputs.forEach(input => {
                    const form = input.closest('form');
                    input.disabled = isGlobal;
                    if (form) {
                        const saveBtn = form.querySelector('button[type="submit"]');
                        if (saveBtn) {
                            saveBtn.disabled = isGlobal;
                        }
                    }
                });
                
                if (isGlobal) {
                    globalMarkupInput.value = '';
                    globalMarkupInput.focus();
                }
            }
            
            // Individual price/markup input handler
            document.querySelectorAll('.price-input, .markup-input').forEach(input => {
                input.addEventListener('input', function() {
                    const mode = document.querySelector('input[name="markup_mode"]:checked').value;
                    if (mode === 'individual') {
                        const form = this.closest('form');
                        if (form && form.classList.contains('product-form')) {
                            updateFinalPrice(form);
                        }
                    }
                });
            });
            
            // Initialize markup mode
            const initialMode = document.querySelector('input[name="markup_mode"]:checked').value;
            toggleMarkupMode(initialMode);
            
            // Form submission handlers
            window.handleFormSubmit = function(event) {
                if (!previewActive) {
                    event.preventDefault();
                    alert('Please preview changes before updating.');
                    return false;
                }
                showLoading();
                return true;
            };
            
            document.querySelectorAll('.product-form').forEach(form => {
                form.addEventListener('submit', function() {
                    showLoading();
                });
            });
            
            // Auto-dismiss success message
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>
