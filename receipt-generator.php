<?php
// Clear any existing output
if (ob_get_level()) ob_end_clean();

// Try to find and load TCPDF
$tcpdfPaths = [
    __DIR__ . '/crm/tcpdf/tcpdf.php',
    'crm/tcpdf/tcpdf.php',
    dirname(__DIR__) . '/crm/tcpdf/tcpdf.php',
    dirname(__FILE__) . '/crm/tcpdf/tcpdf.php',
    realpath(__DIR__ . '/crm/tcpdf/tcpdf.php'),
    realpath('crm/tcpdf/tcpdf.php')
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
class ReceiptPDF extends TCPDF {
    public function Header() {
        // Try different paths for the logo
        $logoOptions = [
            dirname(__FILE__) . '/images/Angel Granites Logo_350dpi.png',
            dirname(__FILE__) . '/images/Angel Granites Logo_300dpi.png',
            dirname(__FILE__) . '/images/logo.png',
            dirname(__FILE__) . '/crm/images/logo.png',
            dirname(__FILE__) . '/crm/images/angelstones-logo.png'
        ];
        
        $logoFound = false;
        foreach ($logoOptions as $logoPath) {
            if (file_exists($logoPath)) {
                // Center the image
                $pageWidth = $this->getPageWidth();
                $imageWidth = 50; // Width of the image in mm
                $imageHeight = 25; // Height in mm
                $x = ($pageWidth - $imageWidth) / 2;
                
                // Add the image
                $this->Image($logoPath, $x, 10, $imageWidth, $imageHeight);
                $logoFound = true;
                break;
            }
        }
        
        // Move position below the header
        $this->SetY(40);
    }
    
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-25);
        $this->SetFillColor(34, 35, 38); // Dark background
        $this->SetTextColor(214, 183, 114); // Gold text
        $this->Cell(0, 20, '', 0, 1, 'C', true);
        $this->SetY(-20);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 5, 'Angel Granites', 0, 1, 'C');
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
    // Get parameters from URL and POST data (Converge may use either)
    // First check GET parameters
    $invoice = isset($_GET['invoice']) ? sanitizeInput($_GET['invoice']) : '';
    $amount = isset($_GET['amount']) ? sanitizeInput($_GET['amount']) : '';
    $name = isset($_GET['name']) ? sanitizeInput($_GET['name']) : '';
    $email = isset($_GET['email']) ? sanitizeInput($_GET['email']) : '';
    $phone = isset($_GET['phone']) ? sanitizeInput($_GET['phone']) : '';
    $address = isset($_GET['address']) ? sanitizeInput($_GET['address']) : '';
    $txnId = isset($_GET['txnid']) ? sanitizeInput($_GET['txnid']) : '';
    $approvalCode = isset($_GET['approval']) ? sanitizeInput($_GET['approval']) : '';
    
    // Check for alternate field names that Converge might use
    if (empty($invoice)) {
        $invoice = isset($_GET['ssl_invoice_number']) ? sanitizeInput($_GET['ssl_invoice_number']) : '';
    }
    if (empty($amount)) {
        $amount = isset($_GET['ssl_amount']) ? sanitizeInput($_GET['ssl_amount']) : '';
    }
    if (empty($name)) {
        $name = isset($_GET['ssl_card_holder']) ? sanitizeInput($_GET['ssl_card_holder']) : '';
        if (empty($name)) {
            $firstName = isset($_GET['ssl_first_name']) ? sanitizeInput($_GET['ssl_first_name']) : '';
            $lastName = isset($_GET['ssl_last_name']) ? sanitizeInput($_GET['ssl_last_name']) : '';
            if (!empty($firstName) || !empty($lastName)) {
                $name = trim($firstName . ' ' . $lastName);
            }
        }
    }
    if (empty($email)) {
        $email = isset($_GET['ssl_email']) ? sanitizeInput($_GET['ssl_email']) : '';
    }
    if (empty($phone)) {
        $phone = isset($_GET['ssl_phone']) ? sanitizeInput($_GET['ssl_phone']) : '';
    }
    if (empty($txnId)) {
        $txnId = isset($_GET['ssl_txn_id']) ? sanitizeInput($_GET['ssl_txn_id']) : '';
    }
    if (empty($approvalCode)) {
        $approvalCode = isset($_GET['ssl_approval_code']) ? sanitizeInput($_GET['ssl_approval_code']) : '';
    }
    
    // Check POST data as fallback
    if (empty($invoice)) {
        $invoice = isset($_POST['invoice']) ? sanitizeInput($_POST['invoice']) : '';
        if (empty($invoice)) {
            $invoice = isset($_POST['ssl_invoice_number']) ? sanitizeInput($_POST['ssl_invoice_number']) : '';
        }
    }
    if (empty($amount)) {
        $amount = isset($_POST['amount']) ? sanitizeInput($_POST['amount']) : '';
        if (empty($amount)) {
            $amount = isset($_POST['ssl_amount']) ? sanitizeInput($_POST['ssl_amount']) : '';
        }
    }
    if (empty($name)) {
        $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
        if (empty($name)) {
            $name = isset($_POST['ssl_card_holder']) ? sanitizeInput($_POST['ssl_card_holder']) : '';
            if (empty($name)) {
                $firstName = isset($_POST['ssl_first_name']) ? sanitizeInput($_POST['ssl_first_name']) : '';
                $lastName = isset($_POST['ssl_last_name']) ? sanitizeInput($_POST['ssl_last_name']) : '';
                if (!empty($firstName) || !empty($lastName)) {
                    $name = trim($firstName . ' ' . $lastName);
                }
            }
        }
    }
    if (empty($email)) {
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        if (empty($email)) {
            $email = isset($_POST['ssl_email']) ? sanitizeInput($_POST['ssl_email']) : '';
        }
    }
    if (empty($phone)) {
        $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
        if (empty($phone)) {
            $phone = isset($_POST['ssl_phone']) ? sanitizeInput($_POST['ssl_phone']) : '';
        }
    }
    if (empty($address)) {
        $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
        if (empty($address)) {
            // Try to build address from components
            $street = isset($_POST['ssl_avs_address']) ? sanitizeInput($_POST['ssl_avs_address']) : '';
            $city = isset($_POST['ssl_city']) ? sanitizeInput($_POST['ssl_city']) : '';
            $state = isset($_POST['ssl_state']) ? sanitizeInput($_POST['ssl_state']) : '';
            $zip = isset($_POST['ssl_avs_zip']) ? sanitizeInput($_POST['ssl_avs_zip']) : '';
            
            $addressParts = [];
            if (!empty($street)) $addressParts[] = $street;
            if (!empty($city)) $addressParts[] = $city;
            if (!empty($state)) $addressParts[] = $state;
            if (!empty($zip)) $addressParts[] = $zip;
            
            if (!empty($addressParts)) {
                $address = implode(', ', $addressParts);
            }
        }
    }
    if (empty($txnId)) {
        $txnId = isset($_POST['txnid']) ? sanitizeInput($_POST['txnid']) : '';
        if (empty($txnId)) {
            $txnId = isset($_POST['ssl_txn_id']) ? sanitizeInput($_POST['ssl_txn_id']) : '';
        }
    }
    if (empty($approvalCode)) {
        $approvalCode = isset($_POST['approval']) ? sanitizeInput($_POST['approval']) : '';
        if (empty($approvalCode)) {
            $approvalCode = isset($_POST['ssl_approval_code']) ? sanitizeInput($_POST['ssl_approval_code']) : '';
        }
    }
    
    // Generate a default invoice number if none provided
    if (empty($invoice)) {
        $invoice = 'AG-' . date('Ymd') . '-' . substr(uniqid(), -5);
    }
    
    // Format the date
    $date = date("F j, Y, g:i a");
    
    // Log parameters to a file for debugging
    $logFile = fopen(__DIR__ . '/receipt_debug.txt', 'a');
    fwrite($logFile, date('Y-m-d H:i:s') . " - Receipt Request\n");
    fwrite($logFile, "Invoice: $invoice\n");
    fwrite($logFile, "Amount: $amount\n");
    fwrite($logFile, "Name: $name\n");
    fwrite($logFile, "Email: $email\n");
    fwrite($logFile, "Phone: $phone\n");
    fwrite($logFile, "Address: $address\n");
    fwrite($logFile, "Transaction ID: $txnId\n");
    fwrite($logFile, "Approval Code: $approvalCode\n\n");
    fclose($logFile);
    
    // Check if we have required data
    if (empty($amount)) {
        throw new Exception('Missing required information (payment amount)');
    }
    
    // Create PDF document
    $pdf = new ReceiptPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Angel Granites');
    $pdf->SetAuthor('Angel Granites');
    $pdf->SetTitle('Payment Receipt');
    $pdf->SetSubject('Payment Receipt for ' . $invoice);
    
    // Set margins
    $pdf->SetMargins(15, 45, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(25);
    $pdf->SetAutoPageBreak(true, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font for the content
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(214, 183, 114); // Gold color
    $pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
    
    // Receipt Number and Date
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(90, 10, 'Receipt #' . $invoice, 0, 0, 'L');
    $pdf->Cell(90, 10, 'Date: ' . $date, 0, 1, 'R');
    
    // Receipt Details Section
    $pdf->Ln(5);
    $pdf->SetFillColor(34, 35, 38); // Dark background
    $pdf->SetTextColor(214, 183, 114); // Gold text
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'L', true);
    
    // Payment details
    $pdf->SetTextColor(51, 51, 51);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(5);
    
    // Function to add a detail row
    function addDetailRow($pdf, $label, $value) {
        $pdf->Cell(40, 7, $label, 0, 0, 'L');
        $pdf->Cell(140, 7, $value, 0, 1, 'L');
    }
    
    // Add all details with validation
    addDetailRow($pdf, 'Amount Paid:', '$' . validateField($amount));
    addDetailRow($pdf, 'Customer Name:', validateField($name));
    
    // Only add optional fields if they have values
    if (!empty($email)) {
        addDetailRow($pdf, 'Email:', $email);
    }
    
    if (!empty($phone)) {
        addDetailRow($pdf, 'Phone:', $phone);
    }
    
    if (!empty($address)) {
        addDetailRow($pdf, 'Address:', $address);
    }
    
    if (!empty($txnId)) {
        addDetailRow($pdf, 'Transaction ID:', $txnId);
    }
    
    if (!empty($approvalCode)) {
        addDetailRow($pdf, 'Approval Code:', $approvalCode);
    }
    
    addDetailRow($pdf, 'Payment Method:', 'Credit Card (Converge)');
    addDetailRow($pdf, 'Status:', 'Completed');
    
    // Thank you message
    $pdf->Ln(10);
    $pdf->SetTextColor(214, 183, 114); // Gold color
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Thank You For Your Business!', 0, 1, 'C');
    
    // Legal text
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->MultiCell(0, 5, 'This receipt is evidence of payment for the specified invoice. Please retain this document for your records. For any questions regarding this payment, please contact our customer service.', 0, 'L');
    
    // Output the PDF
    $pdf->Output('Angel_Granites_Receipt_' . $invoice . '.pdf', 'I');
    
} catch (Exception $e) {
    // Log error
    $errorLog = fopen(__DIR__ . '/receipt_error.txt', 'a');
    fwrite($errorLog, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n");
    fwrite($errorLog, "Invoice: $invoice, Amount: $amount\n\n");
    fclose($errorLog);
    
    // Clear output buffer
    if (ob_get_level()) ob_end_clean();
    
    // Show error message
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error Generating PDF</h3>";
    echo "<p>An error occurred while generating your receipt:</p>";
    echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p>Please contact support with the following information:</p>";
    echo "<ul>";
    echo "<li>Invoice: " . htmlspecialchars($invoice) . "</li>";
    echo "<li>Amount: " . htmlspecialchars($amount) . "</li>";
    echo "<li>Time: " . $date . "</li>";
    echo "</ul>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    echo "</div>";
}
?>
