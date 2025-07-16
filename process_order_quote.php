<?php
// Production version - Angel Stones Order/Quote Form Processing
// Temporarily enable error display for debugging 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent any accidental output
ob_start();

// Add error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

// Define secure access
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Load configuration from secure file
$config_path = __DIR__ . '/email_config.php';
if (!file_exists($config_path)) {
    die('Configuration error');
}
require_once $config_path;

// Load PHPMailer
$phpmailer_path = __DIR__ . '/crm/vendor/phpmailer/PHPMailer.php';
if (!file_exists($phpmailer_path)) {
    die('PHPMailer library not found');
}
require_once $phpmailer_path;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Sales email configuration with corrected addresses
$salesEmails = [
    'Martha' => 'mruker@angelgranites.com',
    'Candiss' => 'cgetter@angelgranites.com', 
    'Mike' => 'mscoggins@angelgranites.com',
    'Jeremy' => 'jowens@angelgranites.com',
    'Angel' => 'adove@angelgranites.com',
    'Jim' => 'janderson@angelgranites.com',
    'Test' => 'info@angelstones.com'
];

// Initialize response
$response = ['status' => 'error', 'message' => 'An unknown error occurred'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Initialize PHPMailer with exception handling enabled
        $mail = new PHPMailer(true);
        
        // SMTP Configuration from config file
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME; 
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Set email content type
        $mail->isHTML(true);
        
        // Set from and to addresses
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress('da@theangelstones.com', 'Angel Stones Support Team');
        
        // Then find the email sending section and add the CC
        if (isset($_POST['sales_person']) && !empty($_POST['sales_person']) && isset($salesEmails[$_POST['sales_person']])) {
            $salesEmail = $salesEmails[$_POST['sales_person']];
            $mail->addCC($salesEmail, $_POST['sales_person']);
        }

        // Set email subject based on form type
        $formType = isset($_POST['form_type']) ? $_POST['form_type'] : 'Quote';
        $mail->Subject = "Angel Stones - New " . $formType . " Request";
        
        // Start building email content
        $emailContent = "<html><body>";
        $emailContent .= "<h1>Angel Stones - " . $formType . " Form Submission</h1>";
        
        // Customer Information - Enhanced to capture all dynamic fields
        $emailContent .= "<h2>Customer Information</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Enhanced customer fields mapping with all possible variations
        $customerFields = [
            'customer_name' => 'Name',
            'customer_company' => 'Company',
            'customer_email' => 'Email',
            'customer_phone' => 'Phone',
            'customer_mobile' => 'Mobile',
            'customer_address' => 'Address',
            'customer_address1' => 'Address Line 1',
            'customer_address2' => 'Address Line 2',
            'customer_city' => 'City',
            'customer_state' => 'State',
            'customer_zip' => 'ZIP',
            'customer_country' => 'Country',
            'customer_fax' => 'Fax',
            'customer_website' => 'Website',
            'customer_contact_person' => 'Contact Person',
            'customer_title' => 'Title',
            'customer_department' => 'Department',
            'customer_preferred_contact' => 'Preferred Contact Method',
            'customer_best_time' => 'Best Time to Contact',
            'customer_notes' => 'Customer Notes'
        ];
        
        // Process all customer fields
        foreach ($customerFields as $field => $label) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $value = is_array($_POST[$field]) ? implode(', ', $_POST[$field]) : htmlspecialchars($_POST[$field]);
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$value}</td></tr>";
            }
        }
        
        // Capture any additional customer fields that might be dynamically added
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'customer_') === 0 && !isset($customerFields[$key]) && !empty($value)) {
                $label = ucwords(str_replace(['customer_', '_'], ['', ' '], $key));
                $displayValue = is_array($value) ? implode(', ', $value) : htmlspecialchars($value);
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$displayValue}</td></tr>";
            }
        }
        
        $emailContent .= "</table>";
        
        // Payment Information - Enhanced to capture all dynamic fields
        $emailContent .= "<h2>Payment & Shipping Details</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Enhanced payment fields mapping
        $paymentFields = [
            'payment_terms' => 'Payment Terms',
            'payment_type' => 'Payment Type',
            'payment_method' => 'Payment Method',
            'payment_schedule' => 'Payment Schedule',
            'payment_due_date' => 'Payment Due Date',
            'payment_discount' => 'Payment Discount',
            'payment_notes' => 'Payment Notes',
            'cc_last_four' => 'Card Last Four',
            'cc_type' => 'Card Type',
            'cc_expiry' => 'Card Expiry',
            'bank_name' => 'Bank Name',
            'account_number' => 'Account Number',
            'routing_number' => 'Routing Number',
            'check_number' => 'Check Number',
            'po_number' => 'PO Number',
            'invoice_number' => 'Invoice Number',
            'sales_person' => 'Sales Person',
            'sales_rep' => 'Sales Representative',
            'project_manager' => 'Project Manager',
            'trucker_info' => 'Trucker Information',
            'trucker_name' => 'Trucker Name',
            'trucker_phone' => 'Trucker Phone',
            'delivery_date' => 'Delivery Date',
            'delivery_time' => 'Delivery Time',
            'delivery_instructions' => 'Delivery Instructions',
            'installation_date' => 'Installation Date',
            'installation_time' => 'Installation Time',
            'installation_notes' => 'Installation Notes',
            'terms' => 'Terms',
            'special_instructions' => 'Special Instructions',
            'rush_order' => 'Rush Order',
            'priority_level' => 'Priority Level'
        ];
        
        // Process all payment fields
        foreach ($paymentFields as $field => $label) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $value = is_array($_POST[$field]) ? implode(', ', $_POST[$field]) : htmlspecialchars($_POST[$field]);
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$value}</td></tr>";
            }
        }
        
        // Handle checkbox fields specifically
        $checkboxFields = [
            'first_order' => 'First Order',
            'seal_certificate' => 'Seal & Certificate',
            'mark_crate' => 'Mark Crate',
            'rush_delivery' => 'Rush Delivery',
            'installation_required' => 'Installation Required',
            'pickup_available' => 'Pickup Available',
            'delivery_required' => 'Delivery Required'
        ];
        
        foreach ($checkboxFields as $field => $label) {
            if (isset($_POST[$field]) && ($_POST[$field] == '1' || $_POST[$field] === 'on' || $_POST[$field] === true)) {
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>Yes</td></tr>";
                
                // Check for related detail fields
                $detailField = $field . '_details';
                if (isset($_POST[$detailField]) && !empty($_POST[$detailField])) {
                    $details = htmlspecialchars($_POST[$detailField]);
                    $emailContent .= "<tr><td><strong>{$label} Details</strong></td><td>{$details}</td></tr>";
                }
            }
        }
        
        // Capture any additional payment/order fields that might be dynamically added
        foreach ($_POST as $key => $value) {
            if ((strpos($key, 'payment_') === 0 || strpos($key, 'order_') === 0 || strpos($key, 'delivery_') === 0 || strpos($key, 'installation_') === 0) 
                && !isset($paymentFields[$key]) && !isset($checkboxFields[$key]) && !empty($value)) {
                $label = ucwords(str_replace(['payment_', 'order_', 'delivery_', 'installation_', '_'], ['', '', '', '', ' '], $key));
                $displayValue = is_array($value) ? implode(', ', $value) : htmlspecialchars($value);
                $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$displayValue}</td></tr>";
            }
        }
        
        $emailContent .= "</table>";
        
        // Shipping Information - Enhanced to capture all dynamic fields
        $emailContent .= "<h2>Shipping Information</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        // Check if same as billing
        if (isset($_POST['same_as_billing']) && ($_POST['same_as_billing'] === 'on' || $_POST['same_as_billing'] === '1')) {
            $emailContent .= "<tr><td colspan='2'><strong>Same as Customer Information</strong></td></tr>";
        } else {
            // Enhanced shipping fields mapping
            $shippingFields = [
                'shipping_name' => 'Name',
                'shipping_company' => 'Company',
                'shipping_contact_person' => 'Contact Person',
                'shipping_title' => 'Title',
                'shipping_department' => 'Department',
                'shipping_address' => 'Address',
                'shipping_address1' => 'Address Line 1',
                'shipping_address2' => 'Address Line 2',
                'shipping_address3' => 'Address Line 3',
                'shipping_city' => 'City',
                'shipping_state' => 'State',
                'shipping_zip' => 'ZIP',
                'shipping_country' => 'Country',
                'shipping_phone' => 'Phone',
                'shipping_mobile' => 'Mobile',
                'shipping_fax' => 'Fax',
                'shipping_email' => 'Email',
                'shipping_website' => 'Website',
                'shipping_notes' => 'Shipping Notes',
                'shipping_instructions' => 'Shipping Instructions',
                'shipping_dock_hours' => 'Dock Hours',
                'shipping_contact_hours' => 'Contact Hours',
                'shipping_gate_code' => 'Gate Code',
                'shipping_building_floor' => 'Building/Floor',
                'shipping_suite_unit' => 'Suite/Unit',
                'shipping_loading_dock' => 'Loading Dock',
                'shipping_appointment_required' => 'Appointment Required',
                'shipping_special_equipment' => 'Special Equipment Needed'
            ];
            
            // Process all shipping fields
            foreach ($shippingFields as $field => $label) {
                if (isset($_POST[$field]) && !empty($_POST[$field])) {
                    $value = is_array($_POST[$field]) ? implode(', ', $_POST[$field]) : htmlspecialchars($_POST[$field]);
                    $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$value}</td></tr>";
                }
            }
            
            // Capture any additional shipping fields that might be dynamically added
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'shipping_') === 0 && !isset($shippingFields[$key]) && !empty($value)) {
                    $label = ucwords(str_replace(['shipping_', '_'], ['', ' '], $key));
                    $displayValue = is_array($value) ? implode(', ', $value) : htmlspecialchars($value);
                    $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$displayValue}</td></tr>";
                }
            }
        }
        
        $emailContent .= "</table>";
        
        // Products Information
        $emailContent .= "<h2>Product Information</h2>";
        
        if (isset($_POST['products']) && is_array($_POST['products'])) {
            $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            $emailContent .= "<tr style='background-color: #f2f2f2;'>";
            $emailContent .= "<th>#</th><th>Product</th><th>Quantity</th><th>Price</th><th>Additional Charges</th><th>Total</th>";
            $emailContent .= "</tr>";
        
            $rowNumber = 1;
            foreach ($_POST['products'] as $product) {
                $productName = htmlspecialchars($product['name'] ?? '');
                $quantity = htmlspecialchars($product['quantity'] ?? '1');
                $price = htmlspecialchars($product['price'] ?? '0.00');
                $additionalChargesTotal = 0;
                
                // Build detailed product information for email
                $productDetails = "";
                
                // Add Product Type if available
                if (isset($product['product_type']) && is_array($product['product_type'])) {
                    $productDetails .= "<br><strong>Product Type:</strong> ";
                    $typeLabels = [];
                    foreach ($product['product_type'] as $type => $isSelected) {
                        if ($isSelected) {
                            $typeLabels[] = htmlspecialchars($type);
                        }
                    }
                    $productDetails .= implode(", ", $typeLabels);
                }
                
                // Add Other Product Name if available
                if (isset($product['other_product_name']) && !empty($product['other_product_name'])) {
                    $productDetails .= "<br><strong>Other Product Name/Code:</strong> " . htmlspecialchars($product['other_product_name']);
                }
                
                // Add Manufacturing Type if available
                if (isset($product['manufacturing_type'])) {
                    $productDetails .= "<br><strong>Manufacturing:</strong> " . htmlspecialchars($product['manufacturing_type']);
                }
                
                // Add Manufacturing Details if available
                if (isset($product['manufacturing_details']) && !empty($product['manufacturing_details'])) {
                    $productDetails .= "<br><strong>Manufacturing Details:</strong> " . htmlspecialchars($product['manufacturing_details']);
                }
                
                if (isset($product['manufacturing_option']) && !empty($product['manufacturing_option'])) {
                    $productDetails .= " - " . htmlspecialchars($product['manufacturing_option']);
                }
        
                // Calculate additional charges and add side details to email
                if (isset($product['sides']) && is_array($product['sides'])) {
                    $sideNumber = 1;
                    foreach ($product['sides'] as $side) {
                        $productDetails .= "<br><br><strong>Side {$sideNumber} Details:</strong>";
                        
                        // Add side notes if available
                        if (isset($side['notes']) && !empty($side['notes'])) {
                            $productDetails .= "<br>Notes: " . htmlspecialchars($side['notes']);
                        }
                        
                        // Add S/B CARVING details and charge
                        if (isset($side['sb_carving']['enabled'])) {
                            $productDetails .= "<br>S/B CARVING: ";
                            if (isset($side['sb_carving']['type'])) {
                                $productDetails .= htmlspecialchars($side['sb_carving']['type']);
                            }
                            
                            if (isset($side['sb_carving']['style']) && !empty($side['sb_carving']['style'])) {
                                $productDetails .= " - Style: " . htmlspecialchars($side['sb_carving']['style']);
                            }
                            
                            if (isset($side['sb_carving']['text']) && !empty($side['sb_carving']['text'])) {
                                $productDetails .= "<br>Text: " . htmlspecialchars($side['sb_carving']['text']);
                            }
                            
                            if (isset($side['sb_carving']['charge']) && floatval($side['sb_carving']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['sb_carving']['charge']), 2);
                                $additionalChargesTotal += floatval($side['sb_carving']['charge']);
                            }
                        }
        
                        // Add ETCHING details and charge
                        if (isset($side['etching']['enabled'])) {
                            $productDetails .= "<br>ETCHING: ";
                            if (isset($side['etching']['type'])) {
                                $productDetails .= htmlspecialchars($side['etching']['type']);
                            }
                            
                            if (isset($side['etching']['text']) && !empty($side['etching']['text'])) {
                                $productDetails .= "<br>Text: " . htmlspecialchars($side['etching']['text']);
                            }
                            
                            if (isset($side['etching']['charge']) && floatval($side['etching']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['etching']['charge']), 2);
                                $additionalChargesTotal += floatval($side['etching']['charge']);
                            }
                        }
        
                        // Add DEDO details and charge
                        if (isset($side['dedo']['enabled'])) {
                            $productDetails .= "<br>Recess & Mount DEDO";
                            if (isset($side['dedo']['charge']) && floatval($side['dedo']['charge']) > 0) {
                                $productDetails .= ": $" . number_format(floatval($side['dedo']['charge']), 2);
                                $additionalChargesTotal += floatval($side['dedo']['charge']);
                            }
                        }
        
                        // Add DOMESTIC ADD ON details and charge
                        if (isset($side['domestic_addon']['enabled'])) {
                            $productDetails .= "<br>DOMESTIC ADD ON:";
                            
                            if (isset($side['domestic_addon']['dim1']) && !empty($side['domestic_addon']['dim1'])) {
                                $productDetails .= " (" . htmlspecialchars($side['domestic_addon']['dim1']);
                                
                                if (isset($side['domestic_addon']['dim2']) && !empty($side['domestic_addon']['dim2'])) {
                                    $productDetails .= " x " . htmlspecialchars($side['domestic_addon']['dim2']);
                                }
                                
                                $productDetails .= ")";
                            }
                            
                            if (isset($side['domestic_addon']['charge']) && floatval($side['domestic_addon']['charge']) > 0) {
                                $productDetails .= "<br>Charge: $" . number_format(floatval($side['domestic_addon']['charge']), 2);
                                $additionalChargesTotal += floatval($side['domestic_addon']['charge']);
                            }
                        }
        
                        // Add DIGITIZATION charge
                        if (isset($side['digitization']['enabled'])) {
                            $productDetails .= "<br>DIGITIZATION";
                            if (isset($side['digitization']['charge']) && floatval($side['digitization']['charge']) > 0) {
                                $productDetails .= ": $" . number_format(floatval($side['digitization']['charge']), 2);
                                $additionalChargesTotal += floatval($side['digitization']['charge']);
                            }
                            // Add digitization details if available
                            if (isset($side['digitization']['details']) && !empty($side['digitization']['details'])) {
                                $productDetails .= "<br>Digitization Details: " . htmlspecialchars($side['digitization']['details']);
                            }
                        }
        
                        // Add miscellaneous charge
                        if (isset($side['misc_charge']) && floatval($side['misc_charge']) > 0) {
                            $productDetails .= "<br>Additional Charges: $" . number_format(floatval($side['misc_charge']), 2);
                            $additionalChargesTotal += floatval($side['misc_charge']);
                        }
                        
                        $sideNumber++;
                    }
                }
        
                $subtotal = floatval($price) * intval($quantity);
                $total = $subtotal + $additionalChargesTotal;
        
                $emailContent .= "<tr>";
                $emailContent .= "<td>" . $rowNumber++ . "</td>";
                $emailContent .= "<td>" . $productName;
                // Add the detailed product information
                $emailContent .= $productDetails;
                $emailContent .= "</td>";
                $emailContent .= "<td>" . $quantity . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($price), 2) . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($additionalChargesTotal), 2) . "</td>";
                $emailContent .= "<td>$" . number_format(floatval($total), 2) . "</td>";
                $emailContent .= "</tr>";
            }
        
            // Add summary totals
            $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.00;
            $additionalChargesTotal = isset($_POST['additional_charges_total']) ? floatval($_POST['additional_charges_total']) : 0.00;
            $taxRate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0.00;
            $taxAmount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0.00;
            $grandTotal = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0.00;
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Subtotal:</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($subtotal), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Additional Charges:</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($additionalChargesTotal), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>Tax (" . number_format(floatval($taxRate), 2) . "%):</strong></td>";
            $emailContent .= "<td>$" . number_format(floatval($taxAmount), 2) . "</td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "<tr style='background-color: #f2f2f2;'>";
            $emailContent .= "<td colspan='5' style='text-align: right;'><strong>TOTAL:</strong></td>";
            $emailContent .= "<td><strong>$" . number_format(floatval($grandTotal), 2) . "</strong></td>";
            $emailContent .= "</tr>";
        
            $emailContent .= "</table>";
        } else {
            $emailContent .= "<p>No product information provided.</p>";
        }
        
        // Notes Section
        if (isset($_POST['notes']) && !empty($_POST['notes'])) {
            $emailContent .= "<h2>Additional Notes</h2>";
            $emailContent .= "<p>" . nl2br(htmlspecialchars($_POST['notes'])) . "</p>";
        }
        
        // Additional Dynamic Fields Section - Capture any fields not processed above
        $processedFields = array_merge(
            array_keys($customerFields),
            array_keys($paymentFields), 
            array_keys($checkboxFields),
            array_keys($shippingFields),
            ['products', 'notes', 'subtotal', 'additional_charges_total', 'tax_rate', 'tax_amount', 'grand_total', 'form_type', 'same_as_billing']
        );
        
        $additionalFields = [];
        foreach ($_POST as $key => $value) {
            // Skip processed fields, empty values, and system fields
            if (!in_array($key, $processedFields) && !empty($value) && 
                !in_array($key, ['submit', 'action', 'csrf_token', 'timestamp']) &&
                !strpos($key, 'customer_') === 0 && !strpos($key, 'shipping_') === 0 && 
                !strpos($key, 'payment_') === 0 && !strpos($key, 'order_') === 0 &&
                !strpos($key, 'delivery_') === 0 && !strpos($key, 'installation_') === 0) {
                
                $additionalFields[$key] = $value;
            }
        }
        
        if (!empty($additionalFields)) {
            $emailContent .= "<h2>Additional Form Fields</h2>";
            $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
            
            foreach ($additionalFields as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $displayValue = is_array($value) ? implode(', ', array_filter($value)) : htmlspecialchars($value);
                if (!empty($displayValue)) {
                    $emailContent .= "<tr><td><strong>{$label}</strong></td><td>{$displayValue}</td></tr>";
                }
            }
            
            $emailContent .= "</table>";
        }
        
        // Form Submission Details
        $emailContent .= "<h2>Form Submission Details</h2>";
        $emailContent .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $emailContent .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        $emailContent .= "<tr><td><strong>Submission Time</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
        $emailContent .= "<tr><td><strong>IP Address</strong></td><td>" . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</td></tr>";
        $emailContent .= "<tr><td><strong>User Agent</strong></td><td>" . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</td></tr>";
        if (isset($_SERVER['HTTP_REFERER'])) {
            $emailContent .= "<tr><td><strong>Referrer</strong></td><td>" . htmlspecialchars($_SERVER['HTTP_REFERER']) . "</td></tr>";
        }
        $emailContent .= "</table>";
        
        // Close HTML
        $emailContent .= "</body></html>";
        
        // Generate PDF attachment
        $pdfAttached = false;
        try {
            // Load MYPDF class
            require_once(__DIR__ . '/crm/includes/mypdf.php');
            
            // Create new PDF document
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Angel Stones');
            $pdf->SetAuthor('Angel Stones');
            $pdf->SetTitle('Order/Quote Form - ' . date('Y-m-d H:i:s'));
            $pdf->SetSubject('Customer Order/Quote Request');
            
            // Set margins
            $pdf->SetMargins(15, 55, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(35);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 40);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Convert HTML email content to PDF-friendly format
            $pdfContent = $emailContent;
            
            // Remove HTML and BODY tags for PDF
            $pdfContent = str_replace(['<html><body>', '</body></html>'], '', $pdfContent);
            
            // Write HTML content
            $pdf->writeHTML($pdfContent, true, false, true, false, '');
            
            // Generate PDF file
            $pdfFileName = 'order_quote_' . date('Ymd_His') . '.pdf';
            $pdfPath = sys_get_temp_dir() . '/' . $pdfFileName;
            $pdf->Output($pdfPath, 'F');
            
            // Attach PDF to email
            if (file_exists($pdfPath)) {
                $mail->addAttachment($pdfPath, $pdfFileName);
                $pdfAttached = true;
            }
            
        } catch (Exception $e) {
            // Log PDF generation error but continue with email
            error_log("PDF Generation Error: " . $e->getMessage());
        }
        
        // Set email body
        $mail->Body = $emailContent;
        
        // Process file attachments
        $uploadedFiles = [];
        
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
        
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xls', 'xlsx'];
        
            // Count valid files
            $fileCount = count($_FILES['attachments']['name']);
        
            for ($i = 0; $i < $fileCount; $i++) {
                // Skip if there was an upload error
                if ($_FILES['attachments']['error'][$i] != 0) {
                    continue;
                }
        
                $fileName = $_FILES['attachments']['name'][$i];
                $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $fileType = $_FILES['attachments']['type'][$i];
        
                // Get file extension
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
                // Validate file extension and MIME type
                if (!in_array($fileExtension, $allowedExtensions) || !in_array($fileType, $allowedMimes)) {
                    continue; // Skip invalid files
                }
        
                // Basic virus check - rename file with safe name
                $safeFileName = md5($fileName . time()) . '.' . $fileExtension;
                $uploadDir = __DIR__ . '/uploads/';
        
                // Create uploads directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
        
                $uploadPath = $uploadDir . $safeFileName;
        
                // Move the file to the upload directory
                if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                    $mail->addAttachment($uploadPath, $fileName);
                    $uploadedFiles[] = $uploadPath; // Remember file for cleanup
                }
            }
        }
        
        
        // Generate PDF attachment using existing TCPDF setup
        $pdfPath = null;
        try {
            // Load existing MYPDF class
            require_once(__DIR__ . '/crm/includes/mypdf.php');
            
            // Create PDF
            $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('Angel Stones');
            $pdf->SetAuthor('Angel Stones');
            $pdf->SetTitle('Order/Quote Form');
            $pdf->SetSubject('Order/Quote Submission');
            
            // Set margins
            $pdf->SetMargins(15, 50, 15);
            $pdf->SetAutoPageBreak(TRUE, 35);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'ORDER/QUOTE FORM', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Customer Information Section
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'CUSTOMER INFORMATION', 0, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Ln(2);
            
            // Customer details in table format
            foreach ($customerFields as $field => $label) {
                if (isset($_POST[$field]) && !empty($_POST[$field])) {
                    $value = htmlspecialchars($_POST[$field]);
                    $pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
                    $pdf->Cell(0, 6, $value, 0, 1, 'L');
                }
            }
            
            $pdf->Ln(5);
            
            // Payment & Shipping Details
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'PAYMENT & SHIPPING DETAILS', 0, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Ln(2);
            
            if (isset($_POST['payment_terms'])) {
                $pdf->Cell(50, 6, 'Payment Terms:', 0, 0, 'L');
                $pdf->Cell(0, 6, htmlspecialchars($_POST['payment_terms']), 0, 1, 'L');
            }
            
            if (isset($_POST['trucker_info']) && !empty($_POST['trucker_info'])) {
                $pdf->Cell(50, 6, 'Trucker Info:', 0, 0, 'L');
                $pdf->Cell(0, 6, htmlspecialchars($_POST['trucker_info']), 0, 1, 'L');
            }
            
            if (isset($_POST['terms']) && !empty($_POST['terms'])) {
                $pdf->Cell(50, 6, 'Terms:', 0, 0, 'L');
                $pdf->MultiCell(0, 6, htmlspecialchars($_POST['terms']), 0, 'L');
            }
            
            // Add checkbox fields to PDF
            if (isset($_POST['first_order']) && $_POST['first_order'] == '1') {
                $pdf->Cell(50, 6, 'First Order:', 0, 0, 'L');
                $pdf->Cell(0, 6, 'Yes', 0, 1, 'L');
            }
            
            if (isset($_POST['seal_certificate']) && $_POST['seal_certificate'] == '1') {
                $pdf->Cell(50, 6, 'Seal & Certificate:', 0, 0, 'L');
                $pdf->Cell(0, 6, 'Yes', 0, 1, 'L');
            }
            
            if (isset($_POST['mark_crate']) && $_POST['mark_crate'] == '1') {
                $pdf->Cell(50, 6, 'Mark Crate:', 0, 0, 'L');
                $markCrateText = 'Yes';
                if (isset($_POST['mark_crate_details']) && !empty($_POST['mark_crate_details'])) {
                    $markCrateText .= ' - ' . htmlspecialchars($_POST['mark_crate_details']);
                }
                $pdf->Cell(0, 6, $markCrateText, 0, 1, 'L');
            }
            
            $pdf->Ln(5);
            
            // Products Section - Enhanced with detailed information
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, 'PRODUCT INFORMATION', 0, 1, 'L', true);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Ln(2);
                
                // Product table header
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell(8, 8, '#', 1, 0, 'C', true);
                $pdf->Cell(60, 8, 'Product', 1, 0, 'C', true);
                $pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Price', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Add. Charges', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'Total', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 7);
                $rowNumber = 1;
                
                foreach ($_POST['products'] as $product) {
                    $productName = htmlspecialchars($product['name'] ?? '');
                    $quantity = intval($product['quantity'] ?? '1');
                    $price = floatval($product['price'] ?? '0.00');
                    $additionalChargesTotal = 0;
                    
                    // Calculate additional charges from sides
                    if (isset($product['sides']) && is_array($product['sides'])) {
                        foreach ($product['sides'] as $side) {
                            if (isset($side['sb_carving']['charge'])) {
                                $additionalChargesTotal += floatval($side['sb_carving']['charge']);
                            }
                            if (isset($side['etching']['charge'])) {
                                $additionalChargesTotal += floatval($side['etching']['charge']);
                            }
                            if (isset($side['dedo']['charge'])) {
                                $additionalChargesTotal += floatval($side['dedo']['charge']);
                            }
                            if (isset($side['domestic_addon']['charge'])) {
                                $additionalChargesTotal += floatval($side['domestic_addon']['charge']);
                            }
                            if (isset($side['digitization']['charge'])) {
                                $additionalChargesTotal += floatval($side['digitization']['charge']);
                            }
                            if (isset($side['misc_charge'])) {
                                $additionalChargesTotal += floatval($side['misc_charge']);
                            }
                        }
                    }
                    
                    $subtotal = $price * $quantity;
                    $total = $subtotal + $additionalChargesTotal;
                    
                    // Main product row
                    $pdf->Cell(8, 8, $rowNumber++, 1, 0, 'C');
                    $pdf->Cell(60, 8, $productName, 1, 0, 'L');
                    $pdf->Cell(15, 8, $quantity, 1, 0, 'C');
                    $pdf->Cell(25, 8, '$' . number_format($price, 2), 1, 0, 'R');
                    $pdf->Cell(25, 8, '$' . number_format($additionalChargesTotal, 2), 1, 0, 'R');
                    $pdf->Cell(25, 8, '$' . number_format($total, 2), 1, 1, 'R');
                    
                    // Add detailed side information if available
                    if (isset($product['sides']) && is_array($product['sides'])) {
                        $sideNumber = 1;
                        foreach ($product['sides'] as $side) {
                            $pdf->SetFont('helvetica', '', 6);
                            
                            // Side details
                            $sideDetails = "  Side {$sideNumber}: ";
                            $sideCharges = [];
                            
                            if (isset($side['sb_carving']['enabled']) && $side['sb_carving']['enabled']) {
                                $sbText = isset($side['sb_carving']['text']) ? $side['sb_carving']['text'] : '';
                                $sbCharge = isset($side['sb_carving']['charge']) ? floatval($side['sb_carving']['charge']) : 0;
                                if ($sbCharge > 0) {
                                    $sideCharges[] = "S/B Carving: {$sbText} (\${$sbCharge})";
                                }
                            }
                            
                            if (isset($side['etching']['enabled']) && $side['etching']['enabled']) {
                                $etchText = isset($side['etching']['text']) ? $side['etching']['text'] : '';
                                $etchCharge = isset($side['etching']['charge']) ? floatval($side['etching']['charge']) : 0;
                                if ($etchCharge > 0) {
                                    $sideCharges[] = "Etching: {$etchText} (\${$etchCharge})";
                                }
                            }
                            
                            if (isset($side['dedo']['enabled']) && $side['dedo']['enabled']) {
                                $dedoCharge = isset($side['dedo']['charge']) ? floatval($side['dedo']['charge']) : 0;
                                if ($dedoCharge > 0) {
                                    $sideCharges[] = "DEDO (\${$dedoCharge})";
                                }
                            }
                            
                            if (isset($side['domestic_addon']['enabled']) && $side['domestic_addon']['enabled']) {
                                $domCharge = isset($side['domestic_addon']['charge']) ? floatval($side['domestic_addon']['charge']) : 0;
                                if ($domCharge > 0) {
                                    $sideCharges[] = "Domestic Add-on (\${$domCharge})";
                                }
                            }
                            
                            if (isset($side['digitization']['enabled']) && $side['digitization']['enabled']) {
                                $digCharge = isset($side['digitization']['charge']) ? floatval($side['digitization']['charge']) : 0;
                                if ($digCharge > 0) {
                                    $sideCharges[] = "Digitization (\${$digCharge})";
                                }
                            }
                            
                            if (isset($side['misc_charge']) && floatval($side['misc_charge']) > 0) {
                                $miscCharge = floatval($side['misc_charge']);
                                $sideCharges[] = "Additional (\${$miscCharge})";
                            }
                            
                            if (!empty($sideCharges)) {
                                $sideDetails .= implode(', ', $sideCharges);
                                $pdf->Cell(8, 6, '', 0, 0, 'C');
                                $pdf->Cell(150, 6, $sideDetails, 0, 1, 'L');
                            }
                            
                            $sideNumber++;
                        }
                    }
                    
                    // Add product type and manufacturing details if available
                    $productDetails = [];
                    if (isset($product['product_type']) && is_array($product['product_type'])) {
                        $types = [];
                        foreach ($product['product_type'] as $type => $isSelected) {
                            if ($isSelected) {
                                $types[] = $type;
                            }
                        }
                        if (!empty($types)) {
                            $productDetails[] = "Type: " . implode(", ", $types);
                        }
                    }
                    
                    if (isset($product['manufacturing_type']) && !empty($product['manufacturing_type'])) {
                        $productDetails[] = "Manufacturing: " . $product['manufacturing_type'];
                    }
                    
                    if (isset($product['manufacturing_details']) && !empty($product['manufacturing_details'])) {
                        $productDetails[] = "Details: " . $product['manufacturing_details'];
                    }
                    
                    if (!empty($productDetails)) {
                        $pdf->SetFont('helvetica', 'I', 6);
                        $pdf->Cell(8, 5, '', 0, 0, 'C');
                        $pdf->Cell(150, 5, "  " . implode(" | ", $productDetails), 0, 1, 'L');
                    }
                    
                    $pdf->SetFont('helvetica', '', 7);
                }
                
                // Add comprehensive totals breakdown
                $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.00;
                $additionalChargesTotal = isset($_POST['additional_charges_total']) ? floatval($_POST['additional_charges_total']) : 0.00;
                $taxRate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0.00;
                $taxAmount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0.00;
                $grandTotal = isset($_POST['grand_total']) ? floatval($_POST['grand_total']) : 0.00;
                
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Cell(133, 6, 'Subtotal:', 1, 0, 'R');
                $pdf->Cell(25, 6, '$' . number_format($subtotal, 2), 1, 1, 'R');
                
                $pdf->Cell(133, 6, 'Additional Charges:', 1, 0, 'R');
                $pdf->Cell(25, 6, '$' . number_format($additionalChargesTotal, 2), 1, 1, 'R');
                
                if ($taxRate > 0) {
                    $pdf->Cell(133, 6, 'Tax (' . number_format($taxRate, 2) . '%):', 1, 0, 'R');
                    $pdf->Cell(25, 6, '$' . number_format($taxAmount, 2), 1, 1, 'R');
                }
                
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->Cell(133, 8, 'GRAND TOTAL:', 1, 0, 'R', true);
                $pdf->Cell(25, 8, '$' . number_format($grandTotal, 2), 1, 1, 'R', true);
            }
            
            // Notes section
            if (isset($_POST['notes']) && !empty($_POST['notes'])) {
                $pdf->Ln(5);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, 'ADDITIONAL NOTES', 0, 1, 'L', true);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Ln(2);
                $pdf->MultiCell(0, 6, htmlspecialchars($_POST['notes']), 1, 'L');
            }
            
            // Save PDF to temporary file
            $pdfPath = __DIR__ . '/temp_order_' . time() . '.pdf';
            $pdf->Output($pdfPath, 'F');
            
            // Attach PDF to email
            if (file_exists($pdfPath)) {
                $mail->addAttachment($pdfPath, 'Order_Quote_Form.pdf');
            }
            
        } catch (Exception $e) {
            // Continue without PDF if generation fails
        }

        // Send email
        try {
            // Set subject line from form data
            $subject = isset($_POST['customer_name']) ? "Order/Quote from " . $_POST['customer_name'] : "New Order/Quote Submission";
            $mail->Subject = $subject;
            
            // Send email
            if ($mail->send()) {
                // Email sent successfully
                $response = [
                    'status' => 'success',
                    'message' => 'Your quote/order has been submitted successfully!'
                ];
                
                // Clean up temporary files
                foreach ($uploadedFiles as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                
                // Clean up PDF file
                if ($pdfPath && file_exists($pdfPath)) {
                    unlink($pdfPath);

                }
            } else {

                // Email sending failed
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to send email. Please try again or contact support.'
                ];
                
                // Clean up files even on failure
                foreach ($uploadedFiles as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                
                // Clean up PDF file on failure too
                if (isset($pdfPath) && file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
                
                if ($pdfPath && file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }
        } catch (Exception $e) {

            $response = [
                'status' => 'error',
                'message' => 'Failed to send email. Please try again or contact customer support.'
            ];
        }
        // Email sending complete
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'An error occurred while processing your request. Please try again later.'
        ];
    }
}

// Clean any output buffer and return JSON response
ob_clean();
header('Content-Type: application/json');
echo json_encode($response);
?>
