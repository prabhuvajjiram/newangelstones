<?php
// Clear any existing output
if (ob_get_level()) ob_end_clean();

require_once 'includes/config.php';
require_once 'includes/mypdf.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Quote ID is required');
    }

    $quote_id = intval($_GET['id']);

    // Get quote data
    $stmt = $pdo->prepare("
        SELECT q.*, c.name as customer_name, c.email as customer_email, 
               c.phone as customer_phone, c.address as customer_address,
               c.city as customer_city, c.state as customer_state, 
               c.postal_code as customer_postal_code,
               q.created_at as quote_date
        FROM quotes q 
        JOIN customers c ON q.customer_id = c.id 
        WHERE q.id = ? AND (q.username = ? OR ? = true)
    ");

    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $stmt->execute([$quote_id, $_SESSION['email'], $isAdmin]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        throw new Exception('Quote not found or access denied');
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

    // Create PDF
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Angel Stones');
    $pdf->SetAuthor('Angel Stones');
    $pdf->SetTitle('Quote #' . $quote['quote_number']);

    // Set margins and breaks
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

    // Output PDF
    $pdf->Output('Quote_' . $quote['quote_number'] . '.pdf', 'I');
    exit;

} catch (Exception $e) {
    // Clear any output
    if (ob_get_level()) ob_end_clean();
    
    // Show error
    die("Error generating PDF: " . $e->getMessage());
}
?>
