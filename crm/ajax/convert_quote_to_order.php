<?php
require_once '../session_check.php';
requireLogin();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Debug session
error_log("Debug: Session data in convert_quote_to_order.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get quote ID from request
    $quote_id = isset($_POST['quote_id']) ? (int)$_POST['quote_id'] : 0;
    if (!$quote_id) {
        throw new Exception('Invalid quote ID');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get quote details
    $quote_query = "SELECT q.*, c.company_id 
                   FROM quotes q 
                   LEFT JOIN customers c ON q.customer_id = c.id 
                   WHERE q.id = :quote_id";
    $stmt = $pdo->prepare($quote_query);
    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    $stmt->execute();
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        throw new Exception('Quote not found');
    }

    // Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', $quote_id);

    // Create order
    $order_query = "INSERT INTO orders (customer_id, company_id, quote_id, order_number, 
                    order_date, total_amount, status, created_at) 
                    VALUES (:customer_id, :company_id, :quote_id, :order_number, 
                            NOW(), :total_amount, 'pending', NOW())";
    
    $stmt = $pdo->prepare($order_query);
    $stmt->bindParam(':customer_id', $quote['customer_id'], PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $quote['company_id'], PDO::PARAM_INT);
    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    $stmt->bindParam(':order_number', $order_number, PDO::PARAM_STR);
    $stmt->bindParam(':total_amount', $quote['total_amount'], PDO::PARAM_STR);
    $stmt->execute();
    $order_id = $pdo->lastInsertId();

    // Get quote items
    $items_query = "SELECT * FROM quote_items WHERE quote_id = :quote_id";
    $stmt = $pdo->prepare($items_query);
    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    $stmt->execute();
    $quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create order items
    $item_query = "INSERT INTO order_items (
        order_id, item_type, product_type, model, size, color_id, 
        length, breadth, sqft, cubic_feet, quantity, unit_price, 
        total_price, commission_rate, status, special_monument_id
    ) VALUES (
        :order_id, 'base_product', :product_type, :model, :size, :color_id, 
        :length, :breadth, :sqft, :cubic_feet, :quantity, :unit_price, 
        :total_price, :commission_rate, 'pending', :special_monument_id
    )";
    
    $stmt = $pdo->prepare($item_query);

    foreach ($quote_items as $item) {
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_type', $item['product_type'], PDO::PARAM_STR);
        $stmt->bindParam(':model', $item['model'], PDO::PARAM_STR);
        $stmt->bindParam(':size', $item['size'], PDO::PARAM_STR);
        $stmt->bindParam(':color_id', $item['color_id'], PDO::PARAM_INT);
        $stmt->bindParam(':length', $item['length'], PDO::PARAM_STR);
        $stmt->bindParam(':breadth', $item['breadth'], PDO::PARAM_STR);
        $stmt->bindParam(':sqft', $item['sqft'], PDO::PARAM_STR);
        $stmt->bindParam(':cubic_feet', $item['cubic_feet'], PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':unit_price', $item['unit_price'], PDO::PARAM_STR);
        $stmt->bindParam(':total_price', $item['total_price'], PDO::PARAM_STR);
        $stmt->bindParam(':commission_rate', $item['commission_rate'], PDO::PARAM_STR);
        $stmt->bindParam(':special_monument_id', $item['special_monument_id'], PDO::PARAM_INT);
        $stmt->execute();
        $order_item_id = $pdo->lastInsertId();

        // If this is a product that needs manufacturing, create manufacturing record
        if ($item['product_type'] != 'raw_material') {
            $mfg_query = "INSERT INTO order_items_manufacturing (
                order_item_id, process_status, estimated_completion_date
            ) VALUES (:order_item_id, 'pending', DATE_ADD(NOW(), INTERVAL 14 DAY))";
            
            $stmt_mfg = $pdo->prepare($mfg_query);
            $stmt_mfg->bindParam(':order_item_id', $order_item_id, PDO::PARAM_INT);
            $stmt_mfg->execute();
        }
    }

    // Create initial status history
    $status_query = "INSERT INTO order_status_history (order_id, status, created_by, notes) 
                     VALUES (:order_id, 'pending', :created_by, 'Order created from Quote #" . $quote_id . "')";
    $stmt = $pdo->prepare($status_query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    // Update quote status
    $update_query = "UPDATE quotes SET status = 'Converted' WHERE id = :quote_id";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
    $stmt->execute();

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quote successfully converted to order',
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error in convert_quote_to_order.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error converting quote to order: ' . $e->getMessage()
    ]);
}
