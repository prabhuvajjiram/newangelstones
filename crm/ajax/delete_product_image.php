<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    $imageId = $_POST['imageId'] ?? null;
    
    if (!$imageId) {
        throw new Exception('Image ID is required');
    }
    
    // Get image path before deleting
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        throw new Exception('Image not found');
    }
    
    // Delete file
    $fullPath = '../../' . $image['image_path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    // Delete database record
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $stmt->execute([$imageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Error in delete_product_image.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
