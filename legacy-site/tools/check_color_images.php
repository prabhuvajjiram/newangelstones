<?php
/**
 * Check Color Images - Diagnostic script to verify color images exist
 * For Angel Stones website
 */

// Directory path
$colorsDir = __DIR__ . '/images/colors/';

echo "<html><head><title>Color Images Check</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }";
echo ".image-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }";
echo ".image-item { border: 1px solid #ddd; padding: 10px; text-align: center; }";
echo ".image-item img { max-width: 100%; height: auto; }";
echo ".error { color: red; font-weight: bold; }";
echo ".success { color: green; font-weight: bold; }</style></head><body>";

echo "<h1>Color Images Diagnostic</h1>";

// Check if directory exists
if (!is_dir($colorsDir)) {
    echo "<p class='error'>Colors directory not found at: " . htmlspecialchars($colorsDir) . "</p>";
    echo "<p>Please create this directory and add color images to it.</p>";
    echo "</body></html>";
    exit;
}

// Get all image files (jpg, jpeg, png)
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$colors = [];

// Read directory
$files = scandir($colorsDir);
$imageCount = 0;

echo "<h2>Directory Contents:</h2>";
echo "<p>Directory: " . htmlspecialchars($colorsDir) . "</p>";

echo "<div class='image-list'>";
foreach ($files as $file) {
    // Skip hidden files and directories
    if ($file[0] === '.') {
        continue;
    }
    
    // Get file extension
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Check if it's an allowed image type
    if (in_array($ext, $allowedExtensions)) {
        $imageCount++;
        
        // Get name from filename by replacing hyphens with spaces and removing extension
        $name = pathinfo($file, PATHINFO_FILENAME);
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        
        echo "<div class='image-item'>";
        echo "<img src='images/colors/" . htmlspecialchars($file) . "' alt='" . htmlspecialchars($name) . "'>";
        echo "<p>" . htmlspecialchars($name) . "</p>";
        echo "</div>";
    }
}
echo "</div>";

if ($imageCount > 0) {
    echo "<p class='success'>Found " . $imageCount . " color images.</p>";
} else {
    echo "<p class='error'>No color images found in the directory.</p>";
    echo "<p>Please add .jpg, .jpeg, .png, or .webp files to the images/colors/ directory.</p>";
}

echo "</body></html>";
?>
