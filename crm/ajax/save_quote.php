<?php
// Prevent any unwanted output
ob_start();

// Disable error display but enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting save_quote.php");

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null) {
    // Clean any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Prepare response
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    // Send response and exit
    echo json_encode($response);
    exit;
}

try {
    // Include files after error handling setup
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/session.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Session expired. Please refresh the page and try again.', [
            'redirect' => '../login.php'
        ]);
    }

    // Get and validate JSON input
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

    // Log received data for debugging
    error_log("Received data: " . print_r($data, true));

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

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Calculate totals
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $total_amount += floatval($item['total_price']);
        }
        $commission_amount = $total_amount * (floatval($data['commission_rate']) / 100);
        $final_total = $total_amount + $commission_amount;

        // Generate quote number
        $quote_number = 'Q' . date('Ymd') . sprintf('%04d', rand(1, 9999));

        // Get username from session
        $username = isset($_SESSION['email']) ? $_SESSION['email'] : '';
        error_log("Saving quote for user: " . $username);

        // Insert quote
        $stmt = $pdo->prepare("
            INSERT INTO quotes 
            (quote_number, customer_id, total_amount, commission_rate, commission_amount, status, valid_until, created_at, username)
            VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), ?)
        ");

        $stmt->execute([
            $quote_number,
            $data['customer_id'],
            $final_total,
            $data['commission_rate'],
            $commission_amount,
            $username
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
