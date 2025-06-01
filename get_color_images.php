<?php
/**
 * Get Color Images - Dynamically fetches color images from the images/colors directory
 * For Angel Stones website
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Base directory for color images
$colorsDir = __DIR__ . '/images/colors/';

// Check if directory exists
if (!is_dir($colorsDir)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Colors directory not found at: ' . $colorsDir,
        'current_dir' => __DIR__
    ], JSON_PRETTY_PRINT);
    exit;
}

// Log directory being scanned
error_log("Scanning directory: " . $colorsDir);

// Get all image files
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$colors = [];

// Read directory
$files = scandir($colorsDir);

// Log files found
error_log("Files in directory: " . print_r($files, true));

foreach ($files as $file) {
    // Skip hidden files and directories
    if ($file[0] === '.') {
        continue;
    }
    
    // Get file info
    $filePath = $colorsDir . $file;
    $fileInfo = pathinfo($filePath);
    $ext = strtolower($fileInfo['extension'] ?? '');
    
    // Check if it's an allowed image type and a file (not directory)
    if (is_file($filePath) && in_array($ext, $allowedExtensions)) {
        // Get name from filename by replacing hyphens/underscores with spaces
        $name = $fileInfo['filename'];
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        
        // Create relative path for web access
        $relativePath = 'images/colors/' . $file;
        $webPath = $relativePath;
        
        // Get file modification time
        $fileTime = @filemtime($filePath);
        if ($fileTime === false) {
            $fileTime = time(); // Fallback to current time if can't get mtime
        }
        
        // For debugging
        error_log("Image added - Name: $name, Path: $webPath, Size: " . filesize($filePath) . " bytes");
        
        // Check if file is readable
        if (!is_readable($filePath)) {
            error_log("Warning: Cannot read file: $filePath");
            continue;
        }
        
        // Add to colors array with additional metadata
        $colors[] = [
            'name' => $name,
            'path' => $webPath,
            'filename' => $file,
            'size' => filesize($filePath),
            'modified' => $fileTime,
            'type' => mime_content_type($filePath)
        ];
    }
}

// Sort colors alphabetically by name
usort($colors, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Return JSON response
$response = [
    'success' => true,
    'count' => count($colors),
    'directory' => 'images/colors/',
    'colors' => $colors,
    'timestamp' => time(),
    'debug' => [
        'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Not available',
        'script_dir' => __DIR__,
        'colors_dir' => $colorsDir,
        'files_found' => count($files) - 2, // subtract . and ..
        'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'CLI'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
