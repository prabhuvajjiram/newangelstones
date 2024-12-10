<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get operation ID
$operationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$operationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid operation ID']);
    exit;
}

try {
    // Get operation details
    $query = "
        SELECT 
            boi.*,
            CASE 
                WHEN boi.item_type = 'finished_product' THEN fp.name
                ELSE rm.name
            END as item_name,
            w1.name as source_warehouse_name,
            w2.name as destination_warehouse_name
        FROM batch_operation_items boi
        LEFT JOIN finished_products fp ON boi.item_type = 'finished_product' AND boi.item_id = fp.id
        LEFT JOIN raw_materials rm ON boi.item_type = 'raw_material' AND boi.item_id = rm.id
        LEFT JOIN warehouses w1 ON boi.source_warehouse_id = w1.id
        LEFT JOIN warehouses w2 ON boi.destination_warehouse_id = w2.id
        WHERE boi.batch_id = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$operationId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the details based on operation type
    foreach ($items as &$item) {
        switch ($item['operation_type']) {
            case 'movement':
                $item['details'] = "Moving from {$item['source_warehouse_name']} to {$item['destination_warehouse_name']}";
                break;
            case 'price_update':
                $item['details'] = "{$item['price_update_type']} update by {$item['price_value']}";
                break;
            case 'quantity_adjustment':
                $item['details'] = "{$item['adjustment_type']} by {$item['quantity']}";
                break;
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['items' => $items]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
