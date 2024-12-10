<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'session_check.php';

$pageTitle = "Raw Materials Inventory";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                <i class="bi bi-plus-lg"></i> Add New Material
            </button>
        </div>

        <!-- Filters Row -->
        <div class="row mb-4">
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
                <input type="text" class="form-control" id="searchInput" placeholder="Search materials...">
            </div>
        </div>

        <!-- Materials Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Color</th>
                        <th>Dimensions (L×W×H)</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Min Stock</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="materialsTableBody">
                    <!-- Table content will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Material Modal -->
    <div class="modal fade" id="addMaterialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addMaterialForm">
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <select class="form-select" name="color_id" required>
                                <!-- Colors will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Length (inches)</label>
                                <input type="number" class="form-control" name="length" step="0.01" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Width (inches)</label>
                                <input type="number" class="form-control" name="width" step="0.01" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Height (inches)</label>
                                <input type="number" class="form-control" name="height" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warehouse Location</label>
                            <select class="form-select" name="warehouse_id" required>
                                <!-- Warehouses will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location Details</label>
                            <input type="text" class="form-control" name="location_details" placeholder="Shelf/Bin number or specific location">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" name="min_stock_level" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMaterialBtn">Save Material</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/raw_materials.js"></script>
</body>
</html>
