<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_sertop'])) {
        $supplier_price = floatval($_POST['supplier_price']);
        $markup_percentage = floatval($_POST['markup_percentage']);
        $base_price = $supplier_price * (1 + ($markup_percentage / 100));
        
        $stmt = $pdo->prepare("UPDATE sertop_products 
                              SET supplier_price = ?, 
                                  markup_percentage = ?,
                                  base_price = ? 
                              WHERE id = ?");
        $stmt->execute([
            $supplier_price,
            $markup_percentage,
            $base_price,
            $_POST['product_id']
        ]);
        $_SESSION['success_message'] = "SERTOP product updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_base'])) {
        $supplier_price = floatval($_POST['supplier_price']);
        $markup_percentage = floatval($_POST['markup_percentage']);
        $base_price = $supplier_price * (1 + ($markup_percentage / 100));
        
        $stmt = $pdo->prepare("UPDATE base_products 
                              SET supplier_price = ?, 
                                  markup_percentage = ?,
                                  base_price = ? 
                              WHERE id = ?");
        $stmt->execute([
            $supplier_price,
            $markup_percentage,
            $base_price,
            $_POST['product_id']
        ]);
        $_SESSION['success_message'] = "BASE product updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_marker'])) {
        $supplier_price = floatval($_POST['supplier_price']);
        $markup_percentage = floatval($_POST['markup_percentage']);
        $base_price = $supplier_price * (1 + ($markup_percentage / 100));
        
        $stmt = $pdo->prepare("UPDATE marker_products 
                              SET supplier_price = ?, 
                                  markup_percentage = ?,
                                  base_price = ? 
                              WHERE id = ?");
        $stmt->execute([
            $supplier_price,
            $markup_percentage,
            $base_price,
            $_POST['product_id']
        ]);
        $_SESSION['success_message'] = "MARKER product updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_slant'])) {
        $supplier_price = floatval($_POST['supplier_price']);
        $markup_percentage = floatval($_POST['markup_percentage']);
        $base_price = $supplier_price * (1 + ($markup_percentage / 100));
        
        $stmt = $pdo->prepare("UPDATE slant_products 
                              SET supplier_price = ?, 
                                  markup_percentage = ?,
                                  base_price = ? 
                              WHERE id = ?");
        $stmt->execute([
            $supplier_price,
            $markup_percentage,
            $base_price,
            $_POST['product_id']
        ]);
        $_SESSION['success_message'] = "SLANT product updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['apply_global_markup'])) {
        $global_markup = floatval($_POST['global_markup']);

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Update SERTOP products
            $stmt = $pdo->prepare("UPDATE sertop_products 
                                  SET markup_percentage = ?,
                                      base_price = supplier_price * (1 + (? / 100))");
            $stmt->execute([$global_markup, $global_markup]);

            // Update BASE products
            $stmt = $pdo->prepare("UPDATE base_products 
                                  SET markup_percentage = ?,
                                      base_price = supplier_price * (1 + (? / 100))");
            $stmt->execute([$global_markup, $global_markup]);

            // Update MARKER products
            $stmt = $pdo->prepare("UPDATE marker_products 
                                  SET markup_percentage = ?,
                                      base_price = supplier_price * (1 + (? / 100))");
            $stmt->execute([$global_markup, $global_markup]);

            // Update SLANT products
            $stmt = $pdo->prepare("UPDATE slant_products 
                                  SET markup_percentage = ?,
                                      base_price = supplier_price * (1 + (? / 100))");
            $stmt->execute([$global_markup, $global_markup]);

            // Commit transaction
            $pdo->commit();
            $_SESSION['success_message'] = "Global markup applied successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error applying global markup: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Display success message if it exists in session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Remove the message after displaying
}

// Fetch all products
$sertop_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, ' - ', size_inches, ' inches') as product_code FROM sertop_products ORDER BY size_inches, model");
$sertop_products = $stmt->fetchAll();

$base_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, ' - ', size_inches, ' inches') as product_code FROM base_products ORDER BY size_inches, model");
$base_products = $stmt->fetchAll();

$marker_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(product_code, ' - ', square_feet, ' sq ft') as display_code FROM marker_products ORDER BY square_feet, product_code");
$marker_products = $stmt->fetchAll();

$slant_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(product_code, ' - ', model) as display_code FROM slant_products ORDER BY product_code");
$slant_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table > :not(caption) > * > * {
            padding: 0.75rem;
            background-color: #ffffff;
        }
        .table {
            --bs-table-bg: #ffffff;
            --bs-table-striped-bg: #ffffff;
            --bs-table-hover-bg: #ffffff;
        }
        .table tbody tr {
            background-color: #ffffff;
        }
        .input-group {
            background-color: #fff;
            border-radius: 4px;
            overflow: hidden;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }
        .form-control {
            border: 1px solid #dee2e6;
        }
        .form-control:read-only {
            background-color: #f8f9fa;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 0.375rem 0.75rem;
        }
        .card {
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        .card-header h4 {
            margin: 0;
            color: #212529;
            font-weight: 500;
        }
        .table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 500;
            color: #212529;
            background-color: #ffffff;
        }
        .table td {
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Product Management</h2>
        </div>

        <!-- Global Markup Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Global Markup Settings</h4>
            </div>
            <div class="card-body">
                <form method="post" id="globalMarkupForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Global Markup Percentage</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="global_markup" class="form-control" step="0.1" required>
                            <span class="input-group-text"><i class="bi bi-percent"></i></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="apply_global_markup" class="btn btn-primary btn-sm">
                            <i class="bi bi-check2-all me-1"></i>Apply Global Markup
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SERTOP Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>SERTOP Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                            <?php foreach ($sertop_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td><?php echo htmlspecialchars($product['size_inches']); ?> inch</td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="supplier_price" 
                                                       value="<?php echo $product['supplier_price']; ?>" 
                                                       class="form-control form-control-sm price-input" 
                                                       step="0.01" required>
                                            </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="markup_percentage" 
                                                   value="<?php echo $product['markup_percentage']; ?>" 
                                                   class="form-control form-control-sm markup-input"
                                                   step="0.1" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control form-control-sm final-price" 
                                                   value="<?php echo number_format($product['base_price'], 2); ?>" 
                                                   readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_sertop" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save"></i>
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BASE Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>BASE Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                            <?php foreach ($base_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td><?php echo htmlspecialchars($product['size_inches']); ?> inch</td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="supplier_price" 
                                                       value="<?php echo $product['supplier_price']; ?>" 
                                                       class="form-control form-control-sm price-input" 
                                                       step="0.01" required>
                                            </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="markup_percentage" 
                                                   value="<?php echo $product['markup_percentage']; ?>" 
                                                   class="form-control form-control-sm markup-input"
                                                   step="0.1" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control form-control-sm final-price" 
                                                   value="<?php echo number_format($product['base_price'], 2); ?>" 
                                                   readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_base" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save"></i>
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MARKER Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>MARKER Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Square Feet</th>
                                <th>Supplier Price</th>
                                <th>Markup %</th>
                                <th>Final Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marker_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['display_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['square_feet']); ?> SQFT</td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="supplier_price" 
                                                       value="<?php echo $product['supplier_price']; ?>" 
                                                       class="form-control form-control-sm price-input" 
                                                       step="0.01" required>
                                            </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="markup_percentage" 
                                                   value="<?php echo $product['markup_percentage']; ?>" 
                                                   class="form-control form-control-sm markup-input"
                                                   step="0.1" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control form-control-sm final-price" 
                                                   value="<?php echo number_format($product['base_price'], 2); ?>" 
                                                   readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_marker" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save"></i>
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SLANT Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>SLANT Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Supplier Price</th>
                                <th>Markup %</th>
                                <th>Final Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slant_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['display_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="supplier_price" 
                                                       value="<?php echo $product['supplier_price']; ?>" 
                                                       class="form-control form-control-sm price-input" 
                                                       step="0.01" required>
                                            </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="markup_percentage" 
                                                   value="<?php echo $product['markup_percentage']; ?>" 
                                                   class="form-control form-control-sm markup-input"
                                                   step="0.1" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control form-control-sm final-price" 
                                                   value="<?php echo number_format($product['base_price'], 2); ?>" 
                                                   readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_slant" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save"></i>
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProduct(type, id) {
            // Implement product view functionality
            alert('View product: ' + type + ' ID: ' + id);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all supplier price and markup inputs
            document.querySelectorAll('.price-input, .markup-input').forEach(input => {
                input.addEventListener('input', function() {
                    try {
                        const row = this.closest('tr');
                        if (!row) return;
                        
                        const supplierPriceInput = row.querySelector('.price-input');
                        const markupInput = row.querySelector('.markup-input');
                        const finalPriceInput = row.querySelector('.final-price');
                        
                        if (!supplierPriceInput || !markupInput || !finalPriceInput) return;

                        const supplierPrice = parseFloat(supplierPriceInput.value) || 0;
                        const markupPercentage = parseFloat(markupInput.value) || 0;
                        const finalPrice = supplierPrice * (1 + (markupPercentage / 100));
                        
                        finalPriceInput.value = finalPrice.toFixed(2);
                    } catch (error) {
                        console.error('Error calculating price:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>
