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
        // Create quotes directory if it doesn't exist
        $quotesDir = __DIR__ . DIRECTORY_SEPARATOR . 'quotes';
        if (!file_exists($quotesDir)) {
            if (!mkdir($quotesDir, 0777, true)) {
                throw new Exception('Failed to create quotes directory: ' . error_get_last()['message']);
            }
        }

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

        // Calculate total amount
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $total_amount += floatval($item['price']);
        }

        // Generate unique quote number
        $quote_number = 'Q' . date('Ymd') . rand(1000, 9999);

        // Get the first item for the main quote details
        $first_item = $data['items'][0];

        // Insert quote into database
        $stmt = $conn->prepare("INSERT INTO quotes (
            quote_number, customer_id, total_amount, price, pdf_file, 
            project_name, length, breadth, width_polish, 
            color, quantity, sertop_type, sertop_price, 
            commission_rate, total_area, price_per_sqft, 
            width_polish_cost, sertop_total, subtotal, 
            commission_amount, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, ?, ?, 
            ?, NOW()
        )");

        if (!$stmt) {
            throw new Exception('Failed to prepare quote insert statement: ' . $conn->error);
        }
        
        $pdf_file = 'quotes' . DIRECTORY_SEPARATOR . $quote_number . '.pdf';
        $customer_id = $data['customer_id'];
        $project_name = $first_item['type'] . ' - ' . $first_item['model'];
        
        // Get color name
        $color_stmt = $conn->prepare("SELECT color_name FROM stone_color_rates WHERE id = ?");
        $color_stmt->bind_param('i', $first_item['colorId']);
        $color_stmt->execute();
        $color_result = $color_stmt->get_result();
        $color_data = $color_result->fetch_assoc();
        $color_name = $color_data ? $color_data['color_name'] : 'Unknown';
        $color_stmt->close();

        // Calculate additional values
        $width_polish = isset($first_item['width_polish']) ? $first_item['width_polish'] : 0;
        $sertop_type = isset($first_item['sertop_type']) ? $first_item['sertop_type'] : 'base';
        $sertop_price = isset($first_item['sertop_price']) ? $first_item['sertop_price'] : 0;
        $commission_rate = isset($data['commission_rate']) ? floatval($data['commission_rate']) : 10.00; // Get from input or use default
        $price_per_sqft = $first_item['price'] / $first_item['sqft'];
        $width_polish_cost = $width_polish * 50.00; // Default width polish rate
        $sertop_total = $first_item['price'];
        $subtotal = $first_item['price'];
        $commission_amount = $subtotal * ($commission_rate / 100);

        $stmt->bind_param(
            'sidssdddsiiidsdddddd',
            $quote_number,
            $customer_id,
            $total_amount,
            $first_item['price'],
            $pdf_file,
            $project_name,
            $first_item['length'],
            $first_item['breadth'],
            $width_polish,
            $color_name,
            $first_item['quantity'],
            $sertop_type,
            $sertop_price,
            $commission_rate,
            $first_item['sqft'],
            $price_per_sqft,
            $width_polish_cost,
            $sertop_total,
            $subtotal,
            $commission_amount
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert quote: ' . $stmt->error);
        }
        
        $quote_id = $stmt->insert_id;
        $stmt->close();

        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Quote inserted with ID: " . $quote_id . "\n", FILE_APPEND);

        try {
            // Create PDF with custom settings
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set document information
            $pdf->SetCreator('Angel Stones');
            $pdf->SetAuthor('Angel Stones');
            $pdf->SetTitle('Quote ' . $quote_number);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
            // Set font
            $pdf->SetFont('helvetica', '', 12);
            
            // Company header
            $pdf->Cell(0, 10, 'Angel Stones', 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'QUOTATION', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'Quote #: ' . $quote_number, 0, 1, 'R');
            $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d'), 0, 1, 'R');
            
            // Customer details
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Customer Details:', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'Name: ' . htmlspecialchars($customer['name']), 0, 1);
            $pdf->Cell(0, 10, 'Email: ' . htmlspecialchars($customer['email']), 0, 1);
            $pdf->Cell(0, 10, 'Phone: ' . htmlspecialchars($customer['phone']), 0, 1);
            
            // Items table
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Items:', 0, 1);
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(30, 7, 'Type', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Model', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Size', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Color', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Dimensions', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Cu.Ft', 1, 0, 'C', true);
            $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Price', 1, 1, 'C', true);

            // Table content
            $pdf->SetFont('helvetica', '', 10);
            $subtotal = 0;
            $total_commission = isset($data['total_commission']) ? $data['total_commission'] : 0;
            $commission_per_item = $total_commission / count($data['items']);

            foreach ($data['items'] as $item) {
                // Get color name
                $color_stmt = $conn->prepare("SELECT color_name FROM stone_color_rates WHERE id = ?");
                $color_stmt->bind_param('i', $item['colorId']);
                $color_stmt->execute();
                $color_result = $color_stmt->get_result();
                $color_data = $color_result->fetch_assoc();
                $color_name = $color_data ? $color_data['color_name'] : 'Unknown';
                $color_stmt->close();

                $pdf->Cell(30, 7, $item['type'], 1, 0, 'L');
                $pdf->Cell(25, 7, $item['model'], 1, 0, 'L');
                $pdf->Cell(25, 7, $item['size'], 1, 0, 'C');
                $pdf->Cell(30, 7, $color_name, 1, 0, 'L');
                $pdf->Cell(30, 7, $item['length'] . '" × ' . $item['breadth'] . '"', 1, 0, 'C');
                $pdf->Cell(20, 7, number_format($item['cubicFeet'], 2), 1, 0, 'R');
                $pdf->Cell(15, 7, $item['quantity'], 1, 0, 'C');
                $pdf->Cell(25, 7, '$' . number_format($item['price'], 2), 1, 1, 'R');
                
                $subtotal += $item['price'];
            }

            // Totals
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(175, 7, 'Subtotal:', 1, 0, 'R');
            $pdf->Cell(25, 7, '$' . number_format($subtotal, 2), 1, 1, 'R');

            // Total (including commission)
            $total = $subtotal + $total_commission;
            $pdf->Cell(175, 7, 'Total:', 1, 0, 'R');
            $pdf->Cell(25, 7, '$' . number_format($total, 2), 1, 1, 'R');

            // Terms and conditions
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Terms and Conditions:', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, "1. Prices are valid for 30 days from the date of quotation.\n2. Payment terms: 50% advance payment required.\n3. Delivery time: 4-6 weeks from order confirmation.\n4. Prices are subject to change without prior notice.\n5. GST/taxes extra as applicable.", 0, 'L');

            // Save PDF
            $pdf_path = __DIR__ . DIRECTORY_SEPARATOR . $pdf_file;
            $pdf->Output($pdf_path, 'F');
            
            // Insert quote items
            $stmt = $conn->prepare("INSERT INTO quote_items (quote_id, product_id, product_type, model, size, color_id, length, breadth, sqft, cubic_feet, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Failed to prepare quote items insert statement: ' . $conn->error);
            }
            
            foreach ($data['items'] as $item) {
                file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Inserting item: " . print_r($item, true) . "\n", FILE_APPEND);
                
                $stmt->bind_param('iisssiddddid', 
                    $quote_id,
                    $item['productId'],
                    $item['type'],
                    $item['model'],
                    $item['size'],
                    $item['colorId'],
                    $item['length'],
                    $item['breadth'],
                    $item['sqft'],
                    $item['cubicFeet'],
                    $item['quantity'],
                    $item['price']
                );
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert quote item: ' . $stmt->error . '. Item data: ' . json_encode($item));
                }
            }
            $stmt->close();

            file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " All items inserted successfully\n", FILE_APPEND);

            // Commit transaction
            $conn->commit();

            file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " Transaction committed\n", FILE_APPEND);

            // Return success response
            echo json_encode([
                'success' => true,
                'quote_number' => $quote_number,
                'pdf_url' => $pdf_file
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
