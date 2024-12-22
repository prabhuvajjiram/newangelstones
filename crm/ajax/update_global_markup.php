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
            // First, update end_date for current active prices
            $stmt = $db->prepare("
                UPDATE supplier_product_prices 
                SET end_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                WHERE end_date IS NULL OR end_date > CURRENT_DATE
            ");
            $stmt->execute();
            
            // Then, insert new price records with updated markup
            $stmt = $db->prepare("
                INSERT INTO supplier_product_prices (
                    supplier_product_id,
                    unit_price,
                    markup_percentage,
                    currency,
                    effective_date,
                    end_date
                )
                SELECT 
                    sp.id,
                    COALESCE(
                        (SELECT unit_price 
                         FROM supplier_product_prices spp2 
                         WHERE spp2.supplier_product_id = sp.id 
                         ORDER BY effective_date DESC 
                         LIMIT 1),
                        0
                    ) as unit_price,
                    :markup as markup_percentage,
                    'USD',
                    CURRENT_DATE,
                    NULL
                FROM supplier_products sp
            ");
            $stmt->execute(['markup' => $markup]);
            
        } else {
            // For raw materials, directly update the final_price
            $stmt = $db->prepare("
                UPDATE raw_materials 
                SET final_price = ROUND(unit_price * (1 + :markup/100), 2),
                    last_updated = CURRENT_TIMESTAMP
                WHERE status != 'out_of_stock'
            ");
            $stmt->execute(['markup' => $markup]);
        }
        
        // Get number of updated rows
        $updatedRows = $stmt->rowCount();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated markup to {$markup}%",
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
