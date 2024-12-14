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
$stmt = $pdo->query("SELECT *, size_inches as size FROM sertop_products ORDER BY id");
$sertop_products = $stmt->fetchAll();

$base_products = [];
$stmt = $pdo->query("SELECT *, size_inches as size FROM base_products ORDER BY id");
$base_products = $stmt->fetchAll();

$marker_products = [];
$stmt = $pdo->query("SELECT *, square_feet as size FROM marker_products ORDER BY id");
$marker_products = $stmt->fetchAll();

$slant_products = [];
$stmt = $pdo->query("SELECT *, size_inches as size FROM slant_products ORDER BY id");
$slant_products = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="css/datatables-custom.css">

<?php include 'navbar.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Global Markup Section -->
    <div class="content-card bg-white rounded-3 shadow-sm mb-4">
        <div class="card-header border-0 bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">Global Markup</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#globalMarkupModal">
                    <i class="fas fa-percentage me-2"></i>Update Global Markup
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Product Categories Tabs -->
    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sertop-tab" data-bs-toggle="tab" data-bs-target="#sertop" type="button" role="tab">
                <i class="fas fa-monument me-2"></i>SERTOP Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="base-tab" data-bs-toggle="tab" data-bs-target="#base" type="button" role="tab">
                <i class="fas fa-cube me-2"></i>BASE Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="marker-tab" data-bs-toggle="tab" data-bs-target="#marker" type="button" role="tab">
                <i class="fas fa-map-marker-alt me-2"></i>MARKER Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="slant-tab" data-bs-toggle="tab" data-bs-target="#slant" type="button" role="tab">
                <i class="fas fa-square me-2"></i>SLANT Products
            </button>
        </li>
    </ul>

    <div class="tab-content" id="productTabsContent">
        <!-- SERTOP Products Tab -->
        <div class="tab-pane fade show active" id="sertop" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">SERTOP Products</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="sertopTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Supplier Price</th>
                                    <th>Markup %</th>
                                    <th>Base Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($sertop_products as $product) {
                                    $size = isset($product['size_inches']) ? floatval($product['size_inches']) : 0;
                                    $model = $product['model'] ?? ($product['id'] . " - " . number_format($size, 2) . " inches");
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($product['id'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($model) . "</td>";
                                    echo "<td>" . number_format($size, 2) . " inch</td>";
                                    echo "<td>$" . number_format($product['supplier_price'] ?? 0, 2) . "</td>";
                                    echo "<td>" . number_format($product['markup_percentage'] ?? 0, 2) . "%</td>";
                                    echo "<td>$" . number_format($product['base_price'] ?? 0, 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button type='button' class='btn btn-primary btn-sm edit-product' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#editModal' 
                                                    data-product-type='sertop'
                                                    data-product-id='" . ($product['id'] ?? '') . "'
                                                    data-supplier-price='" . ($product['supplier_price'] ?? 0) . "'
                                                    data-markup='" . ($product['markup_percentage'] ?? 0) . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- BASE Products Tab -->
        <div class="tab-pane fade" id="base" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">BASE Products</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="baseTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Supplier Price</th>
                                    <th>Markup %</th>
                                    <th>Base Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($base_products as $product) {
                                    $size = isset($product['size_inches']) ? floatval($product['size_inches']) : 0;
                                    $model = $product['model'] ?? ($product['id'] . " - " . number_format($size, 2) . " inches");
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($product['id'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($model) . "</td>";
                                    echo "<td>" . number_format($size, 2) . " inch</td>";
                                    echo "<td>$" . number_format($product['supplier_price'] ?? 0, 2) . "</td>";
                                    echo "<td>" . number_format($product['markup_percentage'] ?? 0, 2) . "%</td>";
                                    echo "<td>$" . number_format($product['base_price'] ?? 0, 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button type='button' class='btn btn-primary btn-sm edit-product' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#editModal' 
                                                    data-product-type='base'
                                                    data-product-id='" . ($product['id'] ?? '') . "'
                                                    data-supplier-price='" . ($product['supplier_price'] ?? 0) . "'
                                                    data-markup='" . ($product['markup_percentage'] ?? 0) . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MARKER Products Tab -->
        <div class="tab-pane fade" id="marker" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">MARKER Products</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="markerTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Supplier Price</th>
                                    <th>Markup %</th>
                                    <th>Base Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($marker_products as $product) {
                                    $size = isset($product['square_feet']) ? floatval($product['square_feet']) : 0;
                                    $model = $product['model'] ?? ($product['id'] . " - " . number_format($size, 2) . " sq ft");
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($product['id'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($model) . "</td>";
                                    echo "<td>" . number_format($size, 2) . " sq ft</td>";
                                    echo "<td>$" . number_format($product['supplier_price'] ?? 0, 2) . "</td>";
                                    echo "<td>" . number_format($product['markup_percentage'] ?? 0, 2) . "%</td>";
                                    echo "<td>$" . number_format($product['base_price'] ?? 0, 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button type='button' class='btn btn-primary btn-sm edit-product' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#editModal' 
                                                    data-product-type='marker'
                                                    data-product-id='" . ($product['id'] ?? '') . "'
                                                    data-supplier-price='" . ($product['supplier_price'] ?? 0) . "'
                                                    data-markup='" . ($product['markup_percentage'] ?? 0) . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLANT Products Tab -->
        <div class="tab-pane fade" id="slant" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">SLANT Products</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="slantTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Supplier Price</th>
                                    <th>Markup %</th>
                                    <th>Base Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($slant_products as $product) {
                                    $size = isset($product['size_inches']) ? floatval($product['size_inches']) : 0;
                                    $model = $product['model'] ?? ($product['id'] . " - " . number_format($size, 2) . " inches");
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($product['id'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($model) . "</td>";
                                    echo "<td>" . number_format($size, 2) . " inch</td>";
                                    echo "<td>$" . number_format($product['supplier_price'] ?? 0, 2) . "</td>";
                                    echo "<td>" . number_format($product['markup_percentage'] ?? 0, 2) . "%</td>";
                                    echo "<td>$" . number_format($product['base_price'] ?? 0, 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button type='button' class='btn btn-primary btn-sm edit-product' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#editModal' 
                                                    data-product-type='slant'
                                                    data-product-id='" . ($product['id'] ?? '') . "'
                                                    data-supplier-price='" . ($product['supplier_price'] ?? 0) . "'
                                                    data-markup='" . ($product['markup_percentage'] ?? 0) . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST">
                    <input type="hidden" name="product_id" id="product_id">
                    <div class="mb-3">
                        <label for="supplier_price" class="form-label">Supplier Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="supplier_price" name="supplier_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="markup_percentage" class="form-label">Markup Percentage (%)</label>
                        <input type="number" step="0.01" class="form-control" id="markup_percentage" name="markup_percentage" required>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateButton">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Global Markup Modal -->
<div class="modal fade" id="globalMarkupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Global Markup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="global_markup" class="form-label">Global Markup Percentage (%)</label>
                        <input type="number" step="0.01" class="form-control" id="global_markup" name="global_markup" required>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="apply_global_markup" class="btn btn-primary">Apply Global Markup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#sertopTable, #baseTable, #markerTable, #slantTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']]
    });

    // Handle edit modal
    $('.edit-product').click(function() {
        const productId = $(this).data('product-id');
        const supplierPrice = $(this).data('supplier-price');
        const markup = $(this).data('markup');
        const productType = $(this).data('product-type');

        $('#product_id').val(productId);
        $('#supplier_price').val(supplierPrice);
        $('#markup_percentage').val(markup);
        
        // Set the form's submit button name based on product type
        $('#updateButton').attr('name', 'update_' + productType);
    });
});
</script>
