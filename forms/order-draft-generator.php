<?php
// Clear any existing output
if (ob_get_level()) ob_end_clean();

// Debug: Output all POST data to a log file for debugging
$debug_file = fopen(dirname(dirname(__FILE__)) . '/pdf_debug.log', 'a');
$debugOutput = "=== POST DATA DUMP ===\n";
$debugOutput .= print_r($_POST, true);
$debugOutput .= "\n\n=== PROCESSED FORM DATA ===\n";
$debugOutput .= print_r($_POST, true); // Changed $formData to $_POST
file_put_contents('pdf_debug.log', $debugOutput);
fclose($debug_file);

// Try to find and load TCPDF
$tcpdfPaths = [
    __DIR__ . '/../crm/tcpdf/tcpdf.php',  // Updated path with ../ to go up one directory
    '../crm/tcpdf/tcpdf.php',             // Updated path
    dirname(__DIR__) . '/crm/tcpdf/tcpdf.php',
    dirname(dirname(__FILE__)) . '/crm/tcpdf/tcpdf.php', // Updated path
    realpath(__DIR__ . '/../crm/tcpdf/tcpdf.php'),  // Updated path
    realpath('../crm/tcpdf/tcpdf.php')    // Updated path
];

$tcpdfFound = false;
foreach ($tcpdfPaths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $tcpdfFound = true;
        break;
    }
}

if (!$tcpdfFound) {
    // Clear output buffer
    if (ob_get_level()) ob_end_clean();
    
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error: TCPDF Library Not Found</h3>";
    echo "<p>The PDF generation library could not be found at any of the expected locations.</p>";
    echo "<p>Please contact support for assistance.</p>";
    echo "</div>";
    exit;
}

// Define a custom PDF class extending TCPDF
class OrderFormPDF extends TCPDF {
    public function Header() {
        // Skip logo image to avoid PNG alpha channel issues
        // Just add company name text instead
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(0, 0, 128); // Dark blue color
        $this->Cell(80, 10, 'ANGEL STONES', 0, 0, 'L');
        $this->SetTextColor(0, 0, 0); // Reset to black
        
        // Add Order/Quote title 
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(80); // Move to the right
        $this->Cell(40, 10, 'Order / Quote Form', 0, 0, 'C');
        $this->Ln(15);
        
        // Add date
        $this->SetFont('helvetica', '', 10);
        $this->Cell(160, 10, 'Date: ' . date('m-d-Y'), 0, 0, 'R');
        $this->Ln(15);
    }
    
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-25);
        $this->SetFillColor(34, 35, 38); // Dark background
        $this->SetTextColor(214, 183, 114); // Gold text
        $this->Cell(0, 20, '', 0, 1, 'C', true);
        $this->SetY(-20);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 5, 'Angel Stones', 0, 1, 'C');
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 5, 'Phone: (706) 262-7177 | Website: www.theangelstones.com', 0, 1, 'C');
    }
}

/**
 * Sanitize input data to prevent XSS and other attacks
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate if a field is empty and return default value if it is
 * @param string $value The value to check
 * @param string $default Default value to return if empty
 * @return string The original value or default if empty
 */
function validateField($value, $default = 'N/A') {
    return !empty($value) ? $value : $default;
}

try {
    // Get form data from POST request
    $formData = $_POST;
    
    // Set PDF document properties
    $pdf = new OrderFormPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Angel Stones');
    $pdf->SetAuthor('Angel Stones CRM');
    $pdf->SetTitle('Order/Quote Draft');
    $pdf->SetSubject('Order/Quote Form Draft');
    $pdf->SetKeywords('Angel Stones, Order, Quote, Draft');
    
    // Set default header/footer data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    
    // Set margins
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 11);
    
    // Document Type (Order or Quote)
    $formType = isset($formData['form_type']) ? strtoupper($formData['form_type']) : 'ORDER/QUOTE';
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $formType . ' FORM', 0, 1, 'C');
    
    // CUSTOMER INFORMATION SECTION
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'CUSTOMER INFORMATION', 0, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    
    // Customer details table
    $pdf->Ln(2);
    $customerName = validateField($formData['customer_name'] ?? '');
    $customerCompany = validateField($formData['customer_company'] ?? '');
    $customerEmail = validateField($formData['customer_email'] ?? '');
    $customerPhone = validateField($formData['customer_phone'] ?? '');
    $customerAddress = validateField($formData['customer_address'] ?? '');
    $customerCity = validateField($formData['customer_city'] ?? '');
    $customerState = validateField($formData['customer_state'] ?? '');
    $customerZip = validateField($formData['customer_zip'] ?? '');
    
    // First row
    $pdf->Cell(95, 8, 'Name: ' . $customerName, 'LTR', 0, 'L');
    
    // Get salesperson from the form (might be 'sales_person' or 'salesperson')
    $salesPerson = '';
    if (isset($formData['salesperson'])) {
        $salesPerson = $formData['salesperson'];
    } elseif (isset($formData['sales_person'])) {
        $salesPerson = $formData['sales_person']; 
    }
    $pdf->Cell(95, 8, 'Sales Person: ' . validateField($salesPerson), 'LTR', 1, 'L');
    
    // Second row
    $pdf->Cell(95, 8, 'Company: ' . $customerCompany, 'LR', 0, 'L');
    
    // Ship To field (use shipping_name if same_as_billing is not checked, otherwise use customer name)
    $shipTo = $customerName; // Default to customer name
    if (!isset($formData['same_as_billing']) || $formData['same_as_billing'] != '1') {
        // Use shipping name if provided
        $shipTo = validateField($formData['shipping_name'] ?? $customerName);
    }
    $pdf->Cell(95, 8, 'Ship To: ' . $shipTo, 'LR', 1, 'L');
    
    // Third row
    $pdf->Cell(95, 8, 'Address: ' . $customerAddress, 'LR', 0, 'L');
    
    // Get Mark Crate status and details
    $markCrate = isset($formData['mark_crate']) && $formData['mark_crate'] ? 'Yes' : 'No';
    $markCrateDetails = '';
    if (isset($formData['mark_crate']) && $formData['mark_crate'] && !empty($formData['mark_crate_details'])) {
        $markCrateDetails = ' - ' . sanitizeInput($formData['mark_crate_details']);
    }
    $pdf->Cell(95, 8, 'Mark Crate: ' . $markCrate . $markCrateDetails, 'LR', 1, 'L');
    
    // Fourth row
    $pdf->Cell(95, 8, 'City/State/Zip: ' . $customerCity . ', ' . $customerState . ' ' . $customerZip, 'LR', 0, 'L');
    
    // Get Seal & Certificate status
    $sealCertificate = isset($formData['seal_certificate']) && $formData['seal_certificate'] ? 'Yes' : 'No';
    $pdf->Cell(95, 8, 'Seal & Certificate: ' . $sealCertificate, 'LR', 1, 'L');
    
    // Fifth row
    $pdf->Cell(95, 8, 'Phone: ' . $customerPhone, 'LR', 0, 'L');
    $pdf->Cell(95, 8, 'Trucker: ' . validateField($formData['trucker_info'] ?? ''), 'LR', 1, 'L');
    
    // Sixth row
    $pdf->Cell(95, 8, 'Email: ' . $customerEmail, 'LBR', 0, 'L');
    
    // Get payment terms
    $paymentTerms = 'N/A';
    if (isset($formData['payment_terms'])) {
        $paymentTerms = $formData['payment_terms'];
    } elseif (isset($formData['fullPayment']) && $formData['fullPayment'] === 'on') {
        $paymentTerms = 'FULL';
    } elseif (isset($formData['oneThird']) && $formData['oneThird'] === 'on') {
        $paymentTerms = '1/3rd';
    } elseif (isset($formData['oneHalf']) && $formData['oneHalf'] === 'on') {
        $paymentTerms = '1/2';
    } elseif (isset($formData['cod']) && $formData['cod'] === 'on') {
        $paymentTerms = 'COD';
    } elseif (isset($formData['payBeforeShipping']) && $formData['payBeforeShipping'] === 'on') {
        $paymentTerms = 'Pay before shipping';
    }
    $pdf->Cell(95, 8, 'Payment Terms: ' . $paymentTerms, 'LBR', 1, 'L');
    
    $pdf->Ln(5);
    
    // PRODUCT INFORMATION SECTION
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'PRODUCT DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    
    // Create product table header
    $pdf->Ln(2);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(70, 8, 'Product Description', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Unit Price', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Add. Charges', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Total', 1, 1, 'C', true);
    
    // Add product data
    $pdf->SetFont('helvetica', '', 9);
    $rowNumber = 1;
    
    // Check if products data exists
    if (isset($formData['products']) && is_array($formData['products'])) {
        foreach ($formData['products'] as $key => $product) {
            // Skip if this is an empty row
            if (empty($product['product_types']) && empty($product['quantity']) && empty($product['price'])) {
                continue;
            }
            
            // Product description construction
            $productDescription = '';
            
            // Product types
            if (isset($product['product_types']) && is_array($product['product_types'])) {
                $types = implode(', ', $product['product_types']);
                
                // Handle "Other" product type
                if (in_array('Other', $product['product_types']) && !empty($product['other_product_name'])) {
                    $types = str_replace('Other', 'Other: ' . $product['other_product_name'], $types);
                }
                
                $productDescription .= $types . "\n";
            }
            
            // Manufacturing details
            if (!empty($product['manufacturing_details'])) {
                $productDescription .= "Manufacturing: " . $product['manufacturing_details'];
                
                // Add in-house/outsource/inventory options
                $manufacturingOptions = [];
                if (isset($product['in_house']) && $product['in_house']) $manufacturingOptions[] = 'In-House';
                if (isset($product['outsource']) && $product['outsource']) $manufacturingOptions[] = 'Outsource';
                if (isset($product['inventory']) && $product['inventory']) $manufacturingOptions[] = 'Inventory';
                
                if (!empty($manufacturingOptions)) {
                    $productDescription .= " (" . implode(', ', $manufacturingOptions) . ")";
                }
                
                $productDescription .= "\n";
            }
            
            // Description
            if (!empty($product['description'])) {
                $productDescription .= 'Description: ' . $product['description'] . "\n";
            }
            
            // Granite color
            if (!empty($product['granite_color'])) {
                $productDescription .= 'Color: ' . $product['granite_color'] . "\n";
            }
            
            // Sides information
            if (isset($product['sides']) && is_array($product['sides'])) {
                $productDescription .= "\nSides:\n";
                
                foreach ($product['sides'] as $sideKey => $side) {
                    $sideNumber = $sideKey + 1;
                    $productDescription .= "Side $sideNumber:\n";
                    
                    // Process the enhanced side data
                    $sideData = [];
                    
                    // Check if we have JSON data (new format)
                    if (isset($side['data']) && !empty($side['data'])) {
                        $sideData = json_decode($side['data'], true) ?: [];
                    }
                    
                    // Add side notes if available
                    if (isset($sideData['notes']) && !empty($sideData['notes'])) {
                        $productDescription .= "  Notes: " . sanitizeInput($sideData['notes']) . "\n";
                    } elseif (isset($side['notes']) && !empty($side['notes'])) { // Backward compatibility
                        $productDescription .= "  Notes: " . sanitizeInput($side['notes']) . "\n";
                    }
                    
                    // Process all options from the sideData
                    $processedOptions = [];
                    
                    // SHAPE OPTIONS
                    // Shape Drawing
                    if (isset($sideData['side_shape_drawing_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_shape_drawing_' . $productKey . '_' . $sideKey] === '1') {
                        $processedOptions[] = "SHAPE DRAWING";
                    }
                    
                    // Shape Dealer
                    if (isset($sideData['side_shape_dealer_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_shape_dealer_' . $productKey . '_' . $sideKey] === '1') {
                        $processedOptions[] = "SHAPE DEALER";
                    }
                    
                    // Company Drawing
                    if (isset($sideData['side_company_drawing_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_company_drawing_' . $productKey . '_' . $sideKey] === '1') {
                        $processedOptions[] = "COMPANY DRAWING";
                    }
                    
                    // SANDBLAST options
                    if (isset($sideData['side_sandblast_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_sandblast_' . $productKey . '_' . $sideKey] === '1') {
                        $processedOptions[] = "SANDBLAST";
                        
                        // COMPANY DRAFTING
                        if (isset($sideData['side_company_drafting_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_company_drafting_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - COMPANY DRAFTING";
                        }
                        
                        // CUSTOMER DRAFTING STENCIL
                        if (isset($sideData['side_customer_drafting_stencil_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_customer_drafting_stencil_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - CUSTOMER DRAFTING STENCIL";
                        }
                        
                        // SANDBLAST WITH ORDER
                        if (isset($sideData['side_with_order_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_with_order_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - SANDBLAST WITH ORDER";
                        }
                        
                        // BLANK option
                        if (isset($sideData['side_blank_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_blank_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - BLANK";
                        }
                    }
                    
                    // ETCHING options
                    if (isset($sideData['side_etching_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_etching_' . $productKey . '_' . $sideKey] === '1') {
                        $etchingOption = "ETCHING";
                        if (isset($sideData['etching_charge']) && $sideData['etching_charge'] > 0) {
                            $etchingOption .= " ($" . $sideData['etching_charge'] . ")";
                        }
                        $processedOptions[] = $etchingOption;
                    }
                    
                    // S/B CARVING
                    if (isset($sideData['side_sb_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_sb_' . $productKey . '_' . $sideKey] === '1') {
                        $sbOption = "S/B CARVING";
                        if (isset($sideData['sb_carving_charge']) && $sideData['sb_carving_charge'] > 0) {
                            $sbOption .= " ($" . $sideData['sb_carving_charge'] . ")";
                        }
                        $processedOptions[] = $sbOption;
                        
                        // Check for LETTERING option
                        if (isset($sideData['side_lettering_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_lettering_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - LETTERING";
                        }
                        
                        // Check for FLAT option
                        if (isset($sideData['side_flat_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_flat_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - FLAT";
                        }
                        
                        // Check for SHARPED option
                        if (isset($sideData['side_sharped_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_sharped_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - SHARPED";
                        }
                        
                        // Check for ROSE option
                        if (isset($sideData['side_rose_' . $productKey . '_' . $sideKey]) && 
                            $sideData['side_rose_' . $productKey . '_' . $sideKey] === '1') {
                            $processedOptions[] = "  - ROSE";
                        }
                    }
                    
                    // Recess & Mount DEDO
                    if (isset($sideData['side_dedo_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_dedo_' . $productKey . '_' . $sideKey] === '1') {
                        $dedoOption = "Recess & Mount DEDO";
                        if (isset($sideData['dedo_charge']) && $sideData['dedo_charge'] > 0) {
                            $dedoOption .= " ($" . $sideData['dedo_charge'] . ")";
                        }
                        $processedOptions[] = $dedoOption;
                    }
                    
                    // DOMESTIC ADD ON
                    if (isset($sideData['side_domestic_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_domestic_' . $productKey . '_' . $sideKey] === '1') {
                        $processedOptions[] = "DOMESTIC ADD ON";
                    }
                    
                    // DIGITIZATION
                    if (isset($sideData['side_digitization_' . $productKey . '_' . $sideKey]) && 
                        $sideData['side_digitization_' . $productKey . '_' . $sideKey] === '1') {
                        $digitizationOption = "DIGITIZATION";
                        
                        if (isset($sideData['digitization_charge']) && $sideData['digitization_charge'] > 0) {
                            $digitizationOption .= " ($" . $sideData['digitization_charge'] . ")";
                        }
                        
                        if (isset($sideData['digitization_details']) && !empty($sideData['digitization_details'])) {
                            $digitizationOption .= ": " . sanitizeInput($sideData['digitization_details']);
                        }
                        
                        $processedOptions[] = $digitizationOption;
                    }
                    
                    // Add all options to the description
                    if (!empty($processedOptions)) {
                        foreach ($processedOptions as $option) {
                            $productDescription .= "  - " . $option . "\n";
                        }
                    }
                    // Legacy format support
                    if (empty($processedOptions) && isset($side['options'])) {
                        // Side options (old format)
                        $sideOptions = [];
                        if (is_string($side['options'])) {
                            $sideOptions = explode(',', $side['options']);
                            // Process option IDs to make them readable
                            $readableSideOptions = [];
                            foreach ($sideOptions as $option) {
                                // Remove product and side indexing from ID if present
                                $option = preg_replace('/^side_([a-zA-Z0-9_]+)_\d+_\d+$/', '$1', $option);
                                // Convert to readable format
                                $option = str_replace('_', ' ', $option);
                                $readableSideOptions[] = "  - " . ucwords($option);
                            }
                            $sideOptions = $readableSideOptions;
                            $productDescription .= implode("\n", $sideOptions) . "\n";
                        } else if (is_array($side['options'])) {
                            foreach ($side['options'] as $option) {
                                $productDescription .= "  - " . ucwords($option) . "\n";
                            }
                        }
                        
                        // Add digitization details if available
                        if (isset($sideOptions) && is_array($sideOptions) && in_array('digitization', $sideOptions) && 
                            isset($side['digitization']['enabled']) && 
                            $side['digitization']['enabled'] && 
                            !empty($side['digitization']['details'])) {
                            $productDescription .= " (Digitization: " . $side['digitization']['details'] . ")";
                        }
                        
                        // Add S/B charge if present (legacy format)
                        if (isset($side['data'])) {
                            $sideData = json_decode($side['data'], true) ?: [];
                            if (isset($sideData['sb_carving_charge']) && $sideData['sb_carving_charge'] > 0) {
                                $productDescription .= " - S/B Carving ($" . number_format(floatval($sideData['sb_carving_charge']), 2) . ")\n";
                            }
                        }
                    } // End of if(empty($processedOptions))
                    
                    $productDescription .= "\n";
                }
            }
            
            // Get quantity, price and charges
            $quantity = isset($product['quantity']) ? $product['quantity'] : 0;
            $price = isset($product['price']) ? $product['price'] : 0;
            $itemSubtotal = floatval($price) * intval($quantity);
            
            // Calculate additional charges
            $additionalCharges = 0;
            if (isset($product['sides']) && is_array($product['sides'])) {
                foreach ($product['sides'] as $side) {
                    // Process JSON data if available
                    if (isset($side['data'])) {
                        $sideData = json_decode($side['data'], true) ?: [];
                        
                        // Add up all side charges
                        if (isset($sideData['sb_carving_charge'])) $additionalCharges += floatval($sideData['sb_carving_charge']);
                        if (isset($sideData['etching_charge'])) $additionalCharges += floatval($sideData['etching_charge']);
                        if (isset($sideData['dedo_charge'])) $additionalCharges += floatval($sideData['dedo_charge']);
                        if (isset($sideData['digitization_charge'])) $additionalCharges += floatval($sideData['digitization_charge']);
                    }
                    
                    // Legacy format - Add digitization charge if enabled
                    if (isset($side['digitization']['enabled']) && $side['digitization']['enabled']) {
                        if (isset($side['digitization']['charge']) && !empty($side['digitization']['charge'])) {
                            $additionalCharges += floatval($side['digitization']['charge']);
                        }
                    }
                }
            }
            
            // Calculate row total
            $subtotal = floatval($price) * intval($quantity);
            $total = $subtotal + $additionalCharges;
            
            // Print the row with multi-line cell for description
            $pdf->Cell(10, 20, $rowNumber++, 1, 0, 'C');
            
            // Multi-line cell for product description
            $pdf->SetFont('helvetica', '', 8);
            $currentY = $pdf->GetY();
            $currentX = $pdf->GetX();
            $pdf->MultiCell(70, 5, $productDescription, 1, 'L');
            $pdf->SetXY($currentX + 70, $currentY);
            $pdf->SetFont('helvetica', '', 9);
            
            $pdf->Cell(20, 20, $quantity, 1, 0, 'C');
            $pdf->Cell(30, 20, '$' . number_format(floatval($price), 2), 1, 0, 'C');
            $pdf->Cell(30, 20, '$' . number_format($additionalCharges, 2), 1, 0, 'C');
            $pdf->Cell(30, 20, '$' . number_format($total, 2), 1, 1, 'C');
        }
    }
    
    // Special Instructions Section
    if (isset($formData['special_instructions']) && !empty($formData['special_instructions'])) {
        $pdf->Ln(5);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'SPECIAL INSTRUCTIONS', 0, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, sanitizeInput($formData['special_instructions']), 1, 'L');
        $pdf->Ln(5);
    }
    
    // Calculate total of all side charges
    $sideChargesTotal = 0;
    if (isset($formData['products']) && is_array($formData['products'])) {
        foreach ($formData['products'] as $productKey => $product) {
            if (isset($product['sides']) && is_array($product['sides'])) {
                foreach ($product['sides'] as $side) {
                    if (isset($side['data'])) {
                        $sideData = json_decode($side['data'], true) ?: [];
                        
                        // Add up all charges
                        if (isset($sideData['sb_carving_charge'])) $sideChargesTotal += floatval($sideData['sb_carving_charge']);
                        if (isset($sideData['etching_charge'])) $sideChargesTotal += floatval($sideData['etching_charge']);
                        if (isset($sideData['dedo_charge'])) $sideChargesTotal += floatval($sideData['dedo_charge']);
                        if (isset($sideData['digitization_charge'])) $sideChargesTotal += floatval($sideData['digitization_charge']);
                    }
                }
            }
        }
    }
    
    // Add summary totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(130, 8, '', 0, 0);
    $pdf->Cell(30, 8, 'Subtotal:', 1, 0, 'R', true);
    
    // Handle subtotal - check multiple possible field names
    $subtotal = 0;
    if (isset($formData['subtotal']) && is_numeric($formData['subtotal'])) {
        $subtotal = $formData['subtotal'];
    } elseif (isset($formData['subtotalDisplay']) && is_string($formData['subtotalDisplay'])) {
        $subtotal = str_replace(['$', ','], '', $formData['subtotalDisplay']);
    } elseif (isset($formData['subtotal_display']) && is_string($formData['subtotal_display'])) {
        $subtotal = str_replace(['$', ','], '', $formData['subtotal_display']);
    }
    
    // Calculate subtotal from products if it's still 0
    if (floatval($subtotal) <= 0 && isset($formData['products']) && is_array($formData['products'])) {
        foreach ($formData['products'] as $product) {
            $quantity = isset($product['quantity']) ? intval($product['quantity']) : 0;
            $price = isset($product['price']) ? floatval($product['price']) : 0;
            $subtotal += $quantity * $price;
        }
    }
    $pdf->Cell(30, 8, '$' . number_format(floatval($subtotal), 2), 1, 1, 'R');
    
    // Handle additional charges
    $pdf->Cell(130, 8, '', 0, 0);
    $pdf->Cell(30, 8, 'Additional Charges:', 1, 0, 'R', true);
    
    $additionalCharges = 0;
    if (isset($formData['additional_charges_total']) && is_numeric($formData['additional_charges_total'])) {
        $additionalCharges = $formData['additional_charges_total'];
    } elseif (isset($formData['additionalChargesTotal'])) {
        $additionalCharges = str_replace(['$', ','], '', $formData['additionalChargesTotal']);
    }
    
    // Sum up additional charges from products if it's still 0
    if (floatval($additionalCharges) <= 0 && isset($formData['products']) && is_array($formData['products'])) {
        foreach ($formData['products'] as $product) {
            // Include any product-level additional charges here if needed
            if (isset($product['additionalCharges'])) {
                $additionalCharges += floatval($product['additionalCharges']);
            }
        }
    }
    $pdf->Cell(30, 8, '$' . number_format(floatval($additionalCharges), 2), 1, 1, 'R');
    
    // Display side charges if any
    if ($sideChargesTotal > 0) {
        $pdf->Cell(130, 8, '', 0, 0);
        $pdf->Cell(30, 8, 'Side Charges:', 1, 0, 'R', true);
        $pdf->Cell(30, 8, '$' . number_format(floatval($sideChargesTotal), 2), 1, 1, 'R');
    }
    
    // Handle tax
    $pdf->Cell(130, 8, '', 0, 0);
    $taxRate = isset($formData['tax_rate']) ? floatval($formData['tax_rate']) : 0;
    $pdf->Cell(30, 8, 'Tax (' . $taxRate . '%):', 1, 0, 'R', true);
    
    $tax = 0;
    if (isset($formData['tax']) && is_numeric($formData['tax'])) {
        $tax = $formData['tax'];
    } elseif (isset($formData['taxAmount'])) {
        $tax = str_replace(['$', ','], '', $formData['taxAmount']);
    }
    $pdf->Cell(30, 8, '$' . number_format(floatval($tax), 2), 1, 1, 'R');
    
    // Handle grand total
    $pdf->Cell(130, 8, '', 0, 0);
    $pdf->SetFillColor(180, 180, 180);
    $pdf->Cell(30, 8, 'GRAND TOTAL:', 1, 0, 'R', true);
    
    $grandTotal = 0;
    if (isset($formData['grand_total']) && is_numeric($formData['grand_total'])) {
        $grandTotal = $formData['grand_total'];
    } elseif (isset($formData['grandTotal'])) {
        $grandTotal = str_replace(['$', ','], '', $formData['grandTotal']);
    }
    
    // Make sure grand total includes side charges
    $grandTotal = floatval($grandTotal) + floatval($sideChargesTotal);
    $pdf->Cell(30, 8, '$' . number_format(floatval($grandTotal), 2), 1, 1, 'R');
    
    // Notes Section
    if (isset($formData['notes']) && !empty($formData['notes'])) {
        $pdf->Ln(5);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'ADDITIONAL NOTES', 0, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, nl2br($formData['notes']), 1, 'L');
    }
    
    // Generate a unique filename for the PDF
    $customer = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customerName);
    $timestamp = date('Ymd_His');
    $filename = "Order_Quote_Draft_{$customer}_{$timestamp}.pdf";
    
    // Set PDF output mode to download
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    // Handle errors
    if (ob_get_level()) ob_end_clean();
    
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error Generating PDF</h3>";
    echo "<p>An error occurred while trying to generate the order draft: " . $e->getMessage() . "</p>";
    echo "<p><a href='javascript:history.back()'>Go Back</a> and try again.</p>";
    echo "</div>";
}
?>
