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
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Get current prices before update
        if ($data['type'] === 'finished_product') {
            $stmt = $db->prepare("SELECT unit_price, final_price FROM finished_products WHERE id = ?");
        } else {
            $stmt = $db->prepare("SELECT unit_price, final_price FROM raw_materials WHERE id = ?");
        }
        $stmt->execute([$data['id']]);
        $oldPrices = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate markup percentage if both prices are provided
        $markupPercentage = null;
        if ($unitPrice > 0 && $finalPrice > 0) {
            $markupPercentage = (($finalPrice / $unitPrice) - 1) * 100;
        }
        
        // Update prices
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
        
        $stmt->execute([
            'id' => $data['id'],
            'unit_price' => $unitPrice,
            'final_price' => $finalPrice
        ]);
        
        // Log price change in history
        $stmt = $db->prepare("
            INSERT INTO price_change_history (
                item_type, item_id, old_unit_price, new_unit_price,
                old_final_price, new_final_price, change_type,
                markup_percentage, changed_by, reason
            ) VALUES (
                :item_type, :item_id, :old_unit_price, :new_unit_price,
                :old_final_price, :new_final_price, :change_type,
                :markup_percentage, :changed_by, :reason
            )
        ");
        
        $stmt->execute([
            'item_type' => $data['type'],
            'item_id' => $data['id'],
            'old_unit_price' => $oldPrices['unit_price'],
            'new_unit_price' => $unitPrice,
            'old_final_price' => $oldPrices['final_price'],
            'new_final_price' => $finalPrice,
            'change_type' => 'individual',
            'markup_percentage' => $markupPercentage,
            'changed_by' => $_SESSION['user_id'],
            'reason' => $data['reason'] ?? 'Price update'
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Price updated successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in update_inventory_price.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
