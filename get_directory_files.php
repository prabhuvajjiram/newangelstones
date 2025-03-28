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
    
    // Clean the directory path and remove 'images/' from the beginning if present
    $directory = trim(str_replace('..', '', $directory), '/');
    if (strpos($directory, 'images/') === 0) {
        $directory = substr($directory, 7); // Remove 'images/' prefix
    }
    
    // Get the absolute path to the images directory
    $baseDir = __DIR__;
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $directory;
    
    // Log the paths for debugging
    error_log("Base Directory: " . $baseDir);
    error_log("Requested Directory: " . $directory);
    error_log("Full Path: " . $fullPath);
    
    if (!is_dir($fullPath)) {
        error_log("Directory not found: " . $fullPath);
        // Try alternate path
        $altPath = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $directory;
        error_log("Trying alternate path: " . $altPath);
        if (is_dir($altPath)) {
            $fullPath = $altPath;
            error_log("Using alternate path");
        } else {
            return $files;
        }
    }
    
    try {
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            // Skip . and .. directories
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            
            // Only include files, not directories
            if (is_file($itemPath)) {
                // Filter by extension if provided
                if ($extension !== null) {
                    $fileExt = pathinfo($item, PATHINFO_EXTENSION);
                    if (strtolower($fileExt) !== strtolower($extension)) {
                        continue;
                    }
                }
                
                $files[] = $item;
            }
        }
        
        // Sort files naturally
        natcasesort($files);
        
        return array_values($files);
    } catch (Exception $e) {
        error_log("Error in getDirectoryFiles: " . $e->getMessage());
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

// Default response
$response = [
    'success' => false,
    'files' => [],
    'error' => null
];

// Determine action from GET or command line
$action = null;
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($argv) && count($argv) > 1) {
    // Check if called from command line with parameters
    foreach ($argv as $arg) {
        if (strpos($arg, 'action=') === 0) {
            $action = substr($arg, strlen('action='));
        }
    }
}

try {
    // Check if we're requesting categories
    if ($action === 'get_categories') {
        $productsDir = __DIR__ . '/images/products';
        
        // Make sure the directory exists
        if (!is_dir($productsDir)) {
            $response = [
                'success' => false,
                'error' => 'Products directory not found',
                'path' => $productsDir
            ];
        } else {
            $categories = getProductCategories($productsDir);
            
            // Make sure we have at least one category
            if (empty($categories)) {
                // Add fallback category if none found
                $categories[] = [
                    'name' => 'MBNA_2025',
                    'display_name' => 'MBNA 2025',
                    'image_count' => 25,
                    'thumbnail' => 'images/products/MBNA_2025/thumbnails/AG-952.png'
                ];
            }
            
            $response = [
                'success' => true,
                'categories' => $categories,
                'count' => count($categories)
            ];
        }
    } else {
        // Get directory from request
        $directory = isset($_GET['directory']) ? $_GET['directory'] : '';
        $extension = isset($_GET['extension']) ? $_GET['extension'] : null;
        
        if (empty($directory)) {
            $response = [
                'success' => false,
                'error' => 'Directory parameter is required',
                'files' => []
            ];
        } else {
            // Get files
            $files = getDirectoryFiles($directory, $extension);
            
            // Return response
            $response = [
                'success' => true,
                'directory' => $directory,
                'files' => $files,
                'count' => count($files),
                'debug' => [
                    'requestedDir' => $directory,
                    'fullPath' => __DIR__ . DIRECTORY_SEPARATOR . $directory,
                    'altPath' => __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $directory,
                    'baseDir' => __DIR__
                ]
            ];
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);
