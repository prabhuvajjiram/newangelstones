<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid product ID');
    }

    $pdo = getDbConnection();
    
    $query = "
        SELECT 
            fp.*,
            COALESCE(i.quantity, 0) as quantity,
            i.warehouse_id,
            i.location_details
        FROM finished_products fp
        LEFT JOIN finished_products_inventory i ON fp.id = i.product_id
        WHERE fp.id = :id AND fp.status = 'active'
        LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    echo json_encode([
        'success' => true,
        'product' => $product
    ]);

} catch (Exception $e) {
    error_log('Error in get_finished_product.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
