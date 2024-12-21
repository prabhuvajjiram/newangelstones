<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Product ID is required');
    }
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    try {
        // Delete unit conversions
        $stmt = $pdo->prepare("DELETE FROM product_unit_conversions WHERE product_id = ?");
        $stmt->execute([$_POST['id']]);
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$_POST['id']]);
        
        if (!$result) {
            throw new Exception('Failed to delete product');
        }
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Product not found');
        }
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in delete_product.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error deleting product: " . $e->getMessage()
    ]);
}
