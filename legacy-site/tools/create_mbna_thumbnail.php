<?php
// Configuration
$sourceDir = 'images/products/MBNA_2025';
$thumbnailName = 'mbna_thumbnail.jpg';
$fullPath = __DIR__ . '/' . $sourceDir . '/' . $thumbnailName;

// Check if thumbnail already exists
if (file_exists($fullPath)) {
    echo "Thumbnail already exists at: {$fullPath}\n";
    exit(0);
}

// Get the first PNG file from the directory
$files = glob($sourceDir . '/*.png');

if (empty($files)) {
    echo "No PNG files found in the directory: {$sourceDir}\n";
    exit(1);
}

// Sort files naturally
natcasesort($files);
$files = array_values($files);

// Use the first image as source
$sourceFile = $files[0];
echo "Using source file: {$sourceFile}\n";

// Create thumbnail
$source = imagecreatefrompng($sourceFile);
if (!$source) {
    echo "Failed to create image from source: {$sourceFile}\n";
    exit(1);
}

// Get source dimensions
$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

// Define thumbnail dimensions
$thumbWidth = 300;
$thumbHeight = 300;

// Create thumbnail image
$thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

// Fill with black background
$black = imagecolorallocate($thumbnail, 0, 0, 0);
imagefill($thumbnail, 0, 0, $black);

// Copy and resize source to thumbnail
imagecopyresampled(
    $thumbnail,
    $source,
    0, 0, 0, 0,
    $thumbWidth, $thumbHeight,
    $sourceWidth, $sourceHeight
);

// Add caption "MBNA 2025"
$white = imagecolorallocate($thumbnail, 255, 255, 255);
$fontSize = 5;
$fontFile = 5; // Using built-in font (size 5)

// Add caption at the bottom
$text = "MBNA 2025";
$textWidth = imagefontwidth($fontFile) * strlen($text);
$textHeight = imagefontheight($fontFile);
$x = ($thumbWidth - $textWidth) / 2;
$y = $thumbHeight - $textHeight - 10;

// Add semi-transparent background for text
$semi = imagecolorallocatealpha($thumbnail, 0, 0, 0, 75);
imagefilledrectangle(
    $thumbnail,
    0, $y - 5,
    $thumbWidth, $thumbHeight,
    $semi
);

// Add text
imagestring($thumbnail, $fontFile, $x, $y, $text, $white);

// Save as JPEG
if (imagejpeg($thumbnail, $fullPath, 90)) {
    echo "Thumbnail created successfully at: {$fullPath}\n";
} else {
    echo "Failed to save thumbnail to: {$fullPath}\n";
}

// Clean up
imagedestroy($source);
imagedestroy($thumbnail);

echo "Process completed.\n";
