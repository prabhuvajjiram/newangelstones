<?php
// Start output buffering to catch any unwanted output
ob_start();

// Enable error reporting for debugging but disable display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting save_quote.php");

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

// Clean any output buffered so far
ob_clean();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null) {
    // Clean any output buffered so far
    ob_clean();
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    
    // End output buffering and flush
    ob_end_flush();
    exit;
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        sendJsonResponse(false, 'User not logged in');
    }

    // Get JSON data
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        error_log("Empty input received");
        sendJsonResponse(false, 'No input received');
    }

    $data = json_decode($jsonInput, true);
    if ($data === null) {
        error_log("JSON decode error: " . json_last_error_msg());
        sendJsonResponse(false, 'Invalid JSON: ' . json_last_error_msg());
    }

    // Validate required fields
    if (empty($data['customer_id'])) {
        sendJsonResponse(false, 'Customer ID is required');
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        sendJsonResponse(false, 'No items in quote');
    }

    if (!isset($data['commission_rate'])) {
        sendJsonResponse(false, 'Commission rate is required');
    }

    foreach ($data['items'] as $item) {
        if (empty($item['product_type'])) {
            sendJsonResponse(false, 'Product type is required');
        }

        if (empty($item['model'])) {
            sendJsonResponse(false, 'Model is required');
        }

        if (empty($item['size'])) {
            sendJsonResponse(false, 'Size is required');
        }

        if (empty($item['color_id'])) {
            sendJsonResponse(false, 'Color ID is required');
        }

        if (empty($item['length'])) {
            sendJsonResponse(false, 'Length is required');
        }

        if (empty($item['breadth'])) {
            sendJsonResponse(false, 'Breadth is required');
        }

        if (empty($item['sqft'])) {
            sendJsonResponse(false, 'SQFT is required');
        }

        if (empty($item['cubic_feet'])) {
            sendJsonResponse(false, 'Cubic feet is required');
        }

        if (empty($item['quantity'])) {
            sendJsonResponse(false, 'Quantity is required');
        }

        if (empty($item['unit_price'])) {
            sendJsonResponse(false, 'Unit price is required');
        }

        if (empty($item['total_price'])) {
            sendJsonResponse(false, 'Total price is required');
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Calculate totals
        $total_amount = 0;
        $commission_amount = 0;

        foreach ($data['items'] as $item) {
            $total_amount += $item['total_price'] * $item['quantity'];
            $commission_amount = $total_amount * ($data['commission_rate'] / 100);
        }

        // Generate unique quote number
        $quote_number = 'Q' . date('Ymd') . sprintf('%04d', rand(1, 9999));

        // Insert quote
        $stmt = $pdo->prepare("
            INSERT INTO quotes 
            (quote_number, customer_id, total_amount, commission_rate, commission_amount, status, valid_until, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
        ");

        $stmt->execute([
            $quote_number,
            $data['customer_id'],
            $total_amount,
            $data['commission_rate'],
            $commission_amount
        ]);

        $quote_id = $pdo->lastInsertId();

        // Insert quote items
        $stmt = $pdo->prepare("
            INSERT INTO quote_items 
            (quote_id, product_type, model, size, color_id, length, breadth, sqft, cubic_feet, quantity, unit_price, total_price, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($data['items'] as $item) {
            $stmt->execute([
                $quote_id,
                $item['product_type'],
                $item['model'],
                $item['size'],
                $item['color_id'],
                $item['length'],
                $item['breadth'],
                $item['sqft'],
                $item['cubic_feet'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
        }

        // Commit transaction
        $pdo->commit();

        // Send success response
        sendJsonResponse(true, 'Quote saved successfully', [
            'quote_id' => $quote_id,
            'quote_number' => $quote_number
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Error in transaction: " . $e->getMessage());
        sendJsonResponse(false, 'Error saving quote: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error in save_quote.php: " . $e->getMessage());
    sendJsonResponse(false, 'Error: ' . $e->getMessage());
}
