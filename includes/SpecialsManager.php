<?php
/**
 * SpecialsManager Class
 * Manages PDF special offers for the Angel Stones CRM
 */
class SpecialsManager {
    private $pdfDirectory;
    private $thumbnailDirectory;
    
    /**
     * Constructor - initialize directories
     */
    public function __construct() {
        $this->pdfDirectory = dirname(__DIR__) . '/images/specials/pdfs/';
        $this->thumbnailDirectory = dirname(__DIR__) . '/images/specials/thumbnails/';
    }
    
    /**
     * Get all available specials
     * @return array List of specials with metadata
     */
    public function getAllSpecials() {
        $specials = [];
        
        // Check if directory exists
        if (!is_dir($this->pdfDirectory)) {
            return $specials;
        }
        
        // Get all PDF files
        $pdfFiles = glob($this->pdfDirectory . '*.pdf');
        
        foreach ($pdfFiles as $pdfFile) {
            $specials[] = $this->getSpecialMetadata($pdfFile);
        }
        
        return $specials;
    }
    
    /**
     * Get a specific special by ID
     * @param string $id The ID of the special
     * @return array|null Special metadata or null if not found
     */
    public function getSpecialById($id) {
        $filePath = $this->pdfDirectory . $id . '.pdf';
        if (file_exists($filePath)) {
            return $this->getSpecialMetadata($filePath);
        }
        return null;
    }
    
    /**
     * Get metadata for a PDF file
     * @param string $pdfFile Full path to the PDF file
     * @return array Metadata for the PDF
     */
    private function getSpecialMetadata($pdfFile) {
        $filename = basename($pdfFile);
        $id = pathinfo($filename, PATHINFO_FILENAME);
        $filesize = filesize($pdfFile);
        $modified = filemtime($pdfFile);
        
        // Format file size
        $formattedSize = $this->formatFileSize($filesize);
        
        // Find matching thumbnail (check for different extensions and naming patterns)
        $thumbnailUrl = $this->findMatchingThumbnail($id);
        
        return [
            'id' => $id,
            'filename' => $filename,
            'title' => $this->formatTitle($id),
            'url' => '/images/specials/pdfs/' . $filename,
            'thumbnail' => $thumbnailUrl,
            'hasThumbnail' => !empty($thumbnailUrl),
            'size' => $formattedSize,
            'modified' => date('Y-m-d', $modified),
            'order' => $this->getDisplayOrder($id)
        ];
    }
    
    /**
     * Find a matching thumbnail for a PDF using various naming patterns
     * 
     * @param string $pdfId The PDF ID (filename without extension)
     * @return string URL to the thumbnail or default image if not found
     */
    private function findMatchingThumbnail($pdfId) {
        // List of possible extensions to check
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        // Check for exact match first
        foreach ($extensions as $ext) {
            if (file_exists($this->thumbnailDirectory . $pdfId . '.' . $ext)) {
                return '/images/specials/thumbnails/' . $pdfId . '.' . $ext;
            }
        }
        
        // Check for thumbnails with pattern: Blue_Special_Flyer → Flyer-1.webp
        if (strpos($pdfId, 'Blue_Special') !== false) {
            if (file_exists($this->thumbnailDirectory . 'Flyer-1.webp')) {
                return '/images/specials/thumbnails/Flyer-1.webp';
            }
        }
        
        if (strpos($pdfId, 'Green_Special') !== false) {
            if (file_exists($this->thumbnailDirectory . 'Flyer-2.webp')) {
                return '/images/specials/thumbnails/Flyer-2.webp';
            }
        }
        
        if (strpos($pdfId, 'Convention_special') !== false) {
            if (file_exists($this->thumbnailDirectory . 'Flyer-3.webp')) {
                return '/images/specials/thumbnails/Flyer-3.webp';
            }
        }
        
        // Generic pattern check - look for any PDF flyer number in name
        if (preg_match('/flyer[_-]?(\d+)/i', $pdfId, $matches)) {
            $flyer_num = $matches[1];
            if (file_exists($this->thumbnailDirectory . 'Flyer-' . $flyer_num . '.webp')) {
                return '/images/specials/thumbnails/Flyer-' . $flyer_num . '.webp';
            }
        }
        
        // Get all files in the thumbnail directory as fallback
        $thumbnails = glob($this->thumbnailDirectory . '*');
        if (!empty($thumbnails)) {
            // Just return the first available thumbnail if we can't find a match
            $thumbnailFile = basename($thumbnails[0]);
            return '/images/specials/thumbnails/' . $thumbnailFile;
        }
        
        // Default thumbnail if nothing found
        return '/images/default-thumbnail.jpg';
    }
    
    /**
     * Format file size to human-readable format
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 1) . ' ' . $units[$i];
    }
    
    /**
     * Format title from filename
     * @param string $filename Filename without extension
     * @return string Formatted title
     */
    private function formatTitle($filename) {
        // Replace underscores and hyphens with spaces
        $title = str_replace(['_', '-'], ' ', $filename);
        // Capitalize words
        return ucwords($title);
    }
    
    /**
     * Get display order for a special
     * @param string $id Special ID
     * @return int Display order (lower numbers appear first)
     */
    private function getDisplayOrder($id) {
        // For now, use alphabetical order
        // In the future, this could be stored in a database or config file
        return 0;
    }
}
