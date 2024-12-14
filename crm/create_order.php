<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Debug session
error_log("Debug: Session data in create_order.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

try {
    // Fetch customers for dropdown
    $stmt = $pdo->query("SELECT id, name, email, phone FROM customers ORDER BY name");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch companies for dropdown
    $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch colors for dropdown
    $stmt = $pdo->query("SELECT id, color_name as name FROM stone_color_rates ORDER BY color_name");
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $page_title = "Create New Order";
    require_once 'header.php';
    require_once 'navbar.php';
} catch (Exception $e) {
    error_log("Error in create_order.php: " . $e->getMessage());
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
                            <h5 class="mb-0">Create New Order</h5>
                        </div>
                        <div class="col text-end">
                            <a href="orders.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Orders
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="createOrderForm" method="POST" action="ajax/save_order.php">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Customer Information</h6>
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer</label>
                                    <select class="form-select" id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>">
                                                <?php echo htmlspecialchars($customer['name']); ?> 
                                                (<?php echo htmlspecialchars($customer['email']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Company (Optional)</label>
                                    <select class="form-select" id="company_id" name="company_id">
                                        <option value="">Select Company</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company['id']; ?>">
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-3">Order Details</h6>
                                <div class="mb-3">
                                    <label for="order_date" class="form-label">Order Date</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Order Items</h6>
                                <div id="orderItems">
                                    <!-- Order items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-secondary mt-3" onclick="addOrderItem()">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount</label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                           step="0.01" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="paid_amount" class="form-label">Paid Amount</label>
                                    <input type="number" class="form-control" id="paid_amount" name="paid_amount" 
                                           step="0.01" value="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Create Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="orderItemTemplate">
    <div class="order-item card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Product Type</label>
                        <select class="form-select" name="items[{index}][product_type]" required>
                            <option value="">Select Type</option>
                            <option value="stone">Stone</option>
                            <option value="tile">Tile</option>
                            <option value="slab">Slab</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="items[{index}][model]" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <input type="text" class="form-control" name="items[{index}][size]" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <select class="form-select" name="items[{index}][color_id]" required>
                            <option value="">Select Color</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?php echo $color['id']; ?>">
                                    <?php echo htmlspecialchars($color['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Length (inches)</label>
                        <input type="number" class="form-control dimension-input" 
                               name="items[{index}][length]" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Width (inches)</label>
                        <input type="number" class="form-control dimension-input" 
                               name="items[{index}][breadth]" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Square Feet</label>
                        <input type="number" class="form-control" name="items[{index}][sqft]" 
                               step="0.01" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control quantity-input" 
                               name="items[{index}][quantity]" value="1" min="1" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Unit Price ($)</label>
                        <input type="number" class="form-control price-input" 
                               name="items[{index}][unit_price]" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Total Price ($)</label>
                        <input type="number" class="form-control" name="items[{index}][total_price]" 
                               step="0.01" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeOrderItem(this)">
                        <i class="bi bi-trash"></i> Remove Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let itemIndex = 0;

function addOrderItem() {
    const template = document.getElementById('orderItemTemplate');
    const orderItems = document.getElementById('orderItems');
    const newItem = template.content.cloneNode(true);

    // Replace {index} placeholder with actual index
    const elements = newItem.querySelectorAll('[name*="{index}"]');
    elements.forEach(element => {
        element.name = element.name.replace('{index}', itemIndex);
    });

    orderItems.appendChild(newItem);
    setupEventListeners(itemIndex);
    itemIndex++;
}

function removeOrderItem(button) {
    const orderItem = button.closest('.order-item');
    orderItem.remove();
    updateTotalAmount();
}

function setupEventListeners(index) {
    const item = document.querySelector(`[name="items[${index}][length]"]`).closest('.order-item');
    
    // Calculate square feet when dimensions change
    const dimensionInputs = item.querySelectorAll('.dimension-input');
    dimensionInputs.forEach(input => {
        input.addEventListener('input', () => {
            calculateSquareFeet(item);
            calculateItemTotal(item);
        });
    });

    // Calculate total price when quantity or unit price changes
    const quantityInput = item.querySelector('.quantity-input');
    const priceInput = item.querySelector('.price-input');
    
    quantityInput.addEventListener('input', () => calculateItemTotal(item));
    priceInput.addEventListener('input', () => calculateItemTotal(item));
}

function calculateSquareFeet(item) {
    const length = parseFloat(item.querySelector('[name$="[length]"]').value) || 0;
    const width = parseFloat(item.querySelector('[name$="[breadth]"]').value) || 0;
    const sqft = (length * width) / 144; // Convert square inches to square feet
    item.querySelector('[name$="[sqft]"]').value = sqft.toFixed(2);
}

function calculateItemTotal(item) {
    const quantity = parseInt(item.querySelector('.quantity-input').value) || 0;
    const unitPrice = parseFloat(item.querySelector('.price-input').value) || 0;
    const total = quantity * unitPrice;
    item.querySelector('[name$="[total_price]"]').value = total.toFixed(2);
    updateTotalAmount();
}

function updateTotalAmount() {
    const totalPriceInputs = document.querySelectorAll('[name$="[total_price]"]');
    let total = 0;
    totalPriceInputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total_amount').value = total.toFixed(2);
}

// Add first item on page load
document.addEventListener('DOMContentLoaded', () => {
    addOrderItem();

    // Form submission handler
    document.getElementById('createOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        // Submit form via AJAX
        const formData = new FormData(this);
        fetch('ajax/save_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `view_order.php?id=${data.order_id}`;
            } else {
                alert('Error creating order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the order.');
        });
    });
});
</script>
