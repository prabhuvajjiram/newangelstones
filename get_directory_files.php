<?php
// Disable error display in output - CRITICAL for JSON response
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
// Add no-cache headers to prevent browser caching of API responses
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Get all files in a specific directory
 */
function getDirectoryFiles($directory) {
    // Validate directory path - only allow specific directories
    $directory = htmlspecialchars(strip_tags($directory));
    
    // Remove any leading or trailing slashes
    $directory = trim($directory, '/');
    
    // Base directory is one level up from this script
    $baseDir = __DIR__ . '/images';
    
    // If searching products, provide the right subdirectory
    if ($directory === 'products' || strpos($directory, 'products/') === 0) {
        $normalizedBaseDir = realpath($baseDir . '/products');
        
        // Exit if base directory doesn't exist
        if (!$normalizedBaseDir) {
            error_log("Base directory not found: {$baseDir}/products");
            return [
                'success' => false,
                'error' => 'Base directory not found',
                'files' => []
            ];
        }

        // If just 'products', return all categories
        if ($directory === 'products') {
            $categories = [];
            $dirs = scandir($normalizedBaseDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($normalizedBaseDir . '/' . $dir)) {
                    // Get first image in the category for thumbnail
                    $thumbnail = null;
                    $categoryPath = $normalizedBaseDir . '/' . $dir;
                    if ($handle = opendir($categoryPath)) {
                        while (false !== ($item = readdir($handle))) {
                            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $thumbnail = 'images/products/' . $dir . '/' . $item;
                                break;
                            }
                        }
                        closedir($handle);
                    }
                    
                    $categories[] = [
                        'name' => $dir,
                        'path' => 'images/products/' . $dir,
                        'thumbnail' => $thumbnail
                    ];
                }
            }
            return [
                'success' => true,
                'files' => $categories
            ];
        }
        
        $category = substr($directory, strlen('products/'));
        $targetPath = $normalizedBaseDir . '/' . $category;
        
        // Try to find the directory with case-insensitive search if it doesn't exist directly
        if (!file_exists($targetPath)) {
            // Get all directories in the products folder
            $dirs = scandir($normalizedBaseDir);
            $found = false;
            
            foreach ($dirs as $dir) {
                // Skip . and .. entries
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                // Case-insensitive comparison
                if (strtolower($dir) === strtolower($category)) {
                    $targetPath = $normalizedBaseDir . '/' . $dir;
                    $category = $dir; // Use the actual directory name from the file system
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                error_log("Could not find directory: {$category} in {$normalizedBaseDir}");
                return [
                    'success' => false,
                    'error' => 'Directory not found: ' . $category,
                    'files' => []
                ];
            }
        }
        
        // Check if the path is valid
        $normalizedPath = realpath($targetPath);
        if (!$normalizedPath || strpos($normalizedPath, $normalizedBaseDir) !== 0) {
            error_log("Invalid path: {$targetPath}");
            return [
                'success' => false,
                'error' => 'Invalid directory path',
                'files' => []
            ];
        }
        
        // Get all files in the directory
        $files = [];
        if ($handle = opendir($normalizedPath)) {
            while (false !== ($item = readdir($handle))) {
                // Skip directories and hidden files
                $normalizedItemPath = $normalizedPath . '/' . $item;
                if (is_dir($normalizedItemPath) || $item === '.' || $item === '..' || strpos($item, '.') === 0) {
                    continue;
                }
                
                // Only include image files
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    continue;
                }
                
                // Create web-accessible path - use the exact category name as found on disk
                $webPath = 'images/products/' . $category . '/' . $item;
                
                // Add cache-busting timestamp to force browser to load the latest image
                // Skip cache busting for MBNA_2025 category
                if ($category !== 'MBNA_2025') {
                    // Use file's last modified time as cache buster
                    $fileTimestamp = filemtime($normalizedItemPath);
                    $webPath .= "?v=" . $fileTimestamp;
                }
                
                // For debugging
                error_log("Adding file: {$item} with web path: {$webPath}");
                
                // Add to files array as an object with name and path
                $files[] = [
                    'name' => pathinfo($item, PATHINFO_FILENAME),
                    'path' => str_replace('\\', '/', $webPath), // Ensure forward slashes
                    'size' => filesize($normalizedItemPath),
                    'type' => mime_content_type($normalizedItemPath),
                    'extension' => pathinfo($item, PATHINFO_EXTENSION),
                    'fullname' => $item
                ];
            }
            closedir($handle);
        }
        
        // Log the result
        error_log("Returning " . count($files) . " files");
        
        return [
            'success' => true,
            'files' => $files
        ];
    } else {
        error_log("Invalid directory: $directory");
        return [
            'success' => false,
            'error' => 'Invalid directory',
            'files' => []
        ];
    }
}

// Get directory parameter
$directory = isset($_GET['directory']) ? $_GET['directory'] : '';

// Log request for debugging
error_log("Directory request: $directory");

if (empty($directory)) {
    echo json_encode(['success' => false, 'error' => 'Directory parameter is required']);
    exit;
}

// Get files from directory
$files = getDirectoryFiles($directory);

// Return response
header('Content-Type: application/json');
echo json_encode($files, JSON_PRETTY_PRINT);
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
