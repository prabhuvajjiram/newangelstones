<?php
require_once 'includes/config.php';
require_once 'vendor/autoload.php'; // Make sure you have TCPDF installed via composer

use TCPDF;

// Get JSON data from POST request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Angel Stones');
$pdf->SetAuthor('Angel Stones');
$pdf->SetTitle('Quote - ' . $data['customer']['name']);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Company Header
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'Angel Stones', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Quote', 0, 1, 'C');
$pdf->Ln(10);

// Date
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Date: ' . $data['date'], 0, 1, 'R');
$pdf->Ln(5);

// Customer Details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Customer Details:', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(30, 7, 'Name:', 0);
$pdf->Cell(0, 7, $data['customer']['name'], 0, 1);
$pdf->Cell(30, 7, 'Project:', 0);
$pdf->Cell(0, 7, $data['customer']['project'], 0, 1);
$pdf->Ln(10);

// Products
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Products:', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Products Table Header
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(30, 7, 'Type', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Size', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Model', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Color', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Dimensions', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Qty', 1, 1, 'C', true);

// Products Table Content
foreach ($data['items'] as $item) {
    $sizeDisplay = $item['type'] === 'marker' ? $item['size'] . ' sq.ft' : 
                  ($item['type'] === 'slant' ? 'N/A' : $item['size'] . ' inch');
    $dimensions = $item['length'] . '" × ' . $item['breadth'] . '"';
    
    $pdf->Cell(30, 7, strtoupper($item['type']), 1);
    $pdf->Cell(25, 7, $sizeDisplay, 1);
    $pdf->Cell(25, 7, $item['model'], 1);
    $pdf->Cell(30, 7, $item['color'], 1);
    $pdf->Cell(30, 7, $dimensions, 1);
    $pdf->Cell(20, 7, $item['quantity'], 1, 1, 'C');
}

$pdf->Ln(10);

// Pricing
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Pricing:', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(40, 7, 'Subtotal:', 0);
$pdf->Cell(0, 7, $data['pricing']['subtotal'], 0, 1);

$pdf->Cell(40, 7, 'Commission:', 0);
$pdf->Cell(0, 7, $data['pricing']['commission'], 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(40, 7, 'Total:', 0);
$pdf->Cell(0, 7, $data['pricing']['total'], 0, 1);

// Terms and Conditions
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Terms and Conditions:', 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, '1. This quote is valid for 30 days from the date of issue.
2. 50% advance payment is required to confirm the order.
3. Delivery time will be confirmed after order confirmation.
4. Prices are subject to change without prior notice.
5. All disputes are subject to local jurisdiction.');

// Generate PDF file
$pdfFileName = 'quote_' . date('Y-m-d_His') . '.pdf';
$pdfFilePath = '../quotes/' . $pdfFileName;

// Create quotes directory if it doesn't exist
if (!file_exists('../quotes')) {
    mkdir('../quotes', 0777, true);
}

// Save PDF
$pdf->Output($pdfFilePath, 'F');

// Return success response with PDF URL
echo json_encode([
    'success' => true,
    'pdfUrl' => '../quotes/' . $pdfFileName
]);
