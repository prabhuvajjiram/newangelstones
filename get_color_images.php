<?php
/**
 * Get Color Images - Dynamically fetches color images from the images/colors directory
 * For Angel Stones website
 */

// Set content type to JSON
header('Content-Type: application/json');

// Directory path
$colorsDir = __DIR__ . '/images/colors/';

// Check if directory exists
if (!is_dir($colorsDir)) {
    echo json_encode(['error' => 'Colors directory not found', 'colors' => []]);
    exit;
}

// Get all image files (jpg, jpeg, png)
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$colors = [];

// Read directory
$files = scandir($colorsDir);

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
        
        // Add to colors array
        $colors[] = [
            'name' => $name,
            'path' => 'images/colors/' . $file
        ];
    }
}

// Sort colors alphabetically by name
usort($colors, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Return JSON response
echo json_encode(['colors' => $colors]);
?>
