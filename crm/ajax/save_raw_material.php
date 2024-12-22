<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Ensure we're sending JSON
header('Content-Type: application/json');

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    // Get database connection
    $pdo = getDbConnection();
    
    // Log incoming data
    error_log("Incoming POST data: " . print_r($_POST, true));
    
    // Required fields validation
    $requiredFields = ['color_id', 'length', 'width', 'height', 'quantity', 'warehouse_id', 'warehouse_name'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missingFields[] = $field;
        }
    }
    if (!empty($missingFields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missingFields));
    }

    // Sanitize and validate inputs
    $colorId = filter_var($_POST['color_id'], FILTER_VALIDATE_INT);
    $length = filter_var($_POST['length'], FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'], FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'], FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $warehouseId = filter_var($_POST['warehouse_id'], FILTER_VALIDATE_INT);
    $warehouseName = trim(htmlspecialchars($_POST['warehouse_name']));
    
    // Optional fields
    $locationDetails = isset($_POST['location_details']) && trim($_POST['location_details']) !== '' 
        ? trim(htmlspecialchars($_POST['location_details'])) 
        : null;
    
    $minStock = isset($_POST['min_stock_level']) && trim($_POST['min_stock_level']) !== '' 
        ? filter_var($_POST['min_stock_level'], FILTER_VALIDATE_INT) 
        : 1;

    // Validate numeric inputs
    if (!$colorId || !$length || !$width || !$height || !$quantity || !$warehouseId) {
        throw new Exception("Invalid numeric values provided");
    }

    // Set status based on quantity
    $status = 'in_stock';
    if ($quantity <= 0) {
        $status = 'out_of_stock';
    } elseif ($quantity <= $minStock) {
        $status = 'low_stock';
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Prepare data array for binding
        $data = [
            ':color_id' => $colorId,
            ':length' => $length,
            ':width' => $width,
            ':height' => $height,
            ':quantity' => $quantity,
            ':warehouse_id' => $warehouseId,
            ':warehouse_name' => $warehouseName,
            ':location_details' => $locationDetails,
            ':min_stock_level' => $minStock,
            ':status' => $status
        ];

        // Check if we're updating or inserting
        if (isset($_POST['material_id']) && !empty($_POST['material_id'])) {
            $materialId = filter_var($_POST['material_id'], FILTER_VALIDATE_INT);
            if (!$materialId) {
                throw new Exception("Invalid material ID");
            }

            $data[':material_id'] = $materialId;
            
            $stmt = $pdo->prepare("
                UPDATE raw_materials 
                SET color_id = :color_id, 
                    length = :length, 
                    width = :width, 
                    height = :height, 
                    quantity = :quantity, 
                    warehouse_id = :warehouse_id, 
                    warehouse_name = :warehouse_name, 
                    location_details = :location_details, 
                    min_stock_level = :min_stock_level, 
                    status = :status,
                    last_updated = CURRENT_TIMESTAMP
                WHERE id = :material_id
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO raw_materials 
                (color_id, length, width, height, quantity, warehouse_id, 
                warehouse_name, location_details, min_stock_level, status, last_updated) 
                VALUES (:color_id, :length, :width, :height, :quantity, :warehouse_id,
                        :warehouse_name, :location_details, :min_stock_level, :status, CURRENT_TIMESTAMP)
            ");
        }

        // Execute the query
        if (!$stmt->execute($data)) {
            throw new Exception("Database error: " . implode(', ', $stmt->errorInfo()));
        }

        // Commit transaction
        $pdo->commit();

        // Clear any buffered output before sending JSON
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Material saved successfully',
            'id' => isset($materialId) ? $materialId : $pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Clear any buffered output before sending error
    while (ob_get_level()) {
        ob_end_clean();
    }

    error_log("Error in save_raw_material.php: " . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
