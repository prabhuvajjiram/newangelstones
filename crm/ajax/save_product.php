<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

class ValidationException extends Exception {}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Log incoming data
    error_log('POST Data: ' . print_r($_POST, true));

    // Validate required fields
    $requiredFields = ['sku', 'name', 'category_id'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // Validate SKU uniqueness
    if (!empty($_POST['sku'])) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $stmt->execute([$_POST['sku'], $_POST['productId'] ?? 0]);
        if ($stmt->fetch()) {
            $errors[] = 'SKU already exists. Please use a different SKU.';
        }
    }

    // Validate numeric fields
    $numericFields = ['length', 'width', 'height', 'weight', 'quantity'];
    foreach ($numericFields as $field) {
        if (!empty($_POST[$field]) && !is_numeric($_POST[$field])) {
            $errors[] = ucfirst($field) . ' must be a number';
        }
    }

    // Check for any validation errors
    if (!empty($errors)) {
        throw new ValidationException(implode(', ', $errors));
    }

    // Prepare product data
    $productData = [
        'sku' => trim($_POST['sku']),
        'name' => trim($_POST['name']),
        'category_id' => $_POST['category_id'],
        'color_id' => !empty($_POST['color_id']) ? $_POST['color_id'] : null,
        'length' => !empty($_POST['length']) ? $_POST['length'] : null,
        'width' => !empty($_POST['width']) ? $_POST['width'] : null,
        'height' => !empty($_POST['height']) ? $_POST['height'] : null,
        'weight' => !empty($_POST['weight']) ? $_POST['weight'] : null,
        'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
        'location_id' => !empty($_POST['location_id']) ? $_POST['location_id'] : null,
        'location_details' => !empty($_POST['location_details']) ? trim($_POST['location_details']) : null,
        'current_stock' => isset($_POST['quantity']) ? intval($_POST['quantity']) : 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Log prepared data
    error_log('Prepared Product Data: ' . print_r($productData, true));

    if (empty($_POST['productId'])) {
        // Insert new product
        $query = "INSERT INTO products (
            sku, name, category_id, color_id, length, width, height, weight,
            description, location_id, location_details, current_stock, updated_at
        ) VALUES (
            :sku, :name, :category_id, :color_id, :length, :width, :height, :weight,
            :description, :location_id, :location_details, :current_stock, :updated_at
        )";
        
        $stmt = $pdo->prepare($query);
        if (!$stmt->execute($productData)) {
            throw new Exception('Failed to insert product: ' . implode(', ', $stmt->errorInfo()));
        }
        $productId = $pdo->lastInsertId();
        error_log('Inserted new product with ID: ' . $productId);
    } else {
        // Update existing product
        $productId = $_POST['productId'];
        $query = "UPDATE products 
                 SET sku = :sku,
                     name = :name,
                     category_id = :category_id,
                     color_id = :color_id,
                     length = :length,
                     width = :width,
                     height = :height,
                     weight = :weight,
                     description = :description,
                     location_id = :location_id,
                     location_details = :location_details,
                     current_stock = :current_stock,
                     updated_at = :updated_at
                 WHERE id = :id";
        
        $productData['id'] = $productId;
        $stmt = $pdo->prepare($query);
        if (!$stmt->execute($productData)) {
            throw new Exception('Failed to update product: ' . implode(', ', $stmt->errorInfo()));
        }
        if ($stmt->rowCount() === 0) {
            throw new Exception('Product not found or no changes made');
        }
        error_log('Updated product with ID: ' . $productId);
    }

    // Handle unit conversions
    $stmt = $pdo->prepare("DELETE FROM product_unit_conversions WHERE product_id = ?");
    $stmt->execute([$productId]);

    if (!empty($_POST['unit_conversions'])) {
        $unitConversions = json_decode($_POST['unit_conversions'], true);
        if (!empty($unitConversions)) {
            $query = "INSERT INTO product_unit_conversions (product_id, unit_type, base_unit, conversion_factor)
                     VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            
            foreach ($unitConversions as $unit) {
                if (!$stmt->execute([
                    $productId,
                    $unit['unit_type'],
                    $unit['base_unit'],
                    $unit['conversion_ratio']
                ])) {
                    throw new Exception('Failed to insert unit conversion: ' . implode(', ', $stmt->errorInfo()));
                }
            }
        }
    }

    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => empty($_POST['productId']) ? 'Product added successfully' : 'Product updated successfully',
        'productId' => $productId
    ]);

} catch (ValidationException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Validation error in save_product.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error in save_product.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving the product: ' . $e->getMessage()
    ]);
}
