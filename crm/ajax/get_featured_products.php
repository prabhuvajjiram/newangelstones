<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDbConnection();
    
    // Get page number from request, default to 1
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $productsPerPage = 3; 
    $offset = ($page - 1) * $productsPerPage;
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        WHERE p.current_stock > 0
    ";
    $countStmt = $db->query($countQuery);
    $totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get featured products with their images
    $query = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            GROUP_CONCAT(DISTINCT pi.image_path) as image_paths,
            GROUP_CONCAT(DISTINCT pi.thumb_path) as thumb_paths,
            p.current_stock,
            p.description
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        WHERE p.current_stock > 0
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$productsPerPage, $offset]);
    
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Process image paths
        $imagePaths = $row['image_paths'] ? explode(',', $row['image_paths']) : [];
        $thumbPaths = $row['thumb_paths'] ? explode(',', $row['thumb_paths']) : [];
        
        // Create images array with full paths
        $images = [];
        foreach ($imagePaths as $index => $path) {
            if (!empty($path)) {
                $images[] = [
                    'path' => '/' . ltrim($path, '/'),
                    'thumb' => isset($thumbPaths[$index]) ? '/' . ltrim($thumbPaths[$index], '/') : '/' . ltrim($path, '/'),
                    'name' => $row['name']
                ];
            }
        }
        
        // Build product data
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'description' => $row['description'] ?? '',
            'current_stock' => (int)$row['current_stock'],
            'images' => $images
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'totalProducts' => (int)$totalProducts,
        'currentPage' => (int)$page
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_featured_products.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load featured products'
    ]);
}
