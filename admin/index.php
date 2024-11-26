<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch all product types
$sertop_products = $conn->query("SELECT * FROM sertop_products ORDER BY size_inches, product_code");
$base_products = $conn->query("SELECT * FROM base_products ORDER BY size_inches, product_code");
$marker_products = $conn->query("SELECT * FROM marker_products ORDER BY square_feet");
$slant_products = $conn->query("SELECT * FROM slant_products ORDER BY product_code");

// Fetch stone colors
$colors = $conn->query("SELECT * FROM stone_color_rates ORDER BY color_name");

// Fetch commission rates
$commissionRates = $conn->query("SELECT * FROM commission_rates ORDER BY percentage");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Dashboard - Angel Stones</title>
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
                        <a class="nav-link active" href="index.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quote.php">Generate Quote</a>
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
        <h2 class="mb-4">Angel Stones Product Catalog</h2>

        <div class="row">
            <!-- SERTOP Products -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">SERTOP Products</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">SERTOP products available in 6-inch and 8-inch sizes</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Size</th>
                                        <th>Model</th>
                                        <th>Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $sertop_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['size_inches']; ?>-inch</td>
                                            <td><?php echo $product['product_code']; ?></td>
                                            <td>$<?php echo number_format($product['base_price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BASE Products -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">BASE Products</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Base products in 6-inch, 8-inch, and 10-inch sizes (P1 and Premium)</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Size</th>
                                        <th>Model</th>
                                        <th>Type</th>
                                        <th>Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $base_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['size_inches']; ?>-inch</td>
                                            <td><?php echo $product['product_code']; ?></td>
                                            <td><?php echo $product['is_premium'] ? 'Premium' : 'Standard'; ?></td>
                                            <td>$<?php echo number_format($product['base_price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MARKER Products -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">MARKER Products</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Marker products available in different square feet sizes</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Model</th>
                                        <th>Size</th>
                                        <th>Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $marker_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['product_code']; ?></td>
                                            <td><?php echo $product['square_feet']; ?> sq.ft</td>
                                            <td>$<?php echo number_format($product['base_price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLANT Products -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">SLANT Products</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Available slant product models</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Model</th>
                                        <th>Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $slant_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $product['product_code']; ?></td>
                                            <td>$<?php echo number_format($product['base_price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stone Colors -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">Stone Colors & Materials</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Additional cost for different stone colors and materials</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Color/Material</th>
                                        <th>Price Increase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($color = $colors->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($color['color_name']); ?></td>
                                            <td><?php echo number_format($color['price_increase_percentage'], 1); ?>%</td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Rates -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Commission Rates</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Commission percentages for different sales categories</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($rate = $commissionRates->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rate['rate_name']); ?></td>
                                            <td><?php echo $rate['percentage']; ?>%</td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
