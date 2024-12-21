<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    $productId = $_GET['productId'] ?? null;
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT id, image_path, created_at
        FROM product_images
        WHERE product_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'images' => array_map(function($image) {
            return [
                'id' => $image['id'],
                'path' => $image['image_path'],
                'created_at' => $image['created_at']
            ];
        }, $images)
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_product_images.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
