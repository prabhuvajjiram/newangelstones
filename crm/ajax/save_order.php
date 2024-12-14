<?php
require_once '../session_check.php';
requireLogin();
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Start transaction
    $pdo->beginTransaction();

    // Generate order number (format: ORD-YYYYMMDD-XXXX)
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT MAX(order_number) as max_number FROM orders WHERE order_number LIKE :prefix");
    $prefix = "ORD-$date-%";
    $stmt->execute(['prefix' => $prefix]);
    $result = $stmt->fetch();
    
    if ($result['max_number']) {
        $number = intval(substr($result['max_number'], -4)) + 1;
    } else {
        $number = 1;
    }
    $order_number = sprintf("ORD-%s-%04d", $date, $number);

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, customer_id, company_id, order_date, status,
            total_amount, paid_amount, notes, created_by, created_at
        ) VALUES (
            :order_number, :customer_id, :company_id, :order_date, :status,
            :total_amount, :paid_amount, :notes, :created_by, NOW()
        )
    ");

    $stmt->execute([
        'order_number' => $order_number,
        'customer_id' => $_POST['customer_id'],
        'company_id' => $_POST['company_id'] ?: null,
        'order_date' => $_POST['order_date'],
        'status' => $_POST['status'],
        'total_amount' => $_POST['total_amount'],
        'paid_amount' => $_POST['paid_amount'],
        'notes' => $_POST['notes'],
        'created_by' => $_SESSION['user_id']
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_type, model, size, color_id,
            length, breadth, sqft, quantity, unit_price, total_price,
            created_at
        ) VALUES (
            :order_id, :product_type, :model, :size, :color_id,
            :length, :breadth, :sqft, :quantity, :unit_price, :total_price,
            NOW()
        )
    ");

    foreach ($_POST['items'] as $item) {
        $stmt->execute([
            'order_id' => $order_id,
            'product_type' => $item['product_type'],
            'model' => $item['model'],
            'size' => $item['size'],
            'color_id' => $item['color_id'],
            'length' => $item['length'],
            'breadth' => $item['breadth'],
            'sqft' => $item['sqft'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price']
        ]);
    }

    // Insert initial status history
    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (
            order_id, status, notes, created_by, created_at
        ) VALUES (
            :order_id, :status, :notes, :created_by, NOW()
        )
    ");

    $stmt->execute([
        'order_id' => $order_id,
        'status' => $_POST['status'],
        'notes' => 'Order created',
        'created_by' => $_SESSION['user_id']
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log("Error in save_order.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the order: ' . $e->getMessage()
    ]);
}
