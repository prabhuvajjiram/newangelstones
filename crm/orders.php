<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Manage Orders";
require_once 'header.php';
require_once 'navbar.php';

// Debug session
error_log("Debug: Session data in orders.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

try {
    // Get filters
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $customer = isset($_GET['customer']) ? $_GET['customer'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    $manufacturing_status = isset($_GET['manufacturing_status']) ? $_GET['manufacturing_status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? $_GET['page'] : 1;

    // Build base query
    $query = "SELECT o.order_id, o.order_number, o.order_date, o.status,
                     o.total_amount, o.paid_amount,
                     c.name as customer_name, 
                     comp.name as company_name
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.id
              LEFT JOIN companies comp ON o.company_id = comp.id
              WHERE 1=1";

    $params = [];

    // If not admin, only show user's own orders
    if (!isAdmin()) {
        $query .= " AND o.created_by = :user_email";
        $params[':user_email'] = $_SESSION['email'];
    }

    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (
            o.order_number LIKE ? OR 
            c.name LIKE ? OR 
            comp.name LIKE ? OR
            o.total_amount LIKE ? OR
            o.status LIKE ?
        )";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    // Add other filters
    if (!empty($status)) {
        $query .= " AND o.status = ?";
        $params[] = $status;
    }

    if (!empty($manufacturing_status)) {
        $query .= " AND o.manufacturing_status = ?";
        $params[] = $manufacturing_status;
    }

    if (!empty($date_from)) {
        $query .= " AND DATE(o.order_date) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $query .= " AND DATE(o.order_date) <= ?";
        $params[] = $date_to;
    }

    // Add sorting
    $query .= " ORDER BY o.order_date DESC";

    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $total_orders = count($orders);
    $orders_per_page = 10;
    $total_pages = ceil($total_orders / $orders_per_page);
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($current_page - 1) * $orders_per_page;

    // Slice the orders array for the current page
    $orders = array_slice($orders, $offset, $orders_per_page);

    // Pagination query string
    $query_string = '';
    if ($status) {
        $query_string .= '&status=' . $status;
    }
    if ($customer) {
        $query_string .= '&customer=' . $customer;
    }
    if ($date_from) {
        $query_string .= '&date_from=' . $date_from;
    }
    if ($date_to) {
        $query_string .= '&date_to=' . $date_to;
    }
    if ($manufacturing_status) {
        $query_string .= '&manufacturing_status=' . $manufacturing_status;
    }
    if ($search) {
        $query_string .= '&search=' . $search;
    }

} catch (Exception $e) {
    error_log("Error in orders.php: " . $e->getMessage());
    die("An error occurred while fetching orders. Please try again later.");
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0">Orders</h2>
        </div>
        <div class="col text-end">
            <a href="create_order.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus"></i> Create New Order
            </a>
        </div>
    </div>

    <!-- Search and filter form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-lg" id="search" name="search" 
                           placeholder="Search orders..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-lg" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($status ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo ($status ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo ($status ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($status ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-lg" id="start_date" name="start_date" 
                           value="<?php echo $date_from ?? ''; ?>" placeholder="Start Date">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-lg" id="end_date" name="end_date" 
                           value="<?php echo $date_to ?? ''; ?>" placeholder="End Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary btn-lg w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Company</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="fw-bold text-primary">
                                            <?php echo htmlspecialchars($order['order_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['company_name'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php 
                                            switch($order['status']) {
                                                case 'pending':
                                                    echo 'bg-warning';
                                                    break;
                                                case 'processing':
                                                    echo 'bg-info';
                                                    break;
                                                case 'shipped':
                                                    echo 'bg-primary';
                                                    break;
                                                case 'delivered':
                                                    echo 'bg-success';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-danger';
                                                    break;
                                                default:
                                                    echo 'bg-secondary';
                                            }
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($order['total_amount'], 2); ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($order['paid_amount'], 2); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" 
                                               title="View Order">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_order.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary"
                                               data-bs-toggle="tooltip" 
                                               title="Edit Order">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="print_order.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-outline-info"
                                               data-bs-toggle="tooltip" 
                                               title="Print Order">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($current_page - 1); ?><?php echo $query_string; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $query_string; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($current_page + 1); ?><?php echo $query_string; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

.form-control-lg, .form-select-lg {
    height: 48px;
    font-size: 1rem;
}

.table th {
    font-weight: 600;
    color: #555;
    border-top: none;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.btn-group .btn {
    padding: 0.4rem 0.75rem;
}

.btn-outline-primary {
    border-color: #dee2e6;
}

.btn-outline-primary:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
    color: #0d6efd;
}
</style>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function printOrder(orderId) {
    window.open(`print_order.php?id=${orderId}`, '_blank');
}
$(document).ready(function() {
    // Handle form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        const queryParams = new URLSearchParams($(this).serialize());
        window.location.href = 'orders.php?' + queryParams.toString();
    });

    // Live search with debounce
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 500);
    });
});
</script>

<?php require_once 'footer.php'; ?>
