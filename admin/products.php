<?php
require_once 'includes/config.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_sertop'])) {
        $stmt = $pdo->prepare("UPDATE sertop_products SET base_price = ? WHERE id = ?");
        $stmt->execute([$_POST['base_price'], $_POST['product_id']]);
        $success_message = "SERTOP product updated successfully!";
    } elseif (isset($_POST['update_base'])) {
        $stmt = $pdo->prepare("UPDATE base_products SET base_price = ? WHERE id = ?");
        $stmt->execute([$_POST['base_price'], $_POST['product_id']]);
        $success_message = "BASE product updated successfully!";
    } elseif (isset($_POST['update_marker'])) {
        $stmt = $pdo->prepare("UPDATE marker_products SET base_price = ? WHERE id = ?");
        $stmt->execute([$_POST['base_price'], $_POST['product_id']]);
        $success_message = "MARKER product updated successfully!";
    } elseif (isset($_POST['update_slant'])) {
        $stmt = $pdo->prepare("UPDATE slant_products SET base_price = ? WHERE id = ?");
        $stmt->execute([$_POST['base_price'], $_POST['product_id']]);
        $success_message = "SLANT product updated successfully!";
    }
}

// Fetch all products
$sertop_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, size_inches) as product_code FROM sertop_products ORDER BY size_inches, model");
$sertop_products = $stmt->fetchAll();

$base_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, size_inches) as product_code FROM base_products ORDER BY size_inches, model");
$base_products = $stmt->fetchAll();

$marker_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, square_feet) as product_code FROM marker_products ORDER BY square_feet, model");
$marker_products = $stmt->fetchAll();

$slant_products = [];
$stmt = $pdo->query("SELECT *, CONCAT(model, id) as product_code FROM slant_products ORDER BY model");
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
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Product Management</h2>

        <!-- SERTOP Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>SERTOP Products</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Model</th>
                                <th>Size</th>
                                <th>Base Price</th>
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
                                            <input type="number" name="base_price" value="<?php echo $product['base_price']; ?>" 
                                                   class="form-control form-control-sm" style="width: 100px" step="0.01" required>
                                            <button type="submit" name="update_sertop" class="btn btn-sm btn-primary ms-2">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewProduct('sertop', <?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Model</th>
                                <th>Size</th>
                                <th>Base Price</th>
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
                                            <input type="number" name="base_price" value="<?php echo $product['base_price']; ?>" 
                                                   class="form-control form-control-sm" style="width: 100px" step="0.01" required>
                                            <button type="submit" name="update_base" class="btn btn-sm btn-primary ms-2">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewProduct('base', <?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Model</th>
                                <th>Square Feet</th>
                                <th>Base Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marker_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td><?php echo htmlspecialchars($product['square_feet']); ?> SQFT</td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="base_price" value="<?php echo $product['base_price']; ?>" 
                                                   class="form-control form-control-sm" style="width: 100px" step="0.01" required>
                                            <button type="submit" name="update_marker" class="btn btn-sm btn-primary ms-2">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewProduct('marker', <?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Model</th>
                                <th>Base Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slant_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($product['model']); ?></td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="base_price" value="<?php echo $product['base_price']; ?>" 
                                                   class="form-control form-control-sm" style="width: 100px" step="0.01" required>
                                            <button type="submit" name="update_slant" class="btn btn-sm btn-primary ms-2">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewProduct('slant', <?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
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
</body>
</html>
