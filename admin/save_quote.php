<?php
// Force error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use absolute paths for includes
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

// Ensure user is logged in
requireLogin();

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    
    // Log raw input
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Raw input: " . $json . "\n", FILE_APPEND);
    
    $data = json_decode($json, true);
    
    // Log decoded data
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

    if (!$data) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }

    if (empty($data['customer_id'])) {
        throw new Exception('Customer ID is required');
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('No items in cart');
    }

    // Validate items data structure
    foreach ($data['items'] as $index => $item) {
        $requiredFields = ['length', 'breadth', 'size', 'model', 'color_id', 'quantity', 'totalPrice', 'commission_rate'];
        foreach ($requiredFields as $field) {
            if (!isset($item[$field])) {
                throw new Exception("Missing required field '$field' in item at index $index");
            }
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get customer details
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare customer query: ' . $conn->error);
        }
        $stmt->bind_param('i', $data['customer_id']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute customer query: ' . $stmt->error);
        }
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            throw new Exception('Customer not found');
        }

        // Generate unique quote number
        $quote_number = 'Q' . date('Ymd') . rand(1000, 9999);

        // Calculate total amount and commission
        $total_amount = 0;
        $total_commission = 0;
        foreach ($data['items'] as $item) {
            if (!is_numeric($item['totalPrice'])) {
                throw new Exception('Invalid total price for item');
            }
            $item_total = floatval($item['totalPrice']);
            $total_amount += $item_total;
            
            // Calculate commission for each item based on its total price
            $commission_rate = isset($item['commission_rate']) ? floatval($item['commission_rate']) : 0;
            $item_commission = $item_total * ($commission_rate / 100);
            $total_commission += $item_commission;
        }

        // Insert quote with the commission rate from the first item (assuming all items have same rate)
        $stmt = $conn->prepare("INSERT INTO quotes (
            quote_number,
            customer_id,
            customer_email,
            total_amount,
            commission_rate,
            commission_amount,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");

        if (!$stmt) {
            throw new Exception('Failed to prepare quote insert: ' . $conn->error);
        }

        $commission_rate = isset($data['items'][0]['commission_rate']) ? floatval($data['items'][0]['commission_rate']) : 0;
        $stmt->bind_param('ssiddd', 
            $quote_number,
            $data['customer_id'],
            $customer['email'],
            $total_amount,
            $commission_rate,
            $total_commission
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert quote: ' . $stmt->error);
        }

        // Get the quote_id that was just inserted
        $quote_id = $conn->insert_id;
        $stmt->close();

        // Insert quote items
        $stmt = $conn->prepare("INSERT INTO quote_items (
            quote_id,
            product_type,
            model,
            size,
            color_id,
            length,
            breadth,
            sqft,
            cubic_feet,
            quantity,
            unit_price,
            total_price,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        if (!$stmt) {
            throw new Exception('Failed to prepare quote items insert: ' . $conn->error);
        }

        foreach ($data['items'] as $item) {
            $unit_price = $item['totalPrice'] / $item['quantity'];
            
            $stmt->bind_param('issdiddddidi',
                $quote_id,
                $item['type'],
                $item['model'],
                $item['size'],
                $item['color_id'],
                $item['length'],
                $item['breadth'],
                $item['sqft'],
                $item['cubicFeet'],
                $item['quantity'],
                $unit_price,
                $item['totalPrice']
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to insert quote item: ' . $stmt->error);
            }
        }

        $stmt->close();

        // Create quotes directory if it doesn't exist
        $quotesDir = __DIR__ . DIRECTORY_SEPARATOR . 'quotes';
        if (!file_exists($quotesDir)) {
            if (!mkdir($quotesDir, 0777, true)) {
                throw new Exception('Failed to create quotes directory');
            }
        }

        // Generate PDF with optimized layout
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10); // Reduced margins
        $pdf->AddPage();

        // Add content to PDF
        $pdf->SetFont('helvetica', 'B', 16); // Slightly smaller title
        $pdf->Cell(0, 8, 'Angel Stones Quote', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 4, 'Quote #' . $quote_number, 0, 1, 'C');
        
        // Add date with less spacing
        $pdf->Ln(2);
        $pdf->Cell(0, 4, 'Date: ' . date('F j, Y'), 0, 1, 'R');
        
        // Add customer details with optimized spacing
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Customer Details:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 4, 'Name:', 0, 0);
        $pdf->Cell(0, 4, $customer['name'], 0, 1);
        $pdf->Cell(30, 4, 'Email:', 0, 0);
        $pdf->Cell(0, 4, $customer['email'], 0, 1);
        $pdf->Cell(30, 4, 'Phone:', 0, 0);
        $pdf->Cell(0, 4, $customer['phone'], 0, 1);
        
        // Add items table with optimized spacing
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Quote Items:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 6, 'Item', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Size', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Dimensions', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Qty', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Price', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['items'] as $item) {
            $pdf->Cell(50, 6, $item['type'] . ' ' . $item['model'], 1, 0);
            $pdf->Cell(25, 6, $item['size'] . '"', 1, 0, 'C');
            $pdf->Cell(30, 6, $item['length'] . '" x ' . $item['breadth'] . '"', 1, 0, 'C');
            $pdf->Cell(20, 6, $item['quantity'], 1, 0, 'C');
            $pdf->Cell(30, 6, '$' . number_format($item['totalPrice'], 2), 1, 1, 'R');
        }

        // Add subtotal, commission and total with clear breakdown
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(125, 5, '', 0, 0);
        $pdf->Cell(30, 5, 'Subtotal:', 0, 0, 'R');
        $pdf->Cell(30, 5, '$' . number_format($total_amount, 2), 0, 1, 'R');
        
        $pdf->Cell(125, 5, '', 0, 0);
        $pdf->Cell(30, 5, 'Commission (' . number_format($commission_rate, 1) . '%):', 0, 0, 'R');
        $pdf->Cell(30, 5, '$' . number_format($total_commission, 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(125, 5, '', 0, 0);
        $pdf->Cell(30, 5, 'Total:', 0, 0, 'R');
        $pdf->Cell(30, 5, '$' . number_format($total_amount + $total_commission, 2), 0, 1, 'R');

        // Save PDF
        $pdf_path = $quotesDir . DIRECTORY_SEPARATOR . $quote_number . '.pdf';
        $pdf->Output($pdf_path, 'F');

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'quote_id' => $quote_id,
            'quote_number' => $quote_number,
            'pdf_url' => 'quotes/' . $quote_number . '.pdf',
            'message' => 'Quote saved successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log error and return error response
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
