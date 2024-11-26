<?php
// Prevent any output before headers
ob_start();

// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Check required extensions
$required_extensions = ['json', 'mysqli', 'fileinfo', 'gd'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    error_log("Missing required PHP extensions: " . implode(', ', $missing_extensions));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error: Missing required PHP extensions'
    ]);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

require_once 'config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    // Get JSON data from POST request
    $jsonData = file_get_contents('php://input');
    error_log("Received data: " . $jsonData);
    
    if (empty($jsonData)) {
        throw new Exception('No data received');
    }
    
    $data = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    error_log("Decoded data: " . print_r($data, true));
    
    // Validate required data
    if (empty($data['customer']['name'])) {
        throw new Exception('Customer name is required');
    }
    
    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('No items in quote');
    }

    // Validate each item has required fields
    foreach ($data['items'] as $item) {
        $required_fields = ['type', 'size', 'model', 'colorId', 'colorName', 'quantity'];
        foreach ($required_fields as $field) {
            if (!isset($item[$field])) {
                throw new Exception("Missing required field '$field' in item data");
            }
        }
    }
    
    // Create quotes directory if it doesn't exist
    $quotesDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'quotes';
    error_log("Creating quotes directory at: " . $quotesDir);
    
    if (!file_exists($quotesDir)) {
        error_log("Quotes directory does not exist, attempting to create...");
        if (!@mkdir($quotesDir, 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create directory: " . print_r($error, true));
            throw new Exception('Failed to create quotes directory: ' . $error['message']);
        }
        chmod($quotesDir, 0777);
        error_log("Successfully created quotes directory");
    }
    
    // Verify directory is writable
    if (!is_writable($quotesDir)) {
        error_log("Quotes directory is not writable: " . $quotesDir);
        throw new Exception('Quotes directory is not writable');
    }
    
    // Start transaction
    if (!isset($conn)) {
        error_log("Database connection not established");
        throw new Exception('Database connection not established');
    }
    
    if (!$conn->ping()) {
        error_log("Database connection lost");
        throw new Exception('Database connection lost');
    }
    
    error_log("Starting database transaction");
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start transaction: ' . $conn->error);
    }
    
    // Generate unique filename
    $timestamp = date('Y-m-d_His');
    $random = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $pdfFileName = "quote_{$timestamp}_{$random}.pdf";
    $pdfFilePath = $quotesDir . DIRECTORY_SEPARATOR . $pdfFileName;
    error_log("PDF file path: " . $pdfFilePath);
    
    try {
        // Generate PDF
        error_log("Initializing TCPDF");
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Angel Stones');
        $pdf->SetTitle('Quote for ' . $data['customer']['name']);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Add title
        $pdf->Cell(0, 10, 'Angel Stones - Quote', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Customer Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Customer Information:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        
        $pdf->Cell(40, 7, 'Name:', 0);
        $pdf->Cell(0, 7, $data['customer']['name'], 0, 1);
        
        if (!empty($data['customer']['requestedBy'])) {
            $pdf->Cell(40, 7, 'Requested By:', 0);
            $pdf->Cell(0, 7, $data['customer']['requestedBy'], 0, 1);
        }
        
        if (!empty($data['customer']['phoneNumber'])) {
            $pdf->Cell(40, 7, 'Phone:', 0);
            $pdf->Cell(0, 7, $data['customer']['phoneNumber'], 0, 1);
        }
        
        if (!empty($data['customer']['projectName'])) {
            $pdf->Cell(40, 7, 'Project:', 0);
            $pdf->Cell(0, 7, $data['customer']['projectName'], 0, 1);
        }
        
        $pdf->Ln(10);
        
        // Add items table
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Items:', 0, 1);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 7, 'Product', 1);
        $pdf->Cell(30, 7, 'Size', 1);
        $pdf->Cell(30, 7, 'Color', 1);
        $pdf->Cell(20, 7, 'Qty', 1);
        $pdf->Cell(30, 7, 'Price', 1, 1, 'R');
        
        // Table content
        $pdf->SetFont('helvetica', '', 10);
        $subtotal = 0;
        
        foreach ($data['items'] as $item) {
            $itemTotal = ($item['basePrice'] + $item['priceIncrease']) * $item['quantity'];
            $subtotal += $itemTotal;
            
            $pdf->Cell(60, 7, $item['type'] . ' - ' . $item['model'], 1);
            $pdf->Cell(30, 7, $item['size'], 1);
            $pdf->Cell(30, 7, $item['colorName'], 1);
            $pdf->Cell(20, 7, $item['quantity'], 1);
            $pdf->Cell(30, 7, '$' . number_format($itemTotal, 2), 1, 1, 'R');
        }
        
        // Add total section
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(140, 7, '', 0);
        $pdf->Cell(30, 7, 'Total:', 1);
        $total = $data['pricing']['total']; // Total already includes commission
        $pdf->Cell(30, 7, '$' . number_format($total, 2), 1, 1, 'R');
        
        // Add Terms and Conditions section
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Terms and Conditions:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $terms = array(
            '1. This quote is valid for 30 days from the date of issue.',
            '2. 50% advance payment is required to confirm the order.',
            '3. Delivery time will be confirmed after order confirmation.',
            '4. Prices are subject to change without prior notice.',
            '5. All disputes are subject to local jurisdiction.'
        );
        
        foreach ($terms as $term) {
            $pdf->Cell(0, 8, $term, 0, 1, 'L');
        }
        
        error_log("Attempting to save PDF to: " . $pdfFilePath);
        $pdf->Output($pdfFilePath, 'F');
        error_log("PDF saved successfully");
        
        // Return success response with PDF URL
        $pdfUrl = '/quotes/' . $pdfFileName;
        echo json_encode([
            'success' => true,
            'pdfUrl' => $pdfUrl
        ]);
        
    } catch (Exception $e) {
        error_log("Error generating PDF: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw new Exception("Failed to generate PDF: " . $e->getMessage());
    }
    
    // Save quote to database
    error_log("Saving quote to database");
    
    // Prepare customer data
    $customerName = $data['customer']['name'];
    $requestedBy = $data['customer']['requestedBy'] ?? '';
    $phoneNumber = $data['customer']['phoneNumber'] ?? '';
    $projectName = $data['customer']['projectName'] ?? '';
    
    // Insert quote
    $quoteQuery = "INSERT INTO quotes (
        customer_name, 
        customer_phone, 
        requested_by,
        project_name,
        total_amount, 
        commission_rate, 
        commission_amount
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $quoteStmt = $conn->prepare($quoteQuery);
    if (!$quoteStmt) {
        throw new Exception('Failed to prepare quote query: ' . $conn->error);
    }
    
    $quoteStmt->bind_param(
        'ssssddd',
        $customerName,
        $phoneNumber,
        $requestedBy,
        $projectName,
        $data['pricing']['total'],
        $data['pricing']['commissionRate'],
        $data['pricing']['commission']
    );
    
    if (!$quoteStmt->execute()) {
        throw new Exception('Failed to insert quote: ' . $quoteStmt->error);
    }
    
    $quoteId = $quoteStmt->insert_id;
    
    // Insert quote items
    $itemStmt = $conn->prepare("INSERT INTO quote_items (
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
    
    if (!$itemStmt) {
        throw new Exception('Failed to prepare quote items statement: ' . $conn->error);
    }
    
    foreach ($data['items'] as $item) {
        $subtotal = ($item['basePrice'] + $item['priceIncrease']) * $item['quantity'];
        
        $itemStmt->bind_param(
            'isssiddiddd',
            $quoteId,
            $item['type'],
            $item['size'],
            $item['model'],
            $item['colorId'],
            $item['length'],
            $item['breadth'],
            $item['quantity'],
            $item['basePrice'],
            $item['priceIncrease'],
            $subtotal
        );
        
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to insert quote item: ' . $itemStmt->error);
        }
    }
    
    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction: ' . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
