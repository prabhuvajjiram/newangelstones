<?php
/**
 * Get Color Images - Dynamically fetches color images from the images/colors directory
 * For Angel Stones website - Performance Optimized Version
 */

// Performance monitoring
$start_time = microtime(true);
$start_memory = memory_get_usage();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON with caching headers for performance
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Add caching headers for better performance (cache for 15 minutes)
$cache_time = 15 * 60; // 15 minutes
header('Cache-Control: public, max-age=' . $cache_time);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');

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

// Calculate performance metrics
$end_time = microtime(true);
$end_memory = memory_get_usage();
$execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
$memory_used = round(($end_memory - $start_memory) / 1024, 2); // Convert to KB
$peak_memory = round(memory_get_peak_usage() / 1024, 2); // Convert to KB

// Return JSON response with performance metrics
$response = [
    'success' => true,
    'count' => count($colors),
    'directory' => 'images/colors/',
    'colors' => $colors,
    'timestamp' => time(),
    'performance' => [
        'execution_time_ms' => $execution_time,
        'memory_used_kb' => $memory_used,
        'peak_memory_kb' => $peak_memory,
        'files_processed' => count($files) - 2, // subtract . and ..
        'colors_found' => count($colors)
    ],
    'debug' => [
        'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Not available',
        'script_dir' => __DIR__,
        'colors_dir' => $colorsDir,
        'files_found' => count($files) - 2, // subtract . and ..
        'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'CLI',
        'php_version' => PHP_VERSION,
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit')
    ]
];

// Log performance for monitoring
error_log("Color API Performance - Execution: {$execution_time}ms, Memory: {$memory_used}KB, Colors: " . count($colors));

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
