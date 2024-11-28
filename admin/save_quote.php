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

        // Get the first item for the main quote details
        $first_item = $data['items'][0];

        // Calculate total amount
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $total_amount += floatval($item['price']);
        }

        // Get commission rate and amount
        $commission_rate = floatval($data['commission_rate']);
        $commission_amount = floatval($data['total_commission']);

        // Get color name
        $color_stmt = $conn->prepare("SELECT color_name FROM stone_color_rates WHERE id = ?");
        $color_stmt->bind_param('i', $first_item['colorId']);
        $color_stmt->execute();
        $color_result = $color_stmt->get_result();
        $color_data = $color_result->fetch_assoc();
        $color_name = $color_data ? $color_data['color_name'] : 'Unknown';
        $color_stmt->close();

        // Calculate additional values
        $pdf_file = 'quotes' . DIRECTORY_SEPARATOR . $quote_number . '.pdf';
        $width_polish = isset($first_item['width_polish']) ? $first_item['width_polish'] : 0;
        $sertop_type = isset($first_item['sertop_type']) ? $first_item['sertop_type'] : 'base';
        $sertop_price = isset($first_item['sertop_price']) ? $first_item['sertop_price'] : 0;
        $total_area = $first_item['sqft'] ?? 0;
        $price_per_sqft = $total_area > 0 ? $first_item['price'] / $total_area : 0;
        $width_polish_cost = $width_polish * 50.00; // Default width polish rate
        $sertop_total = $first_item['price'];
        $subtotal = $first_item['price'];

        // Insert quote
        $stmt = $conn->prepare("INSERT INTO quotes (
            quote_number,
            customer_id,
            customer_name,
            customer_email,
            customer_phone,
            project_name,
            total_amount,
            commission_rate,
            commission_amount,
            pdf_file,
            length,
            breadth,
            width_polish,
            color,
            quantity,
            sertop_type,
            sertop_price,
            total_area,
            price_per_sqft,
            width_polish_cost,
            sertop_total,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception('Failed to prepare quote insert statement: ' . $conn->error);
        }

        // Initialize all variables before binding
        $quote_number = $quote_number ?? '';
        $customer_id = $data['customer_id'] ?? 0;
        $customer_name = $customer['name'] ?? '';
        $customer_email = $customer['email'] ?? '';
        $customer_phone = $customer['phone'] ?? '';
        $project_name = $data['project_name'] ?? '';
        $total_amount = $total_amount ?? 0.00;
        $commission_rate = $commission_rate ?? 0.00;
        $commission_amount = $commission_amount ?? 0.00;
        $pdf_file_name = $pdf_file ?? '';
        $item_length = $first_item['length'] ?? 0.00;
        $item_breadth = $first_item['breadth'] ?? 0.00;
        $item_width_polish = $width_polish ?? 0.00;
        $item_color_name = $color_name ?? '';
        $item_quantity = $first_item['quantity'] ?? 0;
        $item_sertop_type = $sertop_type ?? '';
        $item_sertop_price = $sertop_price ?? 0.00;
        $item_total_area = $total_area ?? 0.00;
        $item_price_per_sqft = $price_per_sqft ?? 0.00;
        $item_width_polish_cost = $width_polish_cost ?? 0.00;
        $item_sertop_total = $sertop_total ?? 0.00;
        $item_subtotal = $subtotal ?? 0.00;

        $stmt->bind_param(
            'sissssdddsdddsisdddddd',
            $quote_number,
            $customer_id,
            $customer_name,
            $customer_email,
            $customer_phone,
            $project_name,
            $total_amount,
            $commission_rate,
            $commission_amount,
            $pdf_file_name,
            $item_length,
            $item_breadth,
            $item_width_polish,
            $item_color_name,
            $item_quantity,
            $item_sertop_type,
            $item_sertop_price,
            $item_total_area,
            $item_price_per_sqft,
            $item_width_polish_cost,
            $item_sertop_total,
            $item_subtotal
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert quote: ' . $stmt->error);
        }

        $quote_id = $conn->insert_id;
        $stmt->close();

        // Insert quote items
        $stmt = $conn->prepare("INSERT INTO quote_items (
            quote_id,
            product_type,
            size,
            model,
            color_id,
            length,
            breadth,
            quantity,
            base_price,
            price_increase,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($data['items'] as $item) {
            $product_type = strtolower($item['type']);
            $color_id = $item['colorId'];
            $base_price = $item['price'] / (1 + ($item['priceIncrease'] ?? 0) / 100);
            $price_increase = $item['price'] - $base_price;
            
            $stmt->bind_param(
                'isssiddiddd',
                $quote_id,
                $product_type,
                $item['size'],
                $item['model'],
                $color_id,
                $item['length'],
                $item['breadth'],
                $item['quantity'],
                $base_price,
                $price_increase,
                $item['price']
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to insert quote item: ' . $stmt->error);
            }
        }

        // Create quotes directory if it doesn't exist
        $quotesDir = __DIR__ . DIRECTORY_SEPARATOR . 'quotes';
        if (!file_exists($quotesDir)) {
            if (!mkdir($quotesDir, 0777, true)) {
                throw new Exception('Failed to create quotes directory: ' . error_get_last()['message']);
            }
        }

        try {
            // Create PDF with custom settings
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 12);
            
            // Add content
            $pdf->Cell(0, 10, 'Quote #: ' . $quote_number, 0, 1);
            $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d'), 0, 1);
            $pdf->Cell(0, 10, 'Customer: ' . $customer['name'], 0, 1);
            
            // Add items table
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(40, 7, 'Item', 1);
            $pdf->Cell(30, 7, 'Size', 1);
            $pdf->Cell(30, 7, 'Color', 1);
            $pdf->Cell(30, 7, 'Quantity', 1);
            $pdf->Cell(40, 7, 'Price', 1);
            $pdf->Ln();
            
            $pdf->SetFont('helvetica', '', 12);
            foreach ($data['items'] as $item) {
                $pdf->Cell(40, 7, $item['type'] . ' ' . $item['model'], 1);
                $pdf->Cell(30, 7, $item['size'], 1);
                $pdf->Cell(30, 7, $color_name, 1);
                $pdf->Cell(30, 7, $item['quantity'], 1);
                $pdf->Cell(40, 7, '$' . number_format($item['price'], 2), 1);
                $pdf->Ln();
            }
            
            // Add totals
            $pdf->Ln(10);
            $pdf->Cell(130);
            $pdf->Cell(30, 7, 'Subtotal:', 0);
            $pdf->Cell(40, 7, '$' . number_format($total_amount, 2), 0);
            $pdf->Ln();
            $pdf->Cell(130);
            $pdf->Cell(30, 7, 'Commission:', 0);
            $pdf->Cell(40, 7, '$' . number_format($commission_amount, 2), 0);
            $pdf->Ln();
            $pdf->Cell(130);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(30, 7, 'Total:', 0);
            $pdf->Cell(40, 7, '$' . number_format($total_amount + $commission_amount, 2), 0);
            
            // Add terms and conditions
            $pdf->Ln(20);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, 'Terms and Conditions:', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, "1. Prices are valid for 30 days from the date of quotation.\n2. Payment terms: 50% advance payment required.\n3. Delivery time: 4-6 weeks from order confirmation.\n4. Prices are subject to change without prior notice.\n5. GST/taxes extra as applicable.", 0, 'L');

            // Save PDF
            $pdf_path = __DIR__ . DIRECTORY_SEPARATOR . $pdf_file;
            $pdf->Output($pdf_path, 'F');

            // Commit transaction
            $conn->commit();

            // Return success response
            echo json_encode([
                'success' => true,
                'quote_id' => $quote_id,
                'quote_number' => $quote_number,
                'pdf_url' => $pdf_file,
                'message' => 'Quote saved successfully'
            ]);

        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " PDF Error: " . $e->getMessage() . "\n", FILE_APPEND);
            throw new Exception('Failed to generate PDF: ' . $e->getMessage());
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Error in transaction: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception('Transaction failed: ' . $e->getMessage());
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Fatal error: " . $e->getMessage() . "\n", FILE_APPEND);
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
