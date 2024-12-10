<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log the received data
    error_log("Received data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($data['id']) || empty($data['type']) || !isset($data['unit_price'])) {
        throw new Exception('Missing required fields');
    }
    
    // Convert prices to float and validate
    $unitPrice = floatval($data['unit_price']);
    $finalPrice = !empty($data['final_price']) ? floatval($data['final_price']) : null;
    
    if ($unitPrice < 0) {
        throw new Exception('Unit price cannot be negative');
    }
    
    if ($finalPrice !== null && $finalPrice < 0) {
        throw new Exception('Final price cannot be negative');
    }
    
    // Prepare and execute query based on type
    if ($data['type'] === 'finished_product') {
        $stmt = $db->prepare("
            UPDATE finished_products 
            SET unit_price = :unit_price,
                final_price = :final_price,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
    } else {
        $stmt = $db->prepare("
            UPDATE raw_materials 
            SET unit_price = :unit_price,
                final_price = :final_price,
                last_updated = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
    }
    
    $params = [
        'id' => $data['id'],
        'unit_price' => $unitPrice,
        'final_price' => $finalPrice
    ];
    
    // Log the SQL parameters
    error_log("SQL parameters: " . print_r($params, true));
    
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Price updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update price');
    }
    
} catch (Exception $e) {
    error_log("Error in update_inventory_price.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
