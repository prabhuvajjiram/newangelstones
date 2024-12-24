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
    $items_sql = "SELECT qi.*, qi.unit_price as base_price, scr.color_name,
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

    // Calculate commission rate and distribute across items
    $commission_rate = floatval($quote['commission_rate'] ?? 0) / 100;
    $total_without_commission = 0;
    foreach ($items as $item) {
        $total_without_commission += floatval($item['total_price']);
    }

    // Calculate commission per dollar
    $commission_per_dollar = $total_without_commission > 0 ? 
        ($total_without_commission * $commission_rate) / $total_without_commission : 0;

    // Apply commission to each item
    foreach ($items as &$item) {
        $item_commission = floatval($item['total_price']) * $commission_per_dollar;
        $item['price_with_commission'] = floatval($item['base_price']) * (1 + $commission_per_dollar);
        $item['total_with_commission'] = floatval($item['total_price']) * (1 + $commission_per_dollar);
    }

    // Calculate totals and shipping information
    $total_cubic_feet = 0;
    foreach ($items as $item) {
        $total_cubic_feet += floatval($item['cubic_feet']);
    }
    
    $container_capacity = 205; // Standard 20x20 container capacity
    $capacity_percentage = ($total_cubic_feet / $container_capacity) * 100;
    $containers_needed = ceil($total_cubic_feet / $container_capacity);
    
    if ($capacity_percentage < 90) {
        $shipping_note = 'Warning: Orders below 90% container capacity (205-210 cubic ft) may experience longer delivery times and additional shipping costs. Please consider adding more items to optimize container space.';
    } elseif ($capacity_percentage <= 95) {
        $shipping_note = 'Your order is currently at ' . number_format($capacity_percentage, 1) . '% of container capacity (205-210 cubic ft). Adding a few more items will achieve optimal shipping efficiency.';
    } elseif ($capacity_percentage <= 100) {
        $shipping_note = 'Your order efficiently utilizes one container (205-210 cubic ft).';
    } else {
        $shipping_note = 'This order requires ' . $containers_needed . ' shipping containers (each container holds 205-210 cubic ft).';
    }

    // Create PDF
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Angel Stones');
    $pdf->SetAuthor('Angel Stones');
    $pdf->SetTitle('Quote #' . $quote['quote_number']);

    // Set page margins even smaller for better fit
    $pdf->SetMargins(5, 55, 5);  
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

    // Table Header
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    $col_widths = array(60, 22, 37, 15, 12, 22, 22);  
    $headers = array('Description', 'Color', 'Dimensions', 'Cu.ft', 'Qty', 'Total Price');
    
    $pdf->Ln(5);
    foreach ($headers as $i => $header) {
        $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Items
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);
    $grand_total = 0;
    
    foreach ($items as $item) {
        $description = ucfirst($item['product_type']);
        if (!empty($item['model_name'])) {
            $description .= ' - ' . $item['model_name'];
        }
        
        $dimensions = $item['length'] . '" x ' . $item['breadth'] . '" x ' . $item['size'] . '"';
        
        $pdf->Cell($col_widths[0], 7, $description, 1, 0, 'L');
        $pdf->Cell($col_widths[1], 7, $item['color_name'], 1, 0, 'C');
        $pdf->Cell($col_widths[2], 7, $dimensions, 1, 0, 'C');
        $pdf->Cell($col_widths[3], 7, number_format($item['cubic_feet'], 2), 1, 0, 'C');
        $pdf->Cell($col_widths[4], 7, $item['quantity'], 1, 0, 'C');
        #$pdf->Cell($col_widths[5], 7, '$' . number_format($item['price_with_commission'], 2), 1, 0, 'R');
        $pdf->Cell($col_widths[6], 7, '$' . number_format($item['total_with_commission'], 2), 1, 0, 'R');
        $pdf->Ln();
        
        $grand_total += $item['total_with_commission'];
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(array_sum(array_slice($col_widths, 0, 5)), 7, 'Total Price:', 1, 0, 'R');
    $pdf->Cell($col_widths[5], 7, '$' . number_format($grand_total, 2), 1, 1, 'R');

    // Add shipping information to PDF
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 10, 'Shipping Information:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, 'Total Cubic Feet: ' . number_format($total_cubic_feet, 2) . ' cu.ft', 0, 'L');
    $pdf->MultiCell(0, 10, $shipping_note, 0, 'L');
    $pdf->Ln(5);

    // Add Terms and Conditions
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(51, 51, 51);
    $terms = "1. This quote is valid for 30 days from the date of issue. 2. 50% advance payment is required to confirm the order. 3. Delivery time will be confirmed after order confirmation. 4. Prices are subject to change with prior notice. 5. All disputes are subject to local jurisdiction.";
    $pdf->MultiCell(0, 4, $terms, 0, 'L');

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
