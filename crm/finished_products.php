<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'session_check.php';

$pageTitle = "Finished Products Inventory";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Angel Stones</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg"></i> Add New Product
            </button>
        </div>

        <!-- Filters Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <select class="form-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <!-- Categories will be populated via AJAX -->
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="colorFilter">
                    <option value="">All Colors</option>
                    <!-- Colors will be populated via AJAX -->
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-responsive">
            <table id="productsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Dimensions (L×W×H)</th>
                        <th>Total Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table content will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productId" name="productId">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" required>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="color" class="form-label">Color</label>
                                <select class="form-select" id="color" name="color" required>
                                    <!-- Colors will be loaded dynamically -->
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="length" class="form-label">Length (inches)</label>
                                <input type="number" step="0.01" class="form-control" id="length" name="length" required>
                            </div>
                            <div class="col-md-4">
                                <label for="width" class="form-label">Width (inches)</label>
                                <input type="number" step="0.01" class="form-control" id="width" name="width" required>
                            </div>
                            <div class="col-md-4">
                                <label for="height" class="form-label">Height (inches)</label>
                                <input type="number" step="0.01" class="form-control" id="height" name="height" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" required>
                            </div>
                            <div class="col-md-6">
                                <label for="warehouse" class="form-label">Location</label>
                                <select class="form-select" id="warehouse" name="warehouse" required>
                                    <option value="">Select Location</option>
                                    <!-- Warehouses will be populated via AJAX -->
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location_details" class="form-label">Location Details</label>
                            <input type="text" class="form-control" id="location_details" name="location_details" placeholder="Shelf/Bin number or specific location">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="weight" class="form-label">Weight</label>
                                <input type="number" step="0.01" class="form-control" id="weight" name="weight">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveProduct">Save Product</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement Modal -->
    <div class="modal fade" id="stockMovementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="stockMovementForm">
                        <input type="hidden" id="movementProductId" name="movementProductId">
                        <div class="mb-3">
                            <label for="warehouse" class="form-label">Warehouse</label>
                            <select class="form-select" id="warehouse" name="warehouse" required>
                                <!-- Warehouses will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="movementType" class="form-label">Movement Type</label>
                            <select class="form-select" id="movementType" name="movementType" required>
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                                <option value="transfer">Transfer</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveMovement">Save Movement</button>
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
    <script src="js/finished_products.js"></script>
</body>
</html>