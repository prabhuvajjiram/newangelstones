<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid product ID');
    }

    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete unit conversions first
        $stmt = $pdo->prepare("DELETE FROM product_unit_conversions WHERE product_id = :id");
        $stmt->execute(['id' => $_POST['id']]);
        
        // Then delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $result = $stmt->execute(['id' => $_POST['id']]);
        
        if (!$result) {
            throw new Exception('Failed to delete product');
        }
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Product not found');
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in delete_finished_product.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
