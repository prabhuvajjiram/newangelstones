<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'session_check.php';

// Require at least staff access
requireStaffOrAdmin();

$pageTitle = "Batch Operations";
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
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
        </div>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4" id="batchTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="movement-tab" data-bs-toggle="tab" href="#movement">
                    <i class="bi bi-arrows-move me-2"></i>Bulk Movement
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link" id="price-tab" data-bs-toggle="tab" href="#price">
                    <i class="bi bi-currency-dollar me-2"></i>Batch Price Update
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" id="quantity-tab" data-bs-toggle="tab" href="#quantity">
                    <i class="bi bi-plus-slash-minus me-2"></i>Quantity Adjustment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history">
                    <i class="bi bi-clock-history me-2"></i>Operation History
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Bulk Movement Tab -->
            <div class="tab-pane fade show active" id="movement">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Bulk Movement</h5>
                        <form id="bulkMovementForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Item Type</label>
                                    <select class="form-select" id="moveItemType" required>
                                        <option value="finished_product">Finished Products</option>
                                        <option value="raw_material">Raw Materials</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Source Warehouse</label>
                                    <select class="form-select" id="sourceWarehouse" required></select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Destination Warehouse</label>
                                    <select class="form-select" id="destWarehouse" required></select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Items to Move</label>
                                    <select class="form-select" id="moveItems" multiple required></select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Start Bulk Movement
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <!-- Batch Price Update Tab -->
            <div class="tab-pane fade" id="price">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Batch Price Update</h5>
                        <form id="batchPriceForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Item Type</label>
                                    <select class="form-select" id="priceItemType" required>
                                        <option value="finished_product">Finished Products</option>
                                        <option value="raw_material">Raw Materials</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Update Type</label>
                                    <select class="form-select" id="priceUpdateType" required>
                                        <option value="percentage">Percentage Change</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Value</label>
                                    <input type="number" class="form-control" id="priceValue" required>
                                    <div class="form-text">For percentage, use positive/negative numbers (e.g., 10 for +10%, -10 for -10%)</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Items to Update</label>
                                    <select class="form-select" id="priceItems" multiple required></select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Reason for Update</label>
                                    <textarea class="form-control" id="priceReason" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-currency-dollar me-2"></i>Start Price Update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quantity Adjustment Tab -->
            <div class="tab-pane fade" id="quantity">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Quantity Adjustment</h5>
                        <form id="quantityAdjustForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Item Type</label>
                                    <select class="form-select" id="quantityItemType" required>
                                        <option value="finished_product">Finished Products</option>
                                        <option value="raw_material">Raw Materials</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Warehouse</label>
                                    <select class="form-select" id="quantityWarehouse" required></select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Items to Adjust</label>
                                    <select class="form-select" id="quantityItems" multiple required></select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Adjustment Type</label>
                                    <select class="form-select" id="adjustmentType" required>
                                        <option value="add">Add to Current Quantity</option>
                                        <option value="subtract">Subtract from Current Quantity</option>
                                        <option value="set">Set to Specific Quantity</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="adjustmentQuantity" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Reason for Adjustment</label>
                                    <textarea class="form-control" id="adjustmentReason" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-slash-minus me-2"></i>Start Quantity Adjustment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Operation History</h5>
                        <div class="table-responsive">
                            <table id="historyTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Operation ID</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Completed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="operationDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Operation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="operationDetails"></div>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Add role information for JavaScript
        var isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
    </script>
    <script src="js/batch_operations.js"></script>
</body>
</html>
