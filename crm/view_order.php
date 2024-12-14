<?php
session_start();
require_once 'includes/config.php';
require_once 'session_check.php';
require_once 'includes/functions.php';

// Debug session
error_log("Debug: Session data in view_order.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

try {
    $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$order_id) {
        throw new Exception('Invalid order ID');
    }

    // Debug logging
    error_log("Debug: Processing order ID: " . $order_id);

    // Get order details
    $query = "SELECT o.*, 
              c.name as customer_name, 
              c.email as customer_email,
              c.phone as customer_phone,
              c.address as customer_address,
              comp.name as company_name,
              q.quote_number,
              q.id as quote_id
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              LEFT JOIN companies comp ON o.company_id = comp.id 
              LEFT JOIN quotes q ON o.quote_id = q.id
              WHERE o.order_id = :order_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Debug: Order details: " . print_r($order, true));

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items with all details
    $items_query = "SELECT oi.*, 
                   scr.color_name,
                   m.process_status,
                   m.estimated_completion_date,
                   m.actual_completion_date,
                   CASE 
                       WHEN qi.id IS NOT NULL THEN qi.model
                       WHEN oi.item_type = 'finished_product' THEN fp.name
                       ELSE oi.model
                   END as product_model,
                   CASE 
                       WHEN qi.id IS NOT NULL THEN CONCAT(qi.product_type, ' - ', qi.model, ' (', qi.size, ')')
                       WHEN oi.item_type = 'raw_material' THEN CONCAT('Raw Material - ', COALESCE(scr.color_name, ''), ' (', oi.length, 'x', oi.breadth, 'x', COALESCE(oi.height, 0), ')')
                       WHEN oi.item_type = 'finished_product' THEN CONCAT(COALESCE(fp.name, ''), ' - ', COALESCE(fp.description, ''))
                       WHEN oi.item_type IN ('base_product', 'marker_product', 'sertop_product', 'slant_product') 
                           THEN CONCAT(UPPER(SUBSTRING(oi.item_type, 1, LENGTH(oi.item_type) - 8)), ' - Model ', oi.model, 
                                     ' (', oi.size, ' - ', 
                                     oi.length, 'x', oi.breadth, 
                                     CASE WHEN oi.height IS NOT NULL AND oi.height > 0 THEN CONCAT('x', oi.height) ELSE '' END,
                                     ')')
                       ELSE CONCAT(oi.product_type, ' - ', oi.model, ' (', oi.size, ')')
                   END as product_description,
                   CASE 
                       WHEN qi.id IS NOT NULL THEN qi.product_type
                       WHEN oi.item_type = 'raw_material' THEN rm.warehouse_name
                       WHEN oi.item_type = 'finished_product' THEN pc.name
                       ELSE UPPER(SUBSTRING(oi.item_type, 1, LENGTH(oi.item_type) - 8))
                   END as category
                   FROM order_items oi
                   LEFT JOIN orders o ON oi.order_id = o.order_id
                   LEFT JOIN quotes q ON o.quote_id = q.id
                   LEFT JOIN quote_items qi ON q.id = qi.quote_id 
                       AND oi.length = qi.length 
                       AND oi.breadth = qi.breadth 
                       AND oi.model = qi.model 
                       AND oi.size = qi.size
                   LEFT JOIN stone_color_rates scr ON oi.color_id = scr.id
                   LEFT JOIN order_items_manufacturing m ON oi.id = m.order_item_id
                   LEFT JOIN raw_materials rm ON oi.product_id = rm.id AND oi.item_type = 'raw_material'
                   LEFT JOIN finished_products fp ON oi.product_id = fp.id AND oi.item_type = 'finished_product'
                   LEFT JOIN product_categories pc ON fp.category_id = pc.id
                   WHERE oi.order_id = :order_id";
    
    $stmt = $pdo->prepare($items_query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Debug: Number of items found: " . count($items));
    error_log("Debug: Items details: " . print_r($items, true));

    // Get status history with user details
    $history_query = "SELECT h.*, 
                     u.first_name, 
                     u.last_name
                     FROM order_status_history h
                     LEFT JOIN users u ON h.created_by = u.id
                     WHERE h.order_id = :order_id
                     ORDER BY h.created_at DESC";
    
    $stmt = $pdo->prepare($history_query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $page_title = "View Order #" . $order['order_number'];
    require_once 'header.php';
    require_once 'navbar.php';

} catch (Exception $e) {
    error_log("Error in view_order.php: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                        </div>
                        <div class="col text-end">
                            <a href="orders.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Orders
                            </a>
                            <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit Order
                            </a>
                            <?php if ($order['quote_number']): ?>
                                <a href="view_quote.php?id=<?php echo $order['quote_id']; ?>" class="btn btn-info">
                                    <i class="bi bi-file-text"></i> View Quote
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Customer Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <?php if ($order['company_name']): ?>
                                <p><strong>Company:</strong> <?php echo htmlspecialchars($order['company_name']); ?></p>
                            <?php endif; ?>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Order Details</h6>
                            <p><strong>Order Date:</strong> <?php echo date('Y-m-d', strtotime($order['order_date'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Paid Amount:</strong> $<?php echo number_format($order['paid_amount'], 2); ?></p>
                            <?php if ($order['quote_number']): ?>
                                <p><strong>Quote Number:</strong> 
                                    <a href="view_quote.php?id=<?php echo $order['quote_id']; ?>">
                                        <?php echo htmlspecialchars($order['quote_number']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                            <?php if ($order['updated_at'] !== $order['created_at']): ?>
                                <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($order['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Order Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item Type</th>
                                                    <th>Product Type</th>
                                                    <th>Model</th>
                                                    <th>Size</th>
                                                    <th>Color</th>
                                                    <th>Dimensions</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total Price</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($items)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center">No items found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td><?php echo ucfirst(htmlspecialchars($item['item_type'])); ?></td>
                                                            <td><?php echo ucfirst(htmlspecialchars($item['product_type'])); ?></td>
                                                            <td><?php echo htmlspecialchars($item['product_model'] ?? $item['model']); ?></td>
                                                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                                                            <td><?php echo htmlspecialchars($item['color_name']); ?></td>
                                                            <td>
                                                                L: <?php echo htmlspecialchars($item['length']); ?>" × 
                                                                W: <?php echo htmlspecialchars($item['breadth']); ?>"
                                                                <?php if (!empty($item['height'])): ?>
                                                                    × H: <?php echo htmlspecialchars($item['height']); ?>"
                                                                <?php endif; ?>
                                                                <?php if (!empty($item['sqft'])): ?>
                                                                    <br>Area: <?php echo htmlspecialchars($item['sqft']); ?> sq.ft
                                                                <?php endif; ?>
                                                                <?php if (!empty($item['cubic_feet'])): ?>
                                                                    <br>Volume: <?php echo htmlspecialchars($item['cubic_feet']); ?> cu.ft
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                                            <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                                                            <td>
                                                                <?php if ($item['process_status']): ?>
                                                                    <span class="badge rounded-pill bg-<?php 
                                                                        switch($item['process_status']) {
                                                                            case 'pending':
                                                                                echo 'warning';
                                                                                break;
                                                                            case 'in_progress':
                                                                                echo 'info';
                                                                                break;
                                                                            case 'completed':
                                                                                echo 'success';
                                                                                break;
                                                                            default:
                                                                                echo 'secondary';
                                                                        }
                                                                    ?>">
                                                                        <?php echo ucfirst($item['process_status']); ?>
                                                                    </span>
                                                                    <?php if ($item['estimated_completion_date']): ?>
                                                                        <br><small class="text-muted">Est: <?php echo date('Y-m-d', strtotime($item['estimated_completion_date'])); ?></small>
                                                                    <?php endif; ?>
                                                                    <?php if ($item['actual_completion_date']): ?>
                                                                        <br><small class="text-muted">Done: <?php echo date('Y-m-d', strtotime($item['actual_completion_date'])); ?></small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Not Started</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-light fw-bold">
                                                        <td colspan="6" class="text-end">Total:</td>
                                                        <td class="text-center"><?php echo array_sum(array_column($items, 'quantity')); ?></td>
                                                        <td></td>
                                                        <td class="text-end">$<?php echo number_format(array_sum(array_column($items, 'total_price')), 2); ?></td>
                                                        <td></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Manufacturing Status History</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Status</th>
                                                    <th>Updated By</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($status_history)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-3">No status history available</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($status_history as $history): ?>
                                                        <tr>
                                                            <td><?php echo date('Y-m-d H:i', strtotime($history['created_at'])); ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill bg-<?php 
                                                                    switch($history['status']) {
                                                                        case 'pending':
                                                                            echo 'warning';
                                                                            break;
                                                                        case 'processing':
                                                                            echo 'info';
                                                                            break;
                                                                        case 'shipped':
                                                                            echo 'primary';
                                                                            break;
                                                                        case 'delivered':
                                                                            echo 'success';
                                                                            break;
                                                                        case 'cancelled':
                                                                            echo 'danger';
                                                                            break;
                                                                        default:
                                                                            echo 'secondary';
                                                                    }
                                                                ?>">
                                                                    <?php echo ucfirst($history['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($history['first_name'] && $history['last_name']): ?>
                                                                    <?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">System</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($history['notes'])): ?>
                                                                    <?php echo nl2br(htmlspecialchars($history['notes'])); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manufacturing Details Modal -->
<div class="modal fade" id="manufacturingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Manufacturing Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Manufacturing status form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

function updateManufacturingStatus(itemId) {
    // Load manufacturing status form into modal
    $.get('ajax/get_manufacturing_form.php', { item_id: itemId }, function(response) {
        $('#manufacturingModal .modal-body').html(response);
        $('#manufacturingModal').modal('show');
    });
}
</script>
