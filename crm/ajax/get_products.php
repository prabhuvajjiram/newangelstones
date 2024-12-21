<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    $pdo = getDbConnection();
    
    // Build the query with joins to get category and color names
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
        ORDER BY p.sku ASC
    ";
    
    $stmt = $pdo->query($query);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    foreach ($products as &$product) {
        // Convert numeric values to proper format
        $product['length'] = floatval($product['length']);
        $product['width'] = floatval($product['width']);
        $product['height'] = floatval($product['height']);
        $product['weight'] = floatval($product['weight']);
        $product['current_stock'] = intval($product['current_stock']);
        
        // Add status based on stock level
        if ($product['current_stock'] <= 0) {
            $product['status'] = 'Out of Stock';
        } elseif ($product['current_stock'] < 5) {
            $product['status'] = 'Low Stock';
        } else {
            $product['status'] = 'In Stock';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_products.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error loading products: " . $e->getMessage()
    ]);
}
