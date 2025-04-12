<?php
/**
 * Get Color Images - Dynamically fetches color images from the images/colors directory
 * For Angel Stones website
 */

// Set content type to JSON
header('Content-Type: application/json');

// Directory path - updated to ensure it finds the correct path
$colorsDir = __DIR__ . '/images/webp/colors/';

// Check if directory exists
if (!is_dir($colorsDir)) {
    // Try alternative path
    $colorsDir = __DIR__ . '/images/colors/';
    
    // If still not found, return error
    if (!is_dir($colorsDir)) {
        echo json_encode(['error' => 'Colors directory not found', 'path_checked' => $colorsDir, 'colors' => []]);
        exit;
    }
}

// Get all image files
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$colors = [];

// Read directory
$files = scandir($colorsDir);

// Debug output to console
// error_log("Files in directory: " . print_r($files, true));

foreach ($files as $file) {
    // Skip hidden files and directories
    if ($file[0] === '.') {
        continue;
    }
    
    // Get file extension
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Check if it's an allowed image type
    if (in_array($ext, $allowedExtensions)) {
        // Get name from filename by replacing hyphens with spaces and removing extension
        $name = pathinfo($file, PATHINFO_FILENAME);
        $name = str_replace('-', ' ', $name);
        // Properly format the name (capitalize first letter of each word)
        $name = ucwords($name);
        
        // Determine the correct path
        $path = 'images/webp/colors/' . $file;
        
        // Check if file exists at that path, if not use fallback
        if (!file_exists(__DIR__ . '/images/webp/colors/' . $file)) {
            $path = 'images/colors/' . $file;
        }
        
        // Add to colors array
        $colors[] = [
            'name' => $name,
            'path' => $path
        ];
    }
}

// Sort colors alphabetically by name
usort($colors, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Return JSON - IMPORTANT: Wrap colors in an object as expected by the JS
echo json_encode(['colors' => $colors]);
?>
