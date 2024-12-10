<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    $productId = $_POST['productId'] ?? null;
    $data = [
        'sku' => $_POST['sku'],
        'name' => $_POST['name'],
        'category_id' => $_POST['category'],
        'color_id' => $_POST['color'],
        'length' => $_POST['length'],
        'width' => $_POST['width'],
        'height' => $_POST['height'],
        'weight' => $_POST['weight'] ?: null,
        'description' => $_POST['description'] ?: null
    ];
    
    $pdo->beginTransaction();
    
    if ($productId) {
        // Update existing product
        $sql = "UPDATE finished_products SET 
                sku = :sku,
                name = :name,
                category_id = :category_id,
                color_id = :color_id,
                length = :length,
                width = :width,
                height = :height,
                weight = :weight,
                description = :description
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $data['id'] = $productId;
        $stmt->execute($data);
        
    } else {
        // Insert new product
        $sql = "INSERT INTO finished_products 
                (sku, name, category_id, color_id, length, width, height, weight, description)
                VALUES 
                (:sku, :name, :category_id, :color_id, :length, :width, :height, :weight, :description)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        $productId = $pdo->lastInsertId();
    }
    
    // Handle inventory entry
    if ($productId && isset($_POST['quantity']) && isset($_POST['warehouse'])) {
        $inventoryData = [
            'product_id' => $productId,
            'warehouse_id' => $_POST['warehouse'],
            'quantity' => $_POST['quantity'],
            'location_details' => $_POST['location_details'] ?: null
        ];
        
        // Check if inventory record exists
        $checkSql = "SELECT id FROM finished_products_inventory 
                    WHERE product_id = :product_id AND warehouse_id = :warehouse_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'product_id' => $productId,
            'warehouse_id' => $_POST['warehouse']
        ]);
        
        if ($checkStmt->fetch()) {
            // Update existing inventory
            $inventorySql = "UPDATE finished_products_inventory 
                            SET quantity = :quantity,
                                location_details = :location_details
                            WHERE product_id = :product_id 
                            AND warehouse_id = :warehouse_id";
        } else {
            // Insert new inventory record
            $inventorySql = "INSERT INTO finished_products_inventory 
                            (product_id, warehouse_id, quantity, location_details)
                            VALUES 
                            (:product_id, :warehouse_id, :quantity, :location_details)";
        }
        
        $inventoryStmt = $pdo->prepare($inventorySql);
        $inventoryStmt->execute($inventoryData);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product saved successfully',
        'id' => $productId
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Error in save_finished_product.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
