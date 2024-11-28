<?php
// Turn off all error reporting to prevent output before PDF
error_reporting(0);
ini_set('display_errors', 0);

require_once 'includes/config.php';
require_once('tcpdf/tcpdf.php');

// Prevent any output before PDF generation
ob_clean();

// Custom PDF class for header styling
class MYPDF extends TCPDF {
    public function Header() {
        // Background color for header
        $this->Rect(0, 0, $this->getPageWidth(), 45, 'F', array(), array(34, 40, 49));
        
        // Company name
        $this->SetY(12);
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'Angel Stones', 0, 1, 'C');
        
        // Tagline
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 8, 'Quality Stone Products & Services', 0, 1, 'C');
    }
}

// Check if it's a GET request with ID
if (isset($_GET['id'])) {
    // Fetch quote data from database
    $quote_id = intval($_GET['id']);
    $sql = "SELECT q.*, c.name as customer_name, c.email as customer_email, 
            c.phone as customer_phone, c.address as customer_address,
            c.city as customer_city, c.state as customer_state, 
            c.postal_code as customer_postal_code,
            q.created_at as quote_date, q.total_amount, q.commission_amount, q.commission_rate
            FROM quotes q 
            JOIN customers c ON q.customer_id = c.id 
            WHERE q.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quote_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Format data for PDF generation
        $data = [
            'customer' => [
                'name' => $row['customer_name'],
                'email' => $row['customer_email'],
                'phone' => $row['customer_phone'],
                'address' => $row['customer_address'],
                'city' => $row['customer_city'],
                'state' => $row['customer_state'],
                'postal_code' => $row['customer_postal_code']
            ],
            'date' => date('Y-m-d', strtotime($row['quote_date'])),
            'total_amount' => $row['total_amount'],
            'commission_amount' => $row['commission_amount'],
            'commission_rate' => $row['commission_rate'],
            'items' => []
        ];
        
        // Fetch quote items with color information
        $items_sql = "SELECT qi.*, qi.product_type as type, scr.color_name,
                     ROUND(((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (1 + COALESCE(scr.price_increase_percentage, 0) / 100), 2) as base_unit_price,
                     ROUND(((qi.length * qi.breadth) / 144) * COALESCE(sp.base_price, bp.base_price, mp.base_price, slp.base_price) * (1 + COALESCE(scr.price_increase_percentage, 0) / 100) * qi.quantity, 2) as base_total_price
                     FROM quote_items qi 
                     LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id 
                     LEFT JOIN sertop_products sp ON qi.model = sp.model AND qi.size = sp.size_inches AND qi.product_type = 'sertop'
                     LEFT JOIN base_products bp ON qi.model = bp.model AND qi.size = bp.size_inches AND qi.product_type = 'base'
                     LEFT JOIN marker_products mp ON qi.model = mp.model AND qi.size = mp.square_feet AND qi.product_type = 'marker'
                     LEFT JOIN slant_products slp ON qi.model = slp.model AND qi.product_type = 'slant'
                     WHERE qi.quote_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $quote_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        // Calculate commission per item based on their proportion of total
        $items_data = [];
        $total_base_price = 0;
        
        // First pass: collect all items and calculate total base price
        while ($item = $items_result->fetch_assoc()) {
            $items_data[] = $item;
            $total_base_price += $item['base_total_price'];
        }
        
        // Second pass: calculate commission per item and create final items array
        foreach ($items_data as $item) {
            // Calculate this item's share of the commission
            $item_commission = 0;
            if ($total_base_price > 0) {
                $item_commission = ($item['base_total_price'] / $total_base_price) * $data['commission_amount'];
            }
            
            // Add commission to unit price and total price
            $commission_per_unit = $item_commission / $item['quantity'];
            $unit_price = round($item['base_unit_price'] * (1 + ($row['commission_rate'] / 100)), 2);
            $total_price = round($unit_price * $item['quantity'], 2);
            
            // Format description safely
            $description = ucfirst($item['type']);
            if (!empty($item['model'])) {
                $description .= " - " . $item['model'];
            }
            if (!empty($item['color_name'])) {
                $description .= " - " . $item['color_name'];
            }
            
            $data['items'][] = [
                'description' => $description,
                'length' => $item['length'],
                'breadth' => $item['breadth'],
                'size' => $item['size'],
                'quantity' => $item['quantity'],
                'unit_price' => $unit_price,
                'total_price' => $total_price
            ];
        }
    } else {
        die("Quote not found");
    }
} else {
    die("Invalid request method");
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Angel Stones');
$pdf->SetAuthor('Angel Stones');
$pdf->SetTitle('Quote - ' . $data['customer']['name']);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 55, 15);
// Disable auto page breaks
$pdf->SetAutoPageBreak(false);

// Add a page
$pdf->AddPage();

// Quote Number and Date
$pdf->SetY(50);
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(90, 10, 'Quote #' . sprintf('%05d', $quote_id), 0, 0, 'L');
$pdf->Cell(90, 10, 'Date: ' . $data['date'], 0, 1, 'R');

// Customer Information Section
$pdf->Ln(5);
$pdf->SetFillColor(34, 40, 49);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Customer Information', 0, 1, 'L', true);

// Reset text color for customer details
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Ln(5);

// Customer details in two columns
$left_x = $pdf->GetX();
$top_y = $pdf->GetY();

// Left column labels
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 7, 'Name:', 0, 1, 'L');
$pdf->Cell(25, 7, 'Email:', 0, 1, 'L');
$pdf->Cell(25, 7, 'Phone:', 0, 1, 'L');
$pdf->Cell(25, 7, 'Address:', 0, 1, 'L');
$pdf->Cell(25, 7, 'City/State:', 0, 1, 'L');

// Reset X and Y for right column values
$pdf->SetXY($left_x + 25, $top_y);
$pdf->SetFont('helvetica', '', 10);

// Right column values
$pdf->Cell(165, 7, $data['customer']['name'], 0, 1, 'L');
$pdf->SetX($left_x + 25);
$pdf->Cell(165, 7, $data['customer']['email'], 0, 1, 'L');
$pdf->SetX($left_x + 25);
$pdf->Cell(165, 7, $data['customer']['phone'], 0, 1, 'L');
$pdf->SetX($left_x + 25);
$pdf->Cell(165, 7, $data['customer']['address'], 0, 1, 'L');
$pdf->SetX($left_x + 25);
$pdf->Cell(165, 7, $data['customer']['city'] . ', ' . $data['customer']['state'] . ' ' . $data['customer']['postal_code'], 0, 1, 'L');

// Items Section Header
$pdf->Ln(5);
$pdf->SetFillColor(34, 40, 49);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Items', 0, 1, 'L', true);

// Table Header
$pdf->Ln(5);
$pdf->SetFillColor(34, 40, 49);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 11);
        
$pdf->Cell(45, 8, 'Description', 1, 0, 'L', true);
$pdf->Cell(45, 8, 'Dimensions', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Total Cu.Ft', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(25, 8, 'Total', 1, 1, 'R', true);
        
// Reset text color for items
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', '', 10);
        
$total = 0;
$total_cubic_feet = 0;
foreach ($data['items'] as $item) {
    // Format dimensions to always show length × width × height
    $dimensions = sprintf('%.2f" × %.2f" × %.2f"', 
                        $item['length'], 
                        $item['breadth'], 
                        $item['size']);
    
    // Calculate total cubic feet (including quantity)
    $cubic_feet = round(($item['length'] * $item['breadth'] * $item['size'] * $item['quantity']) / 1728, 2);
    $total_cubic_feet += $cubic_feet;
    
    $pdf->Cell(45, 8, $item['description'], 1, 0, 'L');
    $pdf->Cell(45, 8, $dimensions, 1, 0, 'C');
    $pdf->Cell(25, 8, number_format($cubic_feet, 2), 1, 0, 'C');
    $pdf->Cell(15, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(25, 8, '$' . number_format($item['unit_price'], 2), 1, 0, 'R');
    $pdf->Cell(25, 8, '$' . number_format($item['total_price'], 2), 1, 1, 'R');
    
    $total += $item['total_price'];
}
        
// Totals row
$pdf->SetFont('helvetica', 'B', 11);
// First total row for cubic feet
$pdf->Cell(90, 8, 'Total Cu.Ft:', 1, 0, 'R');
$pdf->Cell(25, 8, number_format($total_cubic_feet, 2), 1, 0, 'C');
$pdf->Cell(15, 8, '', 1, 0, 'C'); // Empty qty cell
$pdf->Cell(25, 8, '', 1, 0, 'R'); // Empty unit price cell
$pdf->Cell(25, 8, '', 1, 1, 'R'); // Empty total cell
        
// Second total row for price
$pdf->Cell(155, 8, 'Total:', 1, 0, 'R');
$pdf->Cell(25, 8, '$' . number_format($total, 2), 1, 1, 'R');

// Terms and Conditions
$pdf->Ln(8);
$pdf->SetFillColor(34, 40, 49);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Terms and Conditions', 0, 1, 'L', true);
        
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(5);
        
$terms = array(
    "1. This quote is valid for 30 days from the date of issue.",
    "2. 50% advance payment is required to confirm the order.",
    "3. Delivery time will be confirmed after order confirmation.",
    "4. Prices are subject to change without prior notice.",
    "5. All disputes are subject to local jurisdiction."
);
        
foreach ($terms as $term) {
    $pdf->Cell(0, 6, $term, 0, 1, 'L');
}

// Footer
$pdf->SetY(-35);
$pdf->SetFillColor(34, 40, 49);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 25, '', 0, 1, 'C', true);
$pdf->SetY(-30);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'Angel Stones', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Quality Stone Products & Services', 0, 1, 'C');
$pdf->Cell(0, 5, 'Phone: (555) 123-4567 | Email: info@angelstones.com', 0, 1, 'C');

// Output PDF
$pdf->Output('Quote-' . sprintf('%05d', $quote_id) . '.pdf', 'I');
?>
