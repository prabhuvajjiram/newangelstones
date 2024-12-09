<?php
// Prevent any output before headers
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Set content type to JSON
header('Content-Type: application/json');

require_once '../includes/config.php';

function logError($message, $data = []) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . " - Data: " . json_encode($data));
}

// Ensure we catch any errors that might happen after headers are sent
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $response = json_encode([
            'success' => false,
            'message' => 'A server error occurred: ' . $error['message']
        ]);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo $response;
        exit;
    }
});

try {
    // Log incoming data
    logError("Incoming POST data", $_POST);

    // Validate required fields
    $requiredFields = ['color_id', 'length', 'width', 'height', 'quantity', 'location'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Sanitize and validate inputs
    $colorId = filter_var($_POST['color_id'], FILTER_VALIDATE_INT);
    $length = filter_var($_POST['length'], FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'], FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'], FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_FLOAT);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    // min_stock_level is optional, defaults to 0
    $minStockLevel = isset($_POST['min_stock_level']) && $_POST['min_stock_level'] !== '' 
        ? filter_var($_POST['min_stock_level'], FILTER_VALIDATE_INT) 
        : 0;
    $materialId = isset($_POST['material_id']) ? filter_var($_POST['material_id'], FILTER_VALIDATE_INT) : null;

    // Additional validation
    if ($colorId === false || $colorId <= 0) {
        throw new Exception("Invalid color selected");
    }
    if ($length === false || $length <= 0) {
        throw new Exception("Length must be a positive number");
    }
    if ($width === false || $width <= 0) {
        throw new Exception("Width must be a positive number");
    }
    if ($height === false || $height <= 0) {
        throw new Exception("Height must be a positive number");
    }
    if ($quantity === false) {
        throw new Exception("Quantity must be a valid number");
    }
    if (empty($location)) {
        throw new Exception("Location cannot be empty");
    }
    if ($minStockLevel === false || $minStockLevel < 0) {
        throw new Exception("Minimum stock level must be a non-negative number");
    }

    // Calculate status based on quantity and min_stock_level
    $status = 'in_stock';
    if ($quantity <= 0) {
        $status = 'out_of_stock';
    } elseif ($quantity <= $minStockLevel) {
        $status = 'low_stock';
    }

    // Log sanitized data
    logError("Sanitized data", [
        'color_id' => $colorId,
        'length' => $length,
        'width' => $width,
        'height' => $height,
        'quantity' => $quantity,
        'location' => $location,
        'min_stock_level' => $minStockLevel,
        'status' => $status,
        'material_id' => $materialId
    ]);

    // Verify database connection
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        if ($materialId) {
            // Update existing material
            $stmt = $pdo->prepare("
                UPDATE raw_materials 
                SET color_id = ?, length = ?, width = ?, height = ?,
                    quantity = ?, location = ?, min_stock_level = ?,
                    status = ?, last_updated = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $colorId, $length, $width, $height,
                $quantity, $location, $minStockLevel,
                $status, $materialId
            ]);
        } else {
            // Insert new material
            $stmt = $pdo->prepare("
                INSERT INTO raw_materials 
                (color_id, length, width, height, quantity, location, 
                min_stock_level, status, last_updated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $colorId, $length, $width, $height,
                $quantity, $location, $minStockLevel,
                $status
            ]);
        }

        // Commit transaction
        $pdo->commit();

        // Clear any buffered output
        ob_clean();

        echo json_encode([
            'success' => true,
            'message' => 'Material saved successfully',
            'material_id' => $materialId ?? $pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logError("Error in save_raw_material.php: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    // Clear any buffered output
    ob_clean();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
