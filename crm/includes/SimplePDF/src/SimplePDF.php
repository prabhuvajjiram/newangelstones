<?php
/**
 * Simple PDF text extractor
 * Custom implementation for Angel Stones CRM
 */

class SimplePDF {
    private $filename;
    private $content = '';

    public function __construct($filename) {
        $this->filename = $filename;
    }

    public function getText() {
        if (!file_exists($this->filename)) {
            throw new Exception('File not found: ' . $this->filename);
        }

        // Use pdftotext if available (more accurate)
        if ($this->isPdftotextAvailable()) {
            return $this->getPdftotextContent();
        }

        // Fallback to basic PHP parsing
        return $this->getBasicContent();
    }

    private function isPdftotextAvailable() {
        $output = [];
        $returnVar = -1;
        exec('pdftotext -v 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    private function getPdftotextContent() {
        $output = [];
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        exec('pdftotext "' . $this->filename . '" "' . $tempFile . '"', $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($tempFile)) {
            $content = file_get_contents($tempFile);
            unlink($tempFile);
            return $content;
        }
        
        throw new Exception('Failed to extract text using pdftotext');
    }

    private function getBasicContent() {
        // Basic PDF parsing using PHP
        $fp = fopen($this->filename, 'rb');
        $content = '';
        
        while (!feof($fp)) {
            $chunk = fread($fp, 4096);
            // Extract text content between stream markers
            if (preg_match('/stream(.*?)endstream/s', $chunk, $match)) {
                $text = $this->decodeContent($match[1]);
                $content .= $text;
            }
        }
        
        fclose($fp);
        return $content;
    }

    private function decodeContent($content) {
        // Remove non-printable characters except newlines
        $content = preg_replace('/[^A-Za-z0-9\s\.,\-:;\(\)\/]/', '', $content);
        // Convert multiple spaces to single space
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }

    public static function parse($filename) {
        $pdf = new self($filename);
        return $pdf->getText();
    }
}
