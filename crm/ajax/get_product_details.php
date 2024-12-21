<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Product ID is required');
    }
    
    $pdo = getDbConnection();
    
    $query = "
        SELECT 
            p.*,
            pc.name as category_name,
            scr.color_name as color_name,
            w.name as location_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        LEFT JOIN stone_color_rates scr ON p.color_id = scr.id
        LEFT JOIN warehouses w ON p.location_id = w.id
        WHERE p.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Convert numeric values
    $product['length'] = floatval($product['length']);
    $product['width'] = floatval($product['width']);
    $product['height'] = floatval($product['height']);
    $product['weight'] = floatval($product['weight']);
    $product['current_stock'] = intval($product['current_stock']);
    
    // Get unit conversions
    $query = "SELECT * FROM product_unit_conversions WHERE product_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_GET['id']]);
    $product['unit_conversions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_product_details.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error fetching product details: " . $e->getMessage()
    ]);
}
