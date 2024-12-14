<?php
require_once 'includes/config.php';
require_once 'session_check.php';
requireAdmin();

$page_title = "Financial Reports";
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="content-card bg-white rounded-3 shadow-sm mb-4">
        <div class="card-header border-0 bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">Financial Reports</h5>
            </div>
        </div>
        <div class="card-body">
            <!-- Date Range Filter -->
            <div class="date-filters mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary d-block" id="generateReport">Generate Report</button>
                    </div>
                </div>
            </div>

            <!-- Revenue Summary -->
            <div class="report-section mb-4">
                <h6 class="fw-bold mb-3">Revenue Summary</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Revenue</h6>
                                <h4 class="card-title" id="totalRevenue">$0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Orders</h6>
                                <h4 class="card-title" id="totalOrders">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Average Order Value</h6>
                                <h4 class="card-title" id="avgOrderValue">$0.00</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales by Product -->
            <div class="report-section mb-4">
                <h6 class="fw-bold mb-3">Sales by Product</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Cost</th>
                                <th>Profit</th>
                                <th>Profit Margin</th>
                            </tr>
                        </thead>
                        <tbody id="productSalesTable">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="report-section">
                <h6 class="fw-bold mb-3">Monthly Revenue Trend</h6>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    // Load initial reports
    loadFinancialReports();
    
    // Add event listener for report generation
    document.getElementById('generateReport').addEventListener('click', loadFinancialReports);
});

function loadFinancialReports() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    console.log('Loading financial reports...', { startDate, endDate });
    
    // Load Revenue Summary
    fetch(`ajax/generate_financial_report.php?report_type=summary&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            console.log('Summary response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Summary data:', data);
            if (data.success) {
                document.getElementById('totalRevenue').textContent = '$' + data.data.total_revenue.toFixed(2);
                document.getElementById('totalOrders').textContent = data.data.total_orders;
                document.getElementById('avgOrderValue').textContent = '$' + data.data.avg_order_value.toFixed(2);
            }
        })
        .catch(error => console.error('Error loading summary:', error));

    // Load Product Sales
    fetch(`ajax/generate_financial_report.php?report_type=product_sales&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            console.log('Product sales response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Product sales data:', data);
            if (data.success) {
                const tbody = document.getElementById('productSalesTable');
                tbody.innerHTML = '';
                
                data.data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.product_name}</td>
                        <td>${item.units_sold}</td>
                        <td>$${item.revenue.toFixed(2)}</td>
                        <td>$${item.cost.toFixed(2)}</td>
                        <td>$${item.profit.toFixed(2)}</td>
                        <td>${item.profit_margin.toFixed(2)}%</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        })
        .catch(error => console.error('Error loading product sales:', error));

    // Load Monthly Trend
    fetch(`ajax/generate_financial_report.php?report_type=monthly_trend&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            console.log('Monthly trend response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Monthly trend data:', data);
            if (data.success) {
                // Destroy existing chart if it exists
                if (window.revenueChart) {
                    window.revenueChart.destroy();
                }
                initRevenueChart(data.data);
            }
        })
        .catch(error => console.error('Error loading monthly trend:', error));
}

function initRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    window.revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Monthly Revenue',
                data: data.values,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php include 'footer.php'; ?>
