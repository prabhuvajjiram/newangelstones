<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get all files from a directory
 * 
 * @param string $directory Directory path to scan
 * @param string $extension Optional file extension to filter by
 * @return array Array of filenames
 */
function getDirectoryFiles($directory, $extension = null) {
    $files = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']; // Common image extensions
    
    // Debug info
    error_log("getDirectoryFiles called for directory: $directory");
    
    // Clean the directory path and remove dangerous characters
    $directory = trim(str_replace('..', '', $directory), '/\\');
    
    // Normalize slashes for consistency
    $directory = str_replace('\\', '/', $directory);
    
    // IMPORTANT: Normalize directory name to lowercase for case-insensitive matching
    $directoryLower = strtolower($directory);
    
    // Prepare absolute paths - try multiple possibilities
    $baseDir = __DIR__;
    $possiblePaths = [
        // Original case
        $baseDir . DIRECTORY_SEPARATOR . $directory,
        $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $directory,
        $baseDir . DIRECTORY_SEPARATOR . 'images/products' . DIRECTORY_SEPARATOR . $directory,
        $baseDir . '/' . $directory,
        $baseDir . '/images/' . $directory,
        $baseDir . '/images/products/' . $directory,
        
        // Lowercase everything - helps with case sensitivity issues
        $baseDir . DIRECTORY_SEPARATOR . $directoryLower,
        $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $directoryLower,
        $baseDir . DIRECTORY_SEPARATOR . 'images/products' . DIRECTORY_SEPARATOR . $directoryLower,
        $baseDir . '/' . $directoryLower,
        $baseDir . '/images/' . $directoryLower,
        $baseDir . '/images/products/' . $directoryLower,
        
        // Try with first letter capitalized (common convention)
        $baseDir . DIRECTORY_SEPARATOR . ucfirst($directoryLower),
        $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . ucfirst($directoryLower),
        $baseDir . DIRECTORY_SEPARATOR . 'images/products' . DIRECTORY_SEPARATOR . ucfirst($directoryLower),
        $baseDir . '/' . ucfirst($directoryLower),
        $baseDir . '/images/' . ucfirst($directoryLower),
        $baseDir . '/images/products/' . ucfirst($directoryLower),
        
        // Try with all capitals (another possibility)
        $baseDir . DIRECTORY_SEPARATOR . strtoupper($directoryLower),
        $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . strtoupper($directoryLower),
        $baseDir . DIRECTORY_SEPARATOR . 'images/products' . DIRECTORY_SEPARATOR . strtoupper($directoryLower),
        $baseDir . '/' . strtoupper($directoryLower),
        $baseDir . '/images/' . strtoupper($directoryLower),
        $baseDir . '/images/products/' . strtoupper($directoryLower)
    ];
    
    // Log paths for debugging
    error_log("Requested Directory: " . $directory);
    
    $fullPath = null;
    $foundDir = '';
    
    // Try each possible path
    foreach ($possiblePaths as $path) {
        $normalizedPath = str_replace('\\', '/', $path); // Normalize slashes
        error_log("Trying path: " . $normalizedPath);
        if (is_dir($normalizedPath)) {
            $fullPath = $normalizedPath;
            
            // Extract the actual directory name we found to use in web paths
            $pathParts = explode('/', $normalizedPath);
            $foundDir = end($pathParts);
            
            error_log("Found valid path: " . $fullPath . " with directory name: " . $foundDir);
            break;
        }
    }
    
    // If no valid path found, try a direct scan of the products directory to find a case-insensitive match
    if ($fullPath === null) {
        error_log("No direct path match, trying to scan products directory for a match");
        $productsPath = $baseDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products';
        
        if (is_dir($productsPath)) {
            $productDirs = scandir($productsPath);
            foreach ($productDirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                
                if (strtolower($dir) === strtolower(basename($directory))) {
                    $fullPath = $productsPath . DIRECTORY_SEPARATOR . $dir;
                    $foundDir = $dir;
                    error_log("Found case-insensitive match: " . $fullPath);
                    break;
                }
            }
        }
    }
    
    // If still no valid path found, return empty array
    if ($fullPath === null) {
        error_log("No valid directory found for: " . $directory);
        return [];
    }
    
    try {
        error_log("Scanning directory: " . $fullPath);
        $items = scandir($fullPath);
        error_log("Found " . count($items) . " items in directory");
        
        foreach ($items as $item) {
            // Skip . and .. directories
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $normalizedItemPath = str_replace('\\', '/', $itemPath);
            
            // Only include files, not directories
            if (is_file($normalizedItemPath)) {
                // Filter by extension if provided
                if ($extension !== null) {
                    $fileExt = pathinfo($item, PATHINFO_EXTENSION);
                    if (strtolower($fileExt) !== strtolower($extension)) {
                        continue;
                    }
                } else {
                    // If no extension filter provided, only include image files
                    $fileExt = pathinfo($item, PATHINFO_EXTENSION);
                    if (!in_array(strtolower($fileExt), $imageExtensions)) {
                        continue;
                    }
                }
                
                // Construct the web path using the actual directory name we found
                // This ensures correct case in the path for web requests
                $dirParts = explode('/', $directory);
                $dirParts[count($dirParts) - 1] = $foundDir; // Replace last part with the actual directory name found
                
                // Clean path construction - prevent duplication
                // Check if the directory already contains 'images/products'
                if (strpos(strtolower($directory), 'images/products') !== false) {
                    // Already has the prefix, just use it
                    $webPath = implode('/', $dirParts) . '/' . $item;
                } else {
                    // Add the prefix
                    $webPath = 'images/products/' . implode('/', $dirParts) . '/' . $item;
                }
                
                // Make sure the web path starts with images/ for proper web access
                if (strpos($webPath, 'images/') !== 0) {
                    $webPath = 'images/' . $webPath;
                }
                
                // Fix any double slashes or duplicate paths
                $webPath = str_replace('//', '/', $webPath);
                
                // Prevent duplicate 'images/products' in the path
                $webPath = preg_replace('#(images/products/)+#i', 'images/products/', $webPath);
                
                error_log("Adding file: " . $item . " with web path: " . $webPath);
                
                // Add to files array as an object with name and path
                $files[] = [
                    'name' => pathinfo($item, PATHINFO_FILENAME),
                    'path' => $webPath,
                    'size' => filesize($normalizedItemPath),
                    'type' => mime_content_type($normalizedItemPath)
                ];
            }
        }
        
        // Log the result
        error_log("Returning " . count($files) . " files");
        
        return $files;
    } catch (Exception $e) {
        error_log("Error scanning directory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product categories from the product directory
 * 
 * @param string $baseDir Base directory path
 * @return array Array of category information with image counts
 */
function getProductCategories($baseDir) {
    $baseDir = realpath($baseDir);
    if (!$baseDir) return ['success' => false, 'error' => 'Products directory not found'];

    $categories = [];
    $items = scandir($baseDir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($fullPath) && !in_array(strtolower($item), ['thumbnails', '.ds_store'])) {
            // Normalize category name
            $cleanName = str_replace(['_', '-'], ' ', $item);
            $displayName = ucwords(strtolower($cleanName));
            
            // Special case for MBNA
            $displayName = preg_replace('/\bmbna\b/i', 'MBNA', $displayName);
            
            // Get first available image (any format)
            $images = glob($fullPath . '/{*.png,*.jpg,*.jpeg,*.webp}', GLOB_BRACE);
            $imageCount = count($images);
            
            // Find thumbnail (check thumbnails subdir first)
            $thumbPath = '';
            $thumbnails = glob($fullPath . '/thumbnails/{*.png,*.jpg,*.jpeg,*.webp}', GLOB_BRACE);
            
            if (!empty($thumbnails)) {
                $thumbPath = 'images/products/' . $item . '/thumbnails/' . basename($thumbnails[0]);
            } elseif (!empty($images)) {
                $thumbPath = 'images/products/' . $item . '/' . basename($images[0]);
            } else {
                $thumbPath = 'images/default-thumbnail.jpg';
            }
            
            $categories[] = [
                'name' => $item,
                'display_name' => $displayName,
                'image_count' => $imageCount,
                'thumbnail' => $thumbPath
            ];
        }
    }
    
    return $categories;
}

function getAllImages($directory = 'images/products') {
    $images = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                $images[] = str_replace('\\', '/', $file->getPathname());
            }
        }
    }
    
    return $images;
}

// Get parameters
$directory = isset($_GET['directory']) ? $_GET['directory'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$term = isset($_GET['term']) ? $_GET['term'] : '';

// Set headers for JSON response
header('Content-Type: application/json');

// Handle different actions
if ($action === 'findFile') {
    // Direct file search functionality
    $response = searchFiles($term);
} else {
    // Default directory listing functionality
    if (empty($directory)) {
        $response = [
            'success' => false,
            'error' => 'Directory parameter is required',
            'files' => []
        ];
    } else {
        // Get files from the directory
        $files = getDirectoryFiles($directory);
        $response = [
            'success' => true,
            'directory' => $directory,
            'files' => $files
        ];
    }
}

// Return the response as JSON
echo json_encode($response);
exit;

/**
 * Search for files in all product categories containing the given term
 * @param string $term Search term
 * @return array Response with search results
 */
function searchFiles($term) {
    if (empty($term)) {
        return [
            'success' => false,
            'error' => 'Search term is required',
            'files' => []
        ];
    }
    
    // Base product directory
    $baseDir = __DIR__ . '/images/products';
    $baseWebPath = 'images/products';
    $allFiles = [];
    
    // Log the search
    error_log("Searching for files containing '$term'");
    
    // Check if the products directory exists
    if (!is_dir($baseDir)) {
        error_log("Products directory not found: $baseDir");
        return [
            'success' => false,
            'error' => 'Products directory not found',
            'files' => []
        ];
    }
    
    // Get all product categories
    $categories = [];
    try {
        $categories = scandir($baseDir);
    } catch (Exception $e) {
        error_log("Error scanning products directory: " . $e->getMessage());
    }
    
    // Search through each category
    foreach ($categories as $category) {
        // Skip . and .. directories
        if ($category === '.' || $category === '..') {
            continue;
        }
        
        $categoryPath = $baseDir . '/' . $category;
        
        // Only search directories
        if (!is_dir($categoryPath)) {
            continue;
        }
        
        error_log("Searching in category: $category");
        
        // Scan all files in this category
        $files = [];
        try {
            $files = scandir($categoryPath);
        } catch (Exception $e) {
            error_log("Error scanning category $category: " . $e->getMessage());
            continue;
        }
        
        // Check each file
        foreach ($files as $file) {
            // Skip . and .. 
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $categoryPath . '/' . $file;
            
            // Skip directories
            if (is_dir($filePath)) {
                continue;
            }
            
            // Check if the filename contains the search term (case-insensitive)
            if (stripos($file, $term) !== false) {
                error_log("  Found match: $file");
                
                // Add to results
                $allFiles[] = [
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'path' => $baseWebPath . '/' . $category . '/' . $file,
                    'category' => $category,
                    'size' => filesize($filePath),
                    'type' => mime_content_type($filePath)
                ];
            }
        }
    }
    
    error_log("Search complete. Found " . count($allFiles) . " results");
    
    return [
        'success' => true,
        'term' => $term,
        'count' => count($allFiles),
        'files' => $allFiles
    ];
}
