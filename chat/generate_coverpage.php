<?php
/**
 * Angel Granite Professional Fax Cover Page Generator
 * 
 * Generates a branded PDF cover page for faxes
 */

// Use TCPDF from CRM folder
require_once __DIR__ . '/../crm/tcpdf/tcpdf.php';

class AngelGraniteCoverPage extends TCPDF {
    
    /**
     * Generate a professional branded cover page
     * 
     * @param array $options Options for the cover page
     * @return string Path to generated PDF
     */
    public static function generate($options = []) {
        $defaults = [
            'to_name' => '',
            'to_company' => '',
            'to_fax' => '',
            'from_name' => 'Angel Granites',
            'from_phone' => '+1 (706) 262-7177',
            'from_fax' => '+1 (706) 262-7693',
            'from_email' => 'info@theangelstones.com',
            'message' => '',
            'pages' => 1,
            'date' => date('F j, Y'),
            'time' => date('g:i A'),
            'urgent' => false,
            'confidential' => false
        ];
        
        $data = array_merge($defaults, $options);
        
        // Create PDF
        $pdf = new self();
        $pdf->SetCreator('Angel Granites Fax System');
        $pdf->SetAuthor('Angel Granites');
        $pdf->SetTitle('Fax Cover Page');
        $pdf->SetSubject('Fax Transmission');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Add page
        $pdf->AddPage();
        
        // Build the cover page
        $pdf->buildCoverPage($data);
        
        // Save to file
        $filename = __DIR__ . '/temp_coverpage_' . uniqid() . '.pdf';
        $pdf->Output($filename, 'F');
        
        return $filename;
    }
    
    /**
     * Build the cover page content
     */
    private function buildCoverPage($data) {
        $y = 15;
        
        // === HEADER SECTION ===
        // Company name - Large and prominent
        $this->SetFont('helvetica', 'B', 28);
        $this->SetTextColor(0, 102, 153); // Professional blue
        $this->SetXY(15, $y);
        $this->Cell(0, 12, 'ANGEL GRANITES', 0, 1, 'C');
        $y += 12;
        
        // Tagline
        $this->SetFont('helvetica', 'I', 11);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(15, $y);
        $this->Cell(0, 6, 'Elevating Granite, Preserving Memories', 0, 1, 'C');
        $y += 8;
        
        // Venture of Angel Stones
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->SetXY(15, $y);
        $this->Cell(0, 5, 'A Venture of Angel Stones', 0, 1, 'C');
        $y += 10;
        
        // Separator line
        $this->SetDrawColor(0, 102, 153);
        $this->SetLineWidth(0.5);
        $this->Line(15, $y, 195, $y);
        $y += 8;
        
        // FAX COVER SHEET title
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(50, 50, 50);
        $this->SetXY(15, $y);
        $this->Cell(0, 8, 'FAX TRANSMISSION', 0, 1, 'C');
        $y += 15;
        
        // === FLAGS (Urgent/Confidential) ===
        if ($data['urgent'] || $data['confidential']) {
            $flagY = $y;
            $flagX = 15;
            
            if ($data['urgent']) {
                $this->SetFillColor(220, 53, 69); // Red
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 10);
                $this->SetXY($flagX, $flagY);
                $this->Cell(35, 7, 'URGENT', 1, 0, 'C', true);
                $flagX += 40;
            }
            
            if ($data['confidential']) {
                $this->SetFillColor(255, 193, 7); // Yellow
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('helvetica', 'B', 10);
                $this->SetXY($flagX, $flagY);
                $this->Cell(45, 7, 'CONFIDENTIAL', 1, 0, 'C', true);
            }
            
            $y += 15;
        }
        
        // === TRANSMISSION INFO BOX ===
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $boxY = $y;
        
        // Left column - TO information
        $colWidth = 85;
        $rowHeight = 8;
        
        $this->SetFillColor(240, 240, 240);
        $this->Rect(15, $boxY, $colWidth, $rowHeight, 'F');
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(15, $boxY);
        $this->Cell($colWidth, $rowHeight, 'TO:', 0, 0, 'L');
        $boxY += $rowHeight;
        
        // To Name
        if (!empty($data['to_name'])) {
            $this->SetFont('helvetica', '', 10);
            $this->SetXY(15, $boxY);
            $this->Cell(20, $rowHeight, 'Name:', 0, 0, 'L');
            $this->SetFont('helvetica', 'B', 10);
            $this->SetXY(35, $boxY);
            $this->Cell($colWidth - 20, $rowHeight, $data['to_name'], 0, 0, 'L');
            $boxY += $rowHeight;
        }
        
        // To Company
        if (!empty($data['to_company'])) {
            $this->SetFont('helvetica', '', 10);
            $this->SetXY(15, $boxY);
            $this->Cell(20, $rowHeight, 'Company:', 0, 0, 'L');
            $this->SetFont('helvetica', 'B', 10);
            $this->SetXY(35, $boxY);
            $this->Cell($colWidth - 20, $rowHeight, $data['to_company'], 0, 0, 'L');
            $boxY += $rowHeight;
        }
        
        // To Fax
        if (!empty($data['to_fax'])) {
            $this->SetFont('helvetica', '', 10);
            $this->SetXY(15, $boxY);
            $this->Cell(20, $rowHeight, 'Fax:', 0, 0, 'L');
            $this->SetFont('helvetica', 'B', 10);
            $this->SetXY(35, $boxY);
            $this->Cell($colWidth - 20, $rowHeight, $data['to_fax'], 0, 0, 'L');
            $boxY += $rowHeight;
        }
        
        // Right column - FROM information
        $boxY = $y;
        $rightColX = 110;
        
        $this->SetFillColor(240, 240, 240);
        $this->Rect($rightColX, $boxY, $colWidth, $rowHeight, 'F');
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($rightColX, $boxY);
        $this->Cell($colWidth, $rowHeight, 'FROM:', 0, 0, 'L');
        $boxY += $rowHeight;
        
        // From Name
        $this->SetFont('helvetica', '', 10);
        $this->SetXY($rightColX, $boxY);
        $this->Cell(20, $rowHeight, 'Name:', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($rightColX + 20, $boxY);
        $this->Cell($colWidth - 20, $rowHeight, $data['from_name'], 0, 0, 'L');
        $boxY += $rowHeight;
        
        // From Phone
        $this->SetFont('helvetica', '', 10);
        $this->SetXY($rightColX, $boxY);
        $this->Cell(20, $rowHeight, 'Phone:', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($rightColX + 20, $boxY);
        $this->Cell($colWidth - 20, $rowHeight, $data['from_phone'], 0, 0, 'L');
        $boxY += $rowHeight;
        
        // From Fax
        $this->SetFont('helvetica', '', 10);
        $this->SetXY($rightColX, $boxY);
        $this->Cell(20, $rowHeight, 'Fax:', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY($rightColX + 20, $boxY);
        $this->Cell($colWidth - 20, $rowHeight, $data['from_fax'], 0, 0, 'L');
        $boxY += $rowHeight;
        
        $y = $boxY + 5;
        
        // === DATE/TIME/PAGES ===
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(80, 80, 80);
        
        $infoY = $y;
        $this->SetXY(15, $infoY);
        $this->Cell(60, 6, 'Date: ' . $data['date'], 0, 0, 'L');
        $this->SetXY(85, $infoY);
        $this->Cell(60, 6, 'Time: ' . $data['time'], 0, 0, 'L');
        $this->SetXY(155, $infoY);
        $this->Cell(40, 6, 'Pages: ' . $data['pages'], 0, 0, 'L');
        
        $y += 15;
        
        // === MESSAGE SECTION ===
        if (!empty($data['message'])) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY(15, $y);
            $this->Cell(0, 7, 'Message:', 0, 1, 'L');
            $y += 8;
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(50, 50, 50);
            $this->SetXY(15, $y);
            $this->MultiCell(180, 6, $data['message'], 0, 'L');
            $y = $this->GetY() + 5;
        }
        
        // === COMPANY INFORMATION SECTION ===
        $y = 220; // Fixed position near bottom
        
        // Separator line
        $this->SetDrawColor(0, 102, 153);
        $this->SetLineWidth(0.5);
        $this->Line(15, $y, 195, $y);
        $y += 5;
        
        // Company info box with light blue background
        $this->SetFillColor(240, 248, 255); // Light blue
        $this->Rect(15, $y, 180, 40, 'F');
        
        $this->SetDrawColor(0, 102, 153);
        $this->SetLineWidth(0.3);
        $this->Rect(15, $y, 180, 40);
        
        $y += 5;
        
        // Company name again
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 102, 153);
        $this->SetXY(15, $y);
        $this->Cell(180, 6, 'Angel Granites LLC', 0, 1, 'C');
        $y += 7;
        
        // Tagline
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(80, 80, 80);
        $this->SetXY(15, $y);
        $this->Cell(180, 5, 'Premium Granite Monuments & Memorials', 0, 1, 'C');
        $y += 7;
        
        // Contact info - 3 columns
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(60, 60, 60);
        
        $colW = 60;
        
        // Column 1 - Phone & Fax
        $col1X = 15;
        $this->SetXY($col1X, $y);
        $this->Cell($colW, 4, 'Phone: +1 (706) 262-7177', 0, 1, 'C');
        $this->SetXY($col1X, $y + 4);
        $this->Cell($colW, 4, 'Toll Free: +1 (866) 682-5837', 0, 1, 'C');
        $this->SetXY($col1X, $y + 8);
        $this->Cell($colW, 4, 'Fax: +1 (706) 262-7693', 0, 1, 'C');
        
        // Column 2 - Email & Web
        $col2X = 75;
        $this->SetXY($col2X, $y);
        $this->Cell($colW, 4, 'info@theangelstones.com', 0, 1, 'C');
        $this->SetXY($col2X, $y + 4);
        $this->Cell($colW, 4, 'www.theangelstones.com', 0, 1, 'C');
        $this->SetXY($col2X, $y + 8);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(0, 102, 153);
        $this->Cell($colW, 4, 'Mobile App Available!', 0, 1, 'C');
        
        // Column 3 - Address
        $col3X = 135;
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(60, 60, 60);
        $this->SetXY($col3X, $y);
        $this->Cell($colW, 4, 'P.O. Box 370', 0, 1, 'C');
        $this->SetXY($col3X, $y + 4);
        $this->Cell($colW, 4, 'Elberton, GA 30635', 0, 1, 'C');
        $this->SetXY($col3X, $y + 8);
        $this->Cell($colW, 4, 'United States', 0, 1, 'C');
        
        $y += 14;
        
        // Marketing message
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(15, $y);
        $this->MultiCell(180, 3.5, 
            "Serving the monument industry with premium granite products since our establishment. " .
            "Visit our mobile app for instant quotes, browse 100+ granite colors, and explore our full catalog. " .
            "Quality craftsmanship | Nationwide shipping | Family-owned business", 
            0, 'C');
        
        // Disclaimer at very bottom
        $this->SetFont('helvetica', '', 6);
        $this->SetTextColor(150, 150, 150);
        $this->SetXY(15, 280);
        $this->Cell(180, 3, 'CONFIDENTIALITY NOTICE: This fax transmission may contain privileged information. If you are not the intended recipient, please notify sender immediately.', 0, 1, 'C');
    }
}

// If called directly, generate a sample
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $coverPath = AngelGraniteCoverPage::generate([
        'to_name' => 'John Smith',
        'to_company' => 'ABC Memorial Services',
        'to_fax' => '+1 (555) 123-4567',
        'message' => 'Please find attached the quote for the memorial monument as discussed. This includes pricing for Imperial Red granite with custom engraving. Please review and contact us with any questions.',
        'pages' => 3,
        'urgent' => false,
        'confidential' => true
    ]);
    
    echo "Cover page generated: $coverPath\n";
    echo "File size: " . filesize($coverPath) . " bytes\n";
}
