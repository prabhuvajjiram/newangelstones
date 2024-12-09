<?php
// Prevent any output before headers
if (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Debug log function
function debugLog($message, $type = 'info') {
    $log_file = __DIR__ . '/../logs/email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp][$type] $message\n";
    error_log($log_message, 3, $log_file);
}

// Function to generate PDF for email
function generatePDFForEmail($quote_id, $output_path) {
    global $pdo;
    
    // Get full quote data with all necessary joins
    $sql = "SELECT q.*, c.name as customer_name, c.email as customer_email, 
            c.phone as customer_phone, c.address as customer_address,
            c.city as customer_city, c.state as customer_state, 
            c.postal_code as customer_postal_code,
            q.created_at as quote_date
            FROM quotes q 
            JOIN customers c ON q.customer_id = c.id 
            WHERE q.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quote) {
        throw new Exception('Quote not found');
    }

    // Get quote items
    $items_sql = "SELECT qi.*, scr.color_name,
                  CASE qi.product_type
                    WHEN 'sertop' THEN (SELECT model FROM sertop_products WHERE id = qi.model)
                    WHEN 'slant' THEN (SELECT model FROM slant_products WHERE id = qi.model)
                    WHEN 'marker' THEN (SELECT model FROM marker_products WHERE id = qi.model)
                    WHEN 'base' THEN (SELECT model FROM base_products WHERE id = qi.model)
                  END as model_name
                  FROM quote_items qi
                  LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id
                  WHERE qi.quote_id = ?";
    
    $stmt = $pdo->prepare($items_sql);
    $stmt->execute([$quote_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create new PDF instance
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Angel Stones');
    $pdf->SetAuthor('Angel Stones');
    $pdf->SetTitle('Quote #' . $quote['quote_number']);
    
    // Set header and footer
    $pdf->setHeaderData('', 0, '', '');
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->SetMargins(15, 55, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(35);
    $pdf->SetAutoPageBreak(true, 35);
    $pdf->setImageScale(1.25);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    
    // Add first page
    $pdf->AddPage();
    
    // Set font for the content
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    // Quote Number and Date
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(90, 10, 'Quote #' . $quote['quote_number'], 0, 0, 'L');
    $pdf->Cell(90, 10, 'Date: ' . date('m/d/Y', strtotime($quote['quote_date'])), 0, 1, 'R');

    // Customer Information Section
    $pdf->Ln(5);
    $pdf->SetFillColor(34, 40, 49);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Customer Information', 0, 1, 'L', true);

    // Customer details
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(5);
    $pdf->Cell(30, 7, 'Name:', 0, 0, 'L');
    $pdf->Cell(150, 7, $quote['customer_name'], 0, 1, 'L');
    $pdf->Cell(30, 7, 'Email:', 0, 0, 'L');
    $pdf->Cell(150, 7, $quote['customer_email'], 0, 1, 'L');
    $pdf->Cell(30, 7, 'Phone:', 0, 0, 'L');
    $pdf->Cell(150, 7, $quote['customer_phone'], 0, 1, 'L');

    // Items Section
    $pdf->Ln(5);
    $pdf->SetFillColor(34, 40, 49);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Items', 0, 1, 'L', true);

    // Items table header
    $pdf->Ln(5);
    $pdf->SetFillColor(34, 40, 49);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $pdf->Cell(60, 8, 'Description', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Dimensions', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', true);
    $pdf->Cell(35, 8, 'Total', 1, 1, 'R', true);

    // Items rows
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', '', 9);
    
    $subtotal = 0;
    foreach ($items as $item) {
        $description = ucfirst($item['product_type']);
        if (!empty($item['model_name'])) {
            $description .= ' - ' . $item['model_name'];
        }
        if (!empty($item['color_name'])) {
            $description .= ' - ' . $item['color_name'];
        }
        
        $dimensions = $item['length'] . '" × ' . $item['breadth'] . '" × ' . $item['size'] . '"';
        
        $pdf->Cell(60, 7, $description, 1, 0, 'L');
        $pdf->Cell(30, 7, $dimensions, 1, 0, 'C');
        $pdf->Cell(20, 7, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(35, 7, '$' . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(35, 7, '$' . number_format($item['total_price'], 2), 1, 1, 'R');
        
        $subtotal += $item['total_price'];
    }

    // Totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(145, 8, 'Total:', 1, 0, 'R');
    $pdf->Cell(35, 8, '$' . number_format($subtotal, 2), 1, 1, 'R');

    // Save PDF
    return $pdf->Output($output_path, 'F');
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../includes/config.php';
    require_once '../includes/gmail_functions.php';
    require_once '../includes/mypdf.php'; // Use the new file instead of generate_pdf.php
    
    // Parse JSON input
    $rawInput = file_get_contents('php://input');
    debugLog("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    if (!isset($input['quote_id'])) {
        throw new Exception('Quote ID is required');
    }

    $quote_id = intval($input['quote_id']);
    debugLog("Processing quote ID: " . $quote_id);

    // Create PDF directory if it doesn't exist
    $pdf_dir = __DIR__ . '/../pdf_quotes';
    if (!file_exists($pdf_dir)) {
        if (!mkdir($pdf_dir, 0777, true)) {
            throw new Exception("Failed to create PDF directory: $pdf_dir");
        }
    }

    // Generate unique filename
    $pdf_filename = 'quote_' . $quote_id . '_' . date('Y-m-d_His') . '.pdf';
    $pdf_path = $pdf_dir . '/' . $pdf_filename;
    debugLog("PDF path: " . $pdf_path);

    // Check directory permissions
    if (!is_writable($pdf_dir)) {
        throw new Exception("PDF directory is not writable: $pdf_dir");
    }

    // Verify quote exists and user has access
    $stmt = $pdo->prepare("
        SELECT 
            q.*,
            c.name as customer_name,
            c.email as customer_email
        FROM quotes q
        JOIN customers c ON q.customer_id = c.id
        WHERE q.id = ? AND (q.username = ? OR ? = true)
    ");
    
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    if (!$stmt->execute([$quote_id, $_SESSION['email'], $isAdmin])) {
        throw new Exception('Database error while fetching quote');
    }
    
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        throw new Exception('Quote not found or access denied');
    }

    if (empty($quote['customer_email'])) {
        throw new Exception('Customer email is not available');
    }

    // Generate PDF using our custom function
    try {
        generatePDFForEmail($quote_id, $pdf_path);
        
        if (!file_exists($pdf_path)) {
            throw new Exception('Failed to generate PDF file');
        }
        
        debugLog("PDF generated successfully at: " . $pdf_path);
    } catch (Exception $e) {
        debugLog("PDF Generation failed: " . $e->getMessage(), 'error');
        throw new Exception('Failed to generate PDF: ' . $e->getMessage());
    }

    // Load and prepare email template
    $template_path = __DIR__ . '/../email_templates/quote.html';
    if (!file_exists($template_path)) {
        throw new Exception('Email template not found');
    }

    $email_body = file_get_contents($template_path);
    if ($email_body === false) {
        throw new Exception('Failed to read email template');
    }
    
    $email_body = str_replace('{CUSTOMER_NAME}', htmlspecialchars($quote['customer_name']), $email_body);
    $email_body = str_replace('{QUOTE_ID}', htmlspecialchars($quote['quote_number']), $email_body);

    // Send email
    $mailer = new GmailMailer($pdo);
    $subject = "Your Quote #" . $quote['quote_number'] . " from Angel Stones";
    
    // Before sending email:
    debugLog("Attempting to send email with token data");
    debugLog("User email: " . $_SESSION['email']);

    // Get token info
    $stmt = $pdo->prepare("SELECT refresh_token, gmail_refresh_token FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $tokenInfo = $stmt->fetch();
    debugLog("Token info: " . print_r($tokenInfo, true));

    $result = $mailer->sendEmail(
        $quote['customer_email'],
        $subject,
        $email_body,
        $pdf_path
    );

    // Clean up PDF file
    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quote sent successfully to ' . $quote['customer_email']
    ]);

} catch (Exception $e) {
    debugLog("Exception caught: " . $e->getMessage(), 'error');
    debugLog("Stack trace: " . $e->getTraceAsString(), 'error');
    
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start new buffer for JSON response
    ob_start();
    
    // Check if this is an authentication error
    if ($e->getCode() === 401) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Gmail authentication required',
            'needsAuth' => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    // Get and send the final output
    $final_output = ob_get_clean();
    echo $final_output;
}
