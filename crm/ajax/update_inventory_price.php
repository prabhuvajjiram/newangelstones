<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log the received data for debugging
    error_log("Received data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    if (empty($data['id'])) {
        throw new Exception('Missing product ID');
    }
    
    if (!isset($data['unit_price'])) {
        throw new Exception('Missing unit price');
    }
    
    // Convert prices to float and validate
    $unitPrice = floatval($data['unit_price']);
    $markupPercentage = isset($data['markup_percentage']) ? floatval($data['markup_percentage']) : 80.0;
    $productId = intval($data['id']);
    $type = $data['type'];
    
    if ($unitPrice < 0) {
        throw new Exception('Unit price cannot be negative');
    }
    
    if ($markupPercentage < 0) {
        throw new Exception('Markup percentage cannot be negative');
    }
    
    // Calculate final price using the provided markup percentage
    $finalPrice = round($unitPrice * (1 + $markupPercentage / 100), 2);
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        if ($type === 'raw_material') {
            // Update raw materials table directly
            $stmt = $db->prepare("
                UPDATE raw_materials 
                SET unit_price = ?,
                    final_price = ?
                WHERE id = ?
            ");
            $stmt->bindValue(1, $unitPrice, PDO::PARAM_STR);
            $stmt->bindValue(2, $finalPrice, PDO::PARAM_STR);
            $stmt->bindValue(3, $productId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // First get or create supplier_product record
            $stmt = $db->prepare("
                SELECT id FROM supplier_products 
                WHERE product_id = ?
            ");
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->execute();
            $supplierProductId = $stmt->fetchColumn();
            
            if (!$supplierProductId) {
                // Create new supplier_product record if it doesn't exist
                $stmt = $db->prepare("
                    INSERT INTO supplier_products (product_id, supplier_id)
                    VALUES (?, 1)
                ");
                $stmt->bindValue(1, $productId, PDO::PARAM_INT);
                $stmt->execute();
                $supplierProductId = $db->lastInsertId();
            }
            
            // Update end_date of current active price record
            $stmt = $db->prepare("
                UPDATE supplier_product_prices 
                SET end_date = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                WHERE supplier_product_id = ? 
                AND (end_date IS NULL OR end_date > CURRENT_DATE)
            ");
            $stmt->bindValue(1, $supplierProductId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Insert new price record
            $stmt = $db->prepare("
                INSERT INTO supplier_product_prices (
                    supplier_product_id, 
                    unit_price, 
                    markup_percentage,
                    currency,
                    effective_date,
                    end_date
                ) VALUES (?, ?, ?, 'USD', CURRENT_DATE, NULL)
            ");
            $stmt->bindValue(1, $supplierProductId, PDO::PARAM_INT);
            $stmt->bindValue(2, $unitPrice, PDO::PARAM_STR);
            $stmt->bindValue(3, $markupPercentage, PDO::PARAM_STR);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Price updated successfully',
            'data' => [
                'product_id' => $productId,
                'unit_price' => $unitPrice,
                'markup_percentage' => $markupPercentage,
                'final_price' => $finalPrice
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in update_inventory_price.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'received_data' => $data ?? null,
            'error_details' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
