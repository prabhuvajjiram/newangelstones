<?php
require_once(__DIR__ . '/../tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        // Get the current directory (crm folder)
        $root_dir = dirname(dirname(__FILE__));
        // Go up one level to find the images directory
        $parent_dir = dirname($root_dir);
        // Construct absolute path to image
        $image_file = $parent_dir . '/images/logo03.png';
        
        // Check if image exists and add it
        if (file_exists($image_file)) {
            // Center the image
            $pageWidth = $this->getPageWidth();
            $imageWidth = 50; // Width of the image in mm
            $imageHeight = $imageWidth * 0.65; // Maintain aspect ratio
            $x = ($pageWidth - $imageWidth) / 2;
            
            // Add black backdrop for the header
            $this->SetFillColor(34, 40, 49);
            $this->Rect(0, 0, $pageWidth, 45, 'F');
            
            // Center the image vertically within the header space
            $y = (45 - $imageHeight) / 2;
            // Add the image with specified width and height
            $this->Image($image_file, $x, $y, $imageWidth, $imageHeight);
        } else {
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