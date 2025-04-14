<?php
/**
 * Image Serving Proxy Script with Cache Control
 * 
 * This script serves images with cache control headers to prevent browser caching
 * Usage: serve_image.php?path=images/product/category/image.jpg
 */

// Disable caching headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Get image path parameter
$imagePath = isset($_GET['path']) ? $_GET['path'] : '';

// Validate the path (basic security check)
$imagePath = str_replace('../', '', $imagePath); // Prevent directory traversal

// Exit if no valid path provided
if (empty($imagePath)) {
    header("HTTP/1.0 400 Bad Request");
    echo "Error: Image path is required";
    exit;
}

// Check if the file exists
$fullPath = __DIR__ . '/' . $imagePath;
if (!file_exists($fullPath)) {
    header("HTTP/1.0 404 Not Found");
    echo "Error: Image not found";
    exit;
}

// Get file information
$info = getimagesize($fullPath);
$mime = $info['mime'] ?? 'application/octet-stream';

// Set content type header based on image mime type
header("Content-Type: {$mime}");

// Add a random query parameter to the image URL in the logs for debugging
error_log("Serving image: {$imagePath} with no-cache headers");

// Output the image file
readfile($fullPath);
exit;
?>
