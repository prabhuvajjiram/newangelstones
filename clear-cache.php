<?php
/**
 * Cache Management Script
 * 
 * Usage:
 * 1. Clear all cache: /clear-cache.php
 * 2. Clear specific category: /clear-cache.php?category=Monuments
 * 3. View cache status: /clear-cache.php?action=status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cacheDir = __DIR__ . '/cache';
$action = isset($_GET['action']) ? $_GET['action'] : 'clear';
$category = isset($_GET['category']) ? $_GET['category'] : null;

$response = [
    'success' => false,
    'message' => '',
    'cleared' => [],
    'errors' => []
];

// Check if cache directory exists
if (!is_dir($cacheDir)) {
    $response['message'] = 'Cache directory does not exist';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'status') {
    // Show cache status
    $files = glob($cacheDir . '/*.json');
    $cacheInfo = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        $age = time() - filemtime($file);
        $size = filesize($file);
        $ageMinutes = round($age / 60);
        
        $cacheInfo[] = [
            'file' => $filename,
            'size_kb' => round($size / 1024, 2),
            'age_minutes' => $ageMinutes,
            'expired' => $ageMinutes > 60
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'Cache status retrieved';
    $response['cache_files'] = $cacheInfo;
    $response['total_files'] = count($files);
    
} elseif ($action === 'clear') {
    // Clear cache
    if ($category) {
        // Clear specific category cache
        $cacheKey = preg_replace('/[^A-Za-z0-9_-]/', '_', 'products_' . $category);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        
        if (file_exists($cacheFile)) {
            if (unlink($cacheFile)) {
                $response['cleared'][] = basename($cacheFile);
                $response['success'] = true;
                $response['message'] = "Cache cleared for category: {$category}";
            } else {
                $response['errors'][] = "Failed to delete: " . basename($cacheFile);
            }
        } else {
            $response['message'] = "Cache file not found for category: {$category}";
        }
    } else {
        // Clear all cache files
        $files = glob($cacheDir . '/*.json');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $response['cleared'][] = basename($file);
            } else {
                $response['errors'][] = 'Failed to delete: ' . basename($file);
            }
        }
        
        $response['success'] = true;
        $response['message'] = count($response['cleared']) . ' cache files cleared';
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
