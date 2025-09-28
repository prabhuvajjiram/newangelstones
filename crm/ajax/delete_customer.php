<?php
require_once '../includes/config.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    // Check if user has permission to delete customers
    requireStaffOrAdmin();
    
    // Get raw POST data and decode
    $raw_data = file_get_contents('php://input');
    $data = json_decode($raw_data, true);
    
    if ($data === null) {
        throw new Exception('Invalid JSON data received');
    }
    
    if (empty($data['id'])) {
        throw new Exception('Customer ID is required');
    }
    
    $customer_id = (int)$data['id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Check if customer has any quotes or orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $quote_count = $stmt->fetchColumn();
        
        if ($quote_count > 0) {
            throw new Exception("Cannot delete customer. Customer has {$quote_count} associated quotes. Please archive the customer instead.");
        }
        
        // Check if customer exists
        $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception('Customer not found');
        }
        
        // Delete the customer
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Customer '{$customer['name']}' has been deleted successfully."
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in delete_customer.php: " . $e->getMessage());
    
    // Handle specific database errors
    if ($e->getCode() == 23000) {
        $error_message = 'Cannot delete customer due to existing references in the system. Please archive the customer instead.';
    } else {
        $error_message = 'Database error occurred while deleting customer.';
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
} catch (Exception $e) {
    error_log("General error in delete_customer.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
