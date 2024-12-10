<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

include 'header.php';
?>

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="css/datatables-custom.css">

<?php include 'navbar.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Tabs for switching between Finished Products and Raw Materials -->
    <ul class="nav nav-tabs mb-4" id="pricingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="finished-products-tab" data-bs-toggle="tab" 
                    data-bs-target="#finished-products" type="button" role="tab">
                <i class="fas fa-box-open me-2"></i>Finished Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="raw-materials-tab" data-bs-toggle="tab" 
                    data-bs-target="#raw-materials" type="button" role="tab">
                <i class="fas fa-cubes me-2"></i>Raw Materials
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pricingTabsContent">
        <!-- Finished Products Tab -->
        <div class="tab-pane fade show active" id="finished-products" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Global Markup</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#globalMarkupModal" data-type="finished_product">
                            <i class="fas fa-percentage me-2"></i>Update Global Markup
                        </button>
                    </div>
                </div>
            </div>

            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Finished Products Pricing</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="finishedProductsTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Color</th>
                                    <th>Dimensions</th>
                                    <th>Unit Price</th>
                                    <th>Final Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raw Materials Tab -->
        <div class="tab-pane fade" id="raw-materials" role="tabpanel">
            <div class="content-card bg-white rounded-3 shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Global Markup</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#globalMarkupModal" data-type="raw_material">
                            <i class="fas fa-percentage me-2"></i>Update Global Markup
                        </button>
                    </div>
                </div>
            </div>

            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Raw Materials Pricing</h5>
                    </div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table id="rawMaterialsTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Color</th>
                                    <th>Dimensions</th>
                                    <th>Location</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Final Price</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Price Update Modal -->
<div class="modal fade" id="priceUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Pricing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="priceUpdateForm">
                    <input type="hidden" id="itemId" name="itemId">
                    <input type="hidden" id="itemType" name="itemType">
                    
                    <div class="mb-3">
                        <label for="unitPrice" class="form-label">Unit Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="unitPrice" name="unitPrice" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="markup" class="form-label">Markup Percentage</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="markup" name="markup" 
                                   step="0.1" min="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="finalPrice" class="form-label">Final Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="finalPrice" name="finalPrice" 
                                   step="0.01" min="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePriceBtn">Save Changes</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="globalMarkupForm">
                    <input type="hidden" id="globalItemType" name="globalItemType">
                    
                    <div class="mb-3">
                        <label for="globalMarkup" class="form-label">Global Markup Percentage</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="globalMarkup" name="globalMarkup" 
                                   step="0.1" min="0" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text text-muted">
                            This will update all final prices based on their unit prices and this markup percentage.
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Warning: This will update ALL final prices for the selected type. This action cannot be undone.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveGlobalMarkupBtn">Apply Global Markup</button>
            </div>
        </div>
    </div>
</div>

<!-- Add necessary JavaScript -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="js/inventory_pricing.js"></script>

<?php include 'footer.php'; ?>
