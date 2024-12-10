<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'session_check.php';

$pageTitle = "Product Movements";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Inventory Management</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .movement-type-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .movement-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .movement-type-card.selected {
            border: 2px solid #0d6efd;
            background-color: #f8f9fa;
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
        </div>

        <!-- Movement Type Selection -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card movement-type-card" data-type="in">
                    <div class="card-body text-center">
                        <i class="bi bi-box-arrow-in-down card-icon text-success"></i>
                        <h5 class="card-title">Stock In</h5>
                        <p class="card-text">Record new stock arriving at warehouse</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card movement-type-card" data-type="out">
                    <div class="card-body text-center">
                        <i class="bi bi-box-arrow-up card-icon text-danger"></i>
                        <h5 class="card-title">Stock Out</h5>
                        <p class="card-text">Record stock leaving warehouse</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card movement-type-card" data-type="transfer">
                    <div class="card-body text-center">
                        <i class="bi bi-arrow-left-right card-icon text-primary"></i>
                        <h5 class="card-title">Transfer</h5>
                        <p class="card-text">Move stock between warehouses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card movement-type-card" data-type="adjustment">
                    <div class="card-body text-center">
                        <i class="bi bi-gear card-icon text-warning"></i>
                        <h5 class="card-title">Adjustment</h5>
                        <p class="card-text">Adjust stock levels and correct discrepancies</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movement Form -->
        <div class="card mb-4" id="movementForm" style="display: none;">
            <div class="card-body">
                <h4 class="card-title mb-4" id="formTitle">Record Movement</h4>
                <form id="productMovementForm">
                    <input type="hidden" id="movementType" name="movementType">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product" class="form-label">Product</label>
                            <select class="form-select" id="product" name="product" required>
                                <option value="">Select Product</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6" id="sourceWarehouseDiv">
                            <label for="sourceWarehouse" class="form-label">Source Warehouse</label>
                            <select class="form-select" id="sourceWarehouse" name="sourceWarehouse">
                                <option value="">Select Warehouse</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="destinationWarehouseDiv">
                            <label for="destinationWarehouse" class="form-label">Destination Warehouse</label>
                            <select class="form-select" id="destinationWarehouse" name="destinationWarehouse">
                                <option value="">Select Warehouse</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="referenceType" class="form-label">Reference Type</label>
                            <select class="form-select" id="referenceType" name="referenceType">
                                <option value="">Select Reference Type</option>
                                <option value="production">Production</option>
                                <option value="sales_order">Sales Order</option>
                                <option value="purchase">Purchase</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="referenceId" class="form-label">Reference ID</label>
                            <input type="text" class="form-control" id="referenceId" name="referenceId">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" id="cancelMovement">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Movement</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Movements Table -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Recent Movements</h4>
                <table id="movementsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Movement</th>
                            <th>Quantity</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Reference</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                </table>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/product_movements.js"></script>
</body>
</html>
