<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for required extensions
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    die('Error: Neither GD nor Imagick extension is loaded. At least one is required for PDF generation.');
}

require_once('includes/config.php');
require_once('tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        // Get the current directory (crm folder)
        $root_dir = dirname(__FILE__);
        // Go up one level to find the images directory
        $parent_dir = dirname($root_dir);
        // Construct absolute path to image
        $image_file = $parent_dir . '/images/logo03.png';
        
        // Check if image exists and add it
        if (file_exists($image_file)) {
            // Add black backdrop
            $this->SetFillColor(34, 40, 49);
            $this->Rect(0, 0, $this->GetPageWidth(), 45, 'F');
            
            // Logo
            $this->Image($image_file, 10, 10, 60);
        } else {
            error_log("Logo file not found at: " . $image_file);
            // Continue without the logo if image is missing
            $this->SetFillColor(34, 40, 49);
            $this->Rect(0, 0, $this->GetPageWidth(), 45, 'F');
        }
        
        // Move position below the header
        $this->SetY(50);
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-35);
        $this->SetFillColor(34, 40, 49);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 25, '', 0, 1, 'C', true);
        $this->SetY(-30);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 5, 'Angel Stones', 0, 1, 'C');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 'Quality Stone Products & Services', 0, 1, 'C');
        $this->Cell(0, 5, 'Phone: 919-535-7574 | Email: info@theangelstones.com', 0, 1, 'C');
    }
}

// Check if it's a GET request with ID or POST request with quote data
if (isset($_GET['id'])) {
    try {
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quote_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
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
                'quote_number' => $row['quote_number'],
                'quote_date' => $row['quote_date'],
                'commission_amount' => floatval($row['commission_amount']),
                'commission_rate' => floatval($row['commission_rate']),
                'total_amount' => floatval($row['total_amount']),
                'items' => []
            ];
            
            // Fetch quote items with color and special monument information
            $items_sql = "SELECT qi.*, qi.product_type as type, scr.color_name, sm.sp_name as special_monument_name,
                         qi.length, qi.breadth, qi.size,
                         qi.quantity, qi.unit_price, qi.total_price,
                         mp.square_feet as marker_size,
                         sp.size_inches as sertop_size,
                         bp.size_inches as base_size
                         FROM quote_items qi 
                         LEFT JOIN stone_color_rates scr ON qi.color_id = scr.id 
                         LEFT JOIN special_monument sm ON qi.special_monument_id = sm.id
                         LEFT JOIN sertop_products sp ON qi.model = sp.model AND qi.size = sp.size_inches AND qi.product_type = 'sertop'
                         LEFT JOIN base_products bp ON qi.model = bp.model AND qi.size = bp.size_inches AND qi.product_type = 'base'
                         LEFT JOIN marker_products mp ON qi.model = mp.model AND qi.size = mp.square_feet AND qi.product_type = 'marker'
                         LEFT JOIN slant_products slp ON qi.model = slp.model AND qi.product_type = 'slant'
                         WHERE qi.quote_id = ?";
            
            $items_stmt = $pdo->prepare($items_sql);
            $items_stmt->execute([$quote_id]);
            $items_data = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create new PDF document
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Angel Stones');
            $pdf->SetAuthor('Angel Stones');
            $pdf->SetTitle('Quote #' . $row['quote_number']);
            
            // Essential to show header
            $pdf->setHeaderData('', 0, '', '');
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            
            // Set margins
            $pdf->SetMargins(15, 55, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(35);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(true, 35);
            
            // Set image scale factor
            $pdf->setImageScale(1.25);
            
            // Enable header and footer
            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);
            
            // Add first and only page
            $pdf->AddPage();
            
            // Ensure we start from the top after header
            $pdf->SetY(50);
            
            // Set font for the content
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            
            // Quote Number and Date
            $pdf->SetTextColor(51, 51, 51);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(90, 10, 'Quote #' . $row['quote_number'], 0, 0, 'L');
            $pdf->Cell(90, 10, 'Date: ' . date('m/d/Y', strtotime($data['quote_date'])), 0, 1, 'R');

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
            
            // Address formatting
            $address = $data['customer']['address'];
            $city = $data['customer']['city'];
            $state = $data['customer']['state'];
            $postal = $data['customer']['postal_code'];
            
            $pdf->Cell(165, 7, $address, 0, 1, 'L');
            $pdf->SetX($left_x + 25);
            
            // Format city, state, postal
            $location = array_filter([$city, $state, $postal], function($val) { return !empty($val); });
            $pdf->Cell(165, 7, implode(', ', $location), 0, 1, 'L');

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
            $subtotal = 0;
            $total_commission = isset($data['commission_amount']) ? floatval($data['commission_amount']) : 0;

            // First calculate subtotal for commission distribution
            foreach ($items_data as $item) {
                $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
                $subtotal += ($unit_price * $quantity);
            }

            // Now process items and add commission to each
            foreach ($items_data as $item) {
                $length = isset($item['length']) ? floatval($item['length']) : 0;
                $breadth = isset($item['breadth']) ? floatval($item['breadth']) : 0;
                
                $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
                $item_total = $unit_price * $quantity;
                
                // Calculate commission for this item
                $item_commission = 0;
                if ($subtotal > 0) {
                    $commission_ratio = $item_total / $subtotal;
                    $item_commission = $total_commission * $commission_ratio;
                }

                // Add commission to unit price and total
                $unit_price_with_commission = $unit_price + ($item_commission / $quantity);
                $item_total_with_commission = $item_total + $item_commission;
                $total += $item_total_with_commission;
                
                // Set height based on product type
                $type = strtolower($item['type']);
                if ($type === 'marker') {
                    $height = 4; // Fixed height for markers
                } else if (isset($item['size'])) {
                    // For all other types, use the size field directly
                    $height = floatval($item['size']);
                } else {
                    $height = 0; // Default if no valid height found
                }
                
                // Calculate cubic feet
                $cubic_feet = ($length * $breadth * $height) / 1728;
                $cubic_feet = round($cubic_feet * $quantity, 2);
                
                $total_cubic_feet += $cubic_feet;
                
                // Add to items array
                $data['items'][] = [
                    'description' => ucfirst($type) . (isset($item['model']) ? ' - ' . $item['model'] : '') . (isset($item['color_name']) ? ' - ' . $item['color_name'] : '') . (isset($item['special_monument_name']) ? ' - ' . $item['special_monument_name'] : ''),
                    'dimensions' => sprintf('%.2f" × %.2f" × %.2f"', $length, $breadth, $height),
                    'cubic_feet' => $cubic_feet,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price_with_commission,
                    'total_price' => $item_total_with_commission,
                    'type' => $type
                ];
            }
            
            // Display rows with commission included in unit price and total
            foreach ($data['items'] as $item) {
                $pdf->Cell(45, 8, $item['description'], 1, 0, 'L');
                $pdf->Cell(45, 8, $item['dimensions'], 1, 0, 'C');
                $pdf->Cell(25, 8, number_format($item['cubic_feet'], 2), 1, 0, 'C');
                $pdf->Cell(15, 8, $item['quantity'], 1, 0, 'C');
                $pdf->Cell(25, 8, '$' . number_format($item['unit_price'], 2), 1, 0, 'R');
                $pdf->Cell(25, 8, '$' . number_format($item['total_price'], 2), 1, 1, 'R');
            }
            
            // Display totals
            $pdf->SetFont('helvetica', 'B', 11);
            
            // Cubic feet total
            $pdf->Cell(90, 8, 'Total Cu.Ft:', 1, 0, 'R');
            $pdf->Cell(25, 8, number_format($total_cubic_feet, 2), 1, 0, 'C');
            $pdf->Cell(65, 8, '', 1, 1, 'R');
            
            // Final total (includes commission)
            $pdf->Cell(155, 8, 'Total:', 1, 0, 'R');
            $pdf->Cell(25, 8, '$' . number_format($total, 2), 1, 1, 'R');

            // Terms and Conditions
            $pdf->Ln(10);
            $pdf->SetFillColor(34, 40, 49);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Terms and Conditions', 0, 1, 'L', true);
            
            // Reset text color for terms
            $pdf->SetTextColor(51, 51, 51);
            $pdf->SetFont('helvetica', '', 10);
            
            $terms = array(
                "1. This quote is valid for 30 days from the date of issue.",
                "2. 50% advance payment is required to confirm the order.",
                "3. Delivery time will be confirmed after order confirmation.",
                "4. Prices are subject to change without prior notice.",
                "5. All disputes are subject to local jurisdiction."
            );
            
            foreach ($terms as $term) {
                $pdf->Ln(2);
                $pdf->MultiCell(0, 6, $term, 0, 'L');
            }

            // Output the PDF
            $pdf->Output('Quote_' . $row['quote_number'] . '.pdf', 'I');
            exit;
        } else {
            die('Quote not found');
        }
    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
} elseif (isset($_POST['quote_data'])) {
    try {
        // Handle unsaved quote data
        $quote_data = json_decode($_POST['quote_data'], true);
        if (!$quote_data) {
            throw new Exception('Invalid quote data');
        }

        // Validate required fields
        $required_fields = ['customer_name', 'customer_email', 'customer_phone', 'total_amount', 'commission_amount', 'commission_rate', 'subtotal', 'items'];
        foreach ($required_fields as $field) {
            if (!isset($quote_data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Format data for PDF generation
        $data = [
            'customer' => [
                'name' => isset($quote_data['customer_name']) ? $quote_data['customer_name'] : '',
                'email' => isset($quote_data['customer_email']) ? $quote_data['customer_email'] : '',
                'phone' => isset($quote_data['customer_phone']) ? $quote_data['customer_phone'] : '',
                'address' => isset($quote_data['customer_address']) ? $quote_data['customer_address'] : '',
                'city' => isset($quote_data['customer_city']) ? $quote_data['customer_city'] : '',
                'state' => isset($quote_data['customer_state']) ? $quote_data['customer_state'] : '',
                'postal_code' => isset($quote_data['customer_postal_code']) ? $quote_data['customer_postal_code'] : ''
            ],
            'date' => date('m/d/Y'),
            'subtotal' => isset($quote_data['subtotal']) ? $quote_data['subtotal'] : 0,
            'total_amount' => isset($quote_data['total_amount']) ? $quote_data['total_amount'] : 0,
            'commission_amount' => isset($quote_data['commission_amount']) ? $quote_data['commission_amount'] : 0,
            'commission_rate' => isset($quote_data['commission_rate']) ? $quote_data['commission_rate'] : 0,
            'items' => isset($quote_data['items']) ? $quote_data['items'] : []
        ];

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Angel Stones');
        $pdf->SetAuthor('Angel Stones');
        $pdf->SetTitle('Quote');
        
        // Essential to show header
        $pdf->setHeaderData('', 0, '', '');
        $pdf->setHeaderFont(Array('helvetica', '', 10));
        
        // Set margins
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(35);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 35);
        
        // Set image scale factor
        $pdf->setImageScale(1.25);
        
        // Enable header and footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        
        // Add first page
        $pdf->AddPage();
        
        // Start content after header
        $pdf->SetY(50);
        
        // Set font for the content
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        // Quote title and date
        $pdf->SetTextColor(51, 51, 51);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(90, 10, 'Quote', 0, 0, 'L');
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
        
        // Address formatting
        $address = $data['customer']['address'];
        $city = $data['customer']['city'];
        $state = $data['customer']['state'];
        $postal = $data['customer']['postal_code'];
        
        $pdf->Cell(165, 7, $address, 0, 1, 'L');
        $pdf->SetX($left_x + 25);
        
        // Format city, state, postal
        $location = array_filter([$city, $state, $postal], function($val) { return !empty($val); });
        $pdf->Cell(165, 7, implode(', ', $location), 0, 1, 'L');

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
        $subtotal = 0;
        $total_commission = isset($data['commission_amount']) ? floatval($data['commission_amount']) : 0;

        // First calculate subtotal for commission distribution
        foreach ($data['items'] as $item) {
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
            $subtotal += ($unit_price * $quantity);
        }

        // Now process items and add commission to each
        foreach ($data['items'] as $item) {
            $length = isset($item['length']) ? floatval($item['length']) : 0;
            $breadth = isset($item['breadth']) ? floatval($item['breadth']) : 0;
            $size = isset($item['size']) ? floatval($item['size']) : 0;
            
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
            $item_total = $unit_price * $quantity;
            
            // Calculate commission for this item
            $item_commission = 0;
            if ($subtotal > 0) {
                $commission_ratio = $item_total / $subtotal;
                $item_commission = $total_commission * $commission_ratio;
            }

            // Add commission to unit price and total
            $unit_price_with_commission = $unit_price + ($item_commission / $quantity);
            $item_total_with_commission = $item_total + $item_commission;
            $total += $item_total_with_commission;
            
            // Set height based on product type
            $type = strtolower($item['type']);
            if ($type === 'marker') {
                $height = 4; // Fixed height for markers
            } else if (isset($item['size'])) {
                // For all other types, use the size field directly
                $height = floatval($item['size']);
            } else {
                $height = 0; // Default if no valid height found
            }
            
            // Calculate cubic feet
            $cubic_feet = ($length * $breadth * $height) / 1728;
            $cubic_feet = round($cubic_feet * $quantity, 2);
            
            $total_cubic_feet += $cubic_feet;
            
            // Get description
            $description = ucfirst($type);
            if (!empty($item['model'])) {
                $description .= " - " . $item['model'];
            }
            if (!empty($item['color_name'])) {
                $description .= " - " . $item['color_name'];
            }
            
            // Display row with commission included in unit price and total
            $pdf->Cell(45, 8, $description, 1, 0, 'L');
            $pdf->Cell(45, 8, sprintf('%.2f" × %.2f" × %.2f"', $length, $breadth, $size), 1, 0, 'C');
            $pdf->Cell(25, 8, number_format($cubic_feet, 2), 1, 0, 'C');
            $pdf->Cell(15, 8, $quantity, 1, 0, 'C');
            $pdf->Cell(25, 8, '$' . number_format($unit_price_with_commission, 2), 1, 0, 'R');
            $pdf->Cell(25, 8, '$' . number_format($item_total_with_commission, 2), 1, 1, 'R');
        }
        
        // Display totals
        $pdf->SetFont('helvetica', 'B', 11);
        
        // Cubic feet total
        $pdf->Cell(90, 8, 'Total Cu.Ft:', 1, 0, 'R');
        $pdf->Cell(25, 8, number_format($total_cubic_feet, 2), 1, 0, 'C');
        $pdf->Cell(65, 8, '', 1, 1, 'R');
        
        // Final total (includes commission)
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
            $pdf->Ln(2);
            $pdf->MultiCell(0, 6, $term, 0, 'L');
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
        $pdf->Cell(0, 5, 'Phone: 919-535-7574 | Email: info@theangelstones.com', 0, 1, 'C');
        
        // Output PDF
        $pdf->Output('Quote.pdf', 'I');
        exit;
    } catch (Exception $e) {
        die('Error generating PDF: ' . $e->getMessage());
    }
} else {
    die('Either Quote ID or Quote Data is required');
}
?>
