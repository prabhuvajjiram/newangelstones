<?php
require_once(__DIR__ . '/../tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        // Add black backdrop for the header
        $this->SetFillColor(34, 40, 49);
        $this->Rect(0, 0, $this->GetPageWidth(), 45, 'F');
        
        // Add company name as text instead of image to avoid PNG alpha issues
        $this->SetTextColor(255, 255, 255); // White text
        $this->SetFont('helvetica', 'B', 20);
        
        // Center the company name
        $pageWidth = $this->getPageWidth();
        $this->SetXY(0, 15);
        $this->Cell($pageWidth, 15, 'ANGEL STONES', 0, 1, 'C');
        
        // Reset text color to black for content
        $this->SetTextColor(0, 0, 0);
        
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