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
    error_log("Received global markup data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($data['type']) || !isset($data['markup'])) {
        throw new Exception('Missing required fields');
    }
    
    // Convert markup to float and validate
    $markup = floatval($data['markup']);
    if ($markup < 0) {
        throw new Exception('Markup percentage cannot be negative');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Prepare and execute query based on type
        if ($data['type'] === 'finished_product') {
            $stmt = $db->prepare("
                UPDATE finished_products 
                SET final_price = ROUND(unit_price * (1 + :markup/100), 2),
                    updated_at = CURRENT_TIMESTAMP
                WHERE status = 'active'
            ");
        } else {
            $stmt = $db->prepare("
                UPDATE raw_materials 
                SET final_price = ROUND(unit_price * (1 + :markup/100), 2),
                    last_updated = CURRENT_TIMESTAMP
                WHERE status != 'out_of_stock'
            ");
        }
        
        $stmt->execute(['markup' => $markup]);
        
        // Get number of updated rows
        $updatedRows = $stmt->rowCount();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated {$updatedRows} items with {$markup}% markup",
            'updatedRows' => $updatedRows
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in update_global_markup.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
