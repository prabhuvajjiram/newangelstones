<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$sourceDir = 'images/products/MBNA_2025';
$thumbDir = $sourceDir . '/thumbnails';
$targetWidth = 600;  // Increased from 300
$targetHeight = 800; // Increased from 400

// Create thumbnails directory if it doesn't exist
if (!file_exists($thumbDir)) {
    mkdir($thumbDir, 0777, true);
}

// Get all PNG files from source directory
$files = glob($sourceDir . '/*.png');

foreach ($files as $file) {
    $filename = basename($file);
    $thumbPath = $thumbDir . '/' . $filename;
    
    // Skip if thumbnail exists and is newer than source
    if (file_exists($thumbPath) && filemtime($thumbPath) > filemtime($file)) {
        echo "Skipping $filename - thumbnail is up to date<br>";
        continue;
    }
    
    // Load the source image
    $sourceImage = imagecreatefrompng($file);
    if (!$sourceImage) {
        echo "Error loading $filename<br>";
        continue;
    }
    
    // Get original dimensions
    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);
    
    // Calculate aspect ratios
    $srcAspect = $srcWidth / $srcHeight;
    $targetAspect = $targetWidth / $targetHeight;
    
    // Calculate new dimensions maintaining aspect ratio
    if ($srcAspect > $targetAspect) {
        // Image is wider than target
        $newWidth = $targetHeight * $srcAspect;
        $newHeight = $targetHeight;
    } else {
        // Image is taller than target
        $newWidth = $targetWidth;
        $newHeight = $targetWidth / $srcAspect;
    }
    
    // Create new image with dark background
    $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
    
    // Set background color (dark gray)
    $bgColor = imagecolorallocate($thumb, 38, 38, 38);
    imagefill($thumb, 0, 0, $bgColor);
    
    // Enable alpha channel
    imagealphablending($thumb, true);
    imagesavealpha($thumb, true);
    
    // Calculate centering position
    $destX = ($targetWidth - $newWidth) / 2;
    $destY = ($targetHeight - $newHeight) / 2;
    
    // Resize and copy the image with high quality
    imagecopyresampled(
        $thumb,
        $sourceImage,
        $destX, $destY,
        0, 0,
        $newWidth, $newHeight,
        $srcWidth, $srcHeight
    );
    
    // Save the thumbnail with high quality
    imagepng($thumb, $thumbPath, 1); // Lower compression for better quality
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($thumb);
    
    echo "Created thumbnail for $filename<br>";
}

echo "Thumbnail generation complete!";
?>
