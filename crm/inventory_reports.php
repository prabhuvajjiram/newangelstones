<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

$page_title = "Inventory Reports";
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="content-card bg-white rounded-3 shadow-sm mb-4">
        <div class="card-header border-0 bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">Inventory Reports</h5>
            </div>
        </div>
        <div class="card-body">
            <!-- Stock Level Report -->
            <div class="report-section mb-4">
                <h6 class="fw-bold mb-3">Stock Level Report</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product/Material</th>
                                <th>Warehouse</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody id="stockLevelTable">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inventory Movement Report -->
            <div class="report-section mb-4">
                <h6 class="fw-bold mb-3">Inventory Movement Report</h6>
                <div class="date-filters mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary" id="generateReport">Generate Report</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product/Material</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody id="movementTable">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    // Load initial reports
    loadStockLevelReport();
    loadMovementReport();
    
    // Add event listener for report generation
    document.getElementById('generateReport').addEventListener('click', loadMovementReport);
});

function loadStockLevelReport() {
    console.log('Loading stock level report...');
    fetch('ajax/generate_inventory_report.php?report_type=stock_level')
        .then(response => {
            console.log('Stock level response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Stock level data:', data);
            if (data.success) {
                const tbody = document.getElementById('stockLevelTable');
                tbody.innerHTML = '';
                
                data.data.forEach(item => {
                    const row = document.createElement('tr');
                    const statusClass = item.status === 'low_stock' ? 'bg-danger' : 
                                      item.status === 'out_of_stock' ? 'bg-dark' : 'bg-success';
                    const statusText = item.status.replace(/_/g, ' ').toUpperCase();
                    
                    row.innerHTML = `
                        <td>${item.product_name}</td>
                        <td>${item.warehouse_name}</td>
                        <td>${item.current_stock}</td>
                        <td>${item.minimum_stock}</td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                        <td>${new Date(item.last_updated).toLocaleString()}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                alert('Error loading stock level report: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
}

function loadMovementReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    console.log('Loading movement report...', { startDate, endDate });
    fetch(`ajax/generate_inventory_report.php?report_type=movement&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            console.log('Movement response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Movement data:', data);
            if (data.success) {
                const tbody = document.getElementById('movementTable');
                tbody.innerHTML = '';
                
                data.data.forEach(item => {
                    const row = document.createElement('tr');
                    const typeClass = item.type === 'in' ? 'text-success' : 
                                    item.type === 'out' ? 'text-danger' : 'text-warning';
                    
                    row.innerHTML = `
                        <td>${new Date(item.date).toLocaleString()}</td>
                        <td>${item.product_name}</td>
                        <td><span class="${typeClass}">${item.type.toUpperCase()}</span></td>
                        <td>${item.quantity}</td>
                        <td>${item.reference}</td>
                        <td>${item.created_by}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                alert('Error loading movement report: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>

<?php include 'footer.php'; ?>
