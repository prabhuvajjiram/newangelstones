<?php
/**
 * Monument Business Inventory API Proxy
 * 
 * This script fetches inventory data from the monument.business API
 * with support for pagination and filtering.
 * 
 * Handles both GET and POST requests and provides detailed error information
 * for easier debugging and troubleshooting.
 */

// Set headers to allow cross-origin requests and specify JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_DURATION', 86400); // 24 hours in seconds (86400 = 24 * 60 * 60)

// Add cache control headers to prevent Cloudflare and browser caching
// Note: Server-side file cache is separate from browser cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Cloudflare-CDN-Cache-Control: no-cache'); // Specific Cloudflare directive

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API endpoint URL and credentials
$api_url = 'https://monument.business/Api/Inventory/GetAllStock';
$api_key = 'e8l3DUB3i8gUT3ubYiEu73aOh80t6b5hW8mqhAOJOOvROxS5k3lASFHVxRY6Ky5U';
$org_id = 2;

// Enable error reporting for debugging (should be disabled in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start measuring execution time for performance monitoring
$start_time = microtime(true);

// Log the request for debugging
$request_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'get_params' => $_GET,
    'post_params' => $_POST
];

// Combine GET and POST parameters to handle both request types
$params = array_merge($_GET, $_POST);

// Check if this is a request for item details
if (isset($params['action']) && $params['action'] === 'getDetails' && isset($params['epcode'])) {
    $epcode = $params['epcode'];
    
    // Auto-cleanup old cache files (older than 7 days) - runs randomly 1% of the time
    if (CACHE_ENABLED && rand(1, 100) === 1) {
        $seven_days_ago = time() - (7 * 24 * 60 * 60);
        $cache_files = glob(CACHE_DIR . '/detail_*.json');
        $deleted_count = 0;
        foreach ($cache_files as $file) {
            if (filemtime($file) < $seven_days_ago) {
                @unlink($file);
                $deleted_count++;
            }
        }
        // Log cleanup for monitoring (optional - comment out in production if not needed)
        if ($deleted_count > 0) {
            error_log("Inventory cache cleanup: Deleted $deleted_count old detail cache files");
        }
    }
    
    // Use separate cache for detailed records
    $detail_cache_file = CACHE_DIR . '/detail_' . md5($epcode) . '.json';
    
    // Check cache first
    if (CACHE_ENABLED && file_exists($detail_cache_file)) {
        $cache_age = time() - filemtime($detail_cache_file);
        if ($cache_age < CACHE_DURATION) {
            $cached_data = file_get_contents($detail_cache_file);
            if ($cached_data !== false) {
                echo $cached_data;
                exit;
            }
        }
    }
    
    // Fetch from detailed API
    $detail_api_url = 'https://monument.business/Api/Inventory/GetAllStockDetailedSummary';
    $detail_params = [
        'orgid' => $org_id,
        'epcode' => $epcode
    ];
    
    $ch = curl_init($detail_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($detail_params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if ($curl_error || $http_code !== 200) {
        echo json_encode([
            'success' => false,
            'error' => $curl_error ?: 'API returned status code: ' . $http_code,
            'stones' => []
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to parse API response',
            'stones' => []
        ]);
        exit;
    }
    
    // Extract stones from response
    $stones = [];
    if (isset($data['Data']) && is_array($data['Data'])) {
        $stones = $data['Data'];
    }
    
    $result = [
        'success' => true,
        'stones' => $stones,
        'count' => count($stones)
    ];
    
    // Cache the result
    if (CACHE_ENABLED) {
        if (!is_dir(CACHE_DIR)) {
            mkdir(CACHE_DIR, 0755, true);
        }
        file_put_contents($detail_cache_file, json_encode($result));
    }
    
    echo json_encode($result);
    exit;
}

// Get parameters from request or set defaults
$page = isset($params['page']) ? intval($params['page']) : 1;
$pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 1000;
$ptype = isset($params['ptype']) ? $params['ptype'] : '';
$pcolor = isset($params['pcolor']) ? $params['pcolor'] : '';
$pdesign = isset($params['pdesign']) ? $params['pdesign'] : '';
$pfinish = isset($params['pfinish']) ? $params['pfinish'] : '';
$psize = isset($params['psize']) ? $params['psize'] : '';
$locid = isset($params['locid']) ? $params['locid'] : '';  // Empty string for all locations
$description = isset($params['description']) ? $params['description'] : '';
$hasdesc = isset($params['hasdesc']) ? $params['hasdesc'] === 'true' : false;

// Create cache key based on request parameters (excluding timestamp which is just for cache-busting)
$cache_params = [
    'page' => $page,
    'pageSize' => $pageSize,
    'ptype' => $ptype,
    'pcolor' => $pcolor,
    'pdesign' => $pdesign,
    'pfinish' => $pfinish,
    'psize' => $psize,
    'locid' => $locid
];
$cache_key = 'inventory_' . md5(json_encode($cache_params)) . '.json';
$cache_file = CACHE_DIR . '/' . $cache_key;

// Function to check if cache is valid
function is_cache_valid($cache_file) {
    if (!CACHE_ENABLED || !file_exists($cache_file)) {
        return false;
    }
    
    $cache_time = filemtime($cache_file);
    $current_time = time();
    $cache_age = $current_time - $cache_time;
    
    // Check if cache is still valid (less than CACHE_DURATION seconds old)
    return $cache_age < CACHE_DURATION;
}

// Function to get cached data
function get_cached_data($cache_file) {
    if (!is_cache_valid($cache_file)) {
        return null;
    }
    
    $cached_json = file_get_contents($cache_file);
    $cached_data = json_decode($cached_json, true);
    
    if ($cached_data && is_array($cached_data)) {
        // Add cache info to response
        $cached_data['cached'] = true;
        $cached_data['cache_age'] = time() - filemtime($cache_file);
        $cached_data['cache_expires_in'] = CACHE_DURATION - (time() - filemtime($cache_file));
        return $cached_data;
    }
    
    return null;
}

// Function to save data to cache
function save_to_cache($cache_file, $data) {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    // Create cache directory if it doesn't exist
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    
    // Save data to cache file
    $json = json_encode($data);
    $result = file_put_contents($cache_file, $json);
    
    if ($result !== false) {
        // Set file permissions
        chmod($cache_file, 0644);
        return true;
    }
    
    return false;
}

// Check if we should force refresh (bypass cache)
$force_refresh = isset($params['force_refresh']) && $params['force_refresh'] === 'true';

// Try to get cached data first (unless force refresh is requested)
if (!$force_refresh && CACHE_ENABLED) {
    $cached_data = get_cached_data($cache_file);
    if ($cached_data !== null) {
        // Return cached data
        echo json_encode($cached_data);
        exit;
    }
}

// Request parameters for API call (new JSON format)
$api_params = [
    'orgid' => $org_id,
    'hasdesc' => $hasdesc,
    'description' => $description,
    'ptype' => $ptype,
    'pcolor' => $pcolor,
    'pdesign' => $pdesign,
    'pfinish' => $pfinish,
    'psize' => $psize,
    'locid' => $locid,
    'page' => $page,
    'pagesize' => $pageSize
];

// Function to handle errors and return consistent JSON response
function return_error($message, $code = 500, $additional_data = []) {
    global $start_time, $request_log, $api_params;
    
    $execution_time = microtime(true) - $start_time;
    
    $response = [
        'success' => false,
        'error' => $message,
        'error_code' => $code,
        'execution_time' => round($execution_time, 4) . 's',
        'debug' => array_merge($additional_data, [
            'request' => $request_log,
            'api_params' => $api_params
        ])
    ];
    
    http_response_code($code);
    echo json_encode($response);
    exit;
}

// Initialize cURL session
$ch = curl_init();

// Set cURL options with new JSON API format
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($api_params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, // For development only - should be true in production
    CURLOPT_SSL_VERIFYHOST => false, // For development only - should be 2 in production
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $api_key
    ]
]);

// Execute cURL request with error handling
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    return_error(
        'cURL error: ' . curl_error($ch), 
        500, 
        [
            'curl_error_code' => curl_errno($ch),
            'curl_error_message' => curl_error($ch),
            'curl_info' => curl_getinfo($ch)
        ]
    );
}

// Check HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code != 200) {
    return_error(
        'API returned non-200 HTTP status: ' . $http_code, 
        $http_code, 
        [
            'curl_info' => curl_getinfo($ch),
            'response_sample' => substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '')
        ]
    );
}

// Note: curl_close() is no longer needed in PHP 8.0+ (deprecated in 8.5)
// cURL handles are automatically closed when they go out of scope

// Calculate execution time so far
$execution_time = microtime(true) - $start_time;

// Process the response
// First, check if the response is valid JSON
$data = json_decode($response, true);
$json_error = json_last_error();

// Debug information
$debug_info = [
    'execution_time' => round($execution_time, 4) . 's',
    'json_error_code' => $json_error,
    'json_error_msg' => json_last_error_msg(),
    'response_length' => strlen($response),
    'response_sample' => substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_timestamp' => date('Y-m-d H:i:s'),
    'api_params' => $api_params
];

// Handle JSON parsing errors
if ($json_error !== JSON_ERROR_NONE) {
    return_error(
        'Failed to parse API response: ' . json_last_error_msg(),
        500,
        [
            'response_sample' => substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '')
        ]
    );
}

// Check if data is properly structured
if (!is_array($data)) {
    return_error(
        'API response is not a valid array',
        500,
        [
            'response_type' => gettype($data),
            'response_sample' => substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '')
        ]
    );
}

// Handle both uppercase and lowercase 'Data' property
$items = [];
if (isset($data['Data']) && is_array($data['Data'])) {
    $items = $data['Data'];
} elseif (isset($data['data']) && is_array($data['data'])) {
    $items = $data['data'];
}

// Get total rows from new API (TotalRows field shows items in current response)
$totalRows = 0;
if (isset($data['TotalRows'])) {
    $totalRows = intval($data['TotalRows']);
} elseif (isset($data['totalRows'])) {
    $totalRows = intval($data['totalRows']);
} elseif (is_array($items)) {
    $totalRows = count($items);
}

// Note: TotalRows in new API = number of items in current response, not total available
// Pagination logic: keep fetching pages while TotalRows == pageSize
$hasNextPage = $totalRows >= $pageSize;
$totalPages = 0; // We don't know total pages without fetching all data

// Calculate total pages (this is an estimate based on current page)
$totalPages = $hasNextPage ? $page + 1 : $page;

// Prepare the successful response
$result = [
    'success' => true,
    'data' => $items,
    'pagination' => [
        'page' => $page,
        'pageSize' => $pageSize,
        'totalItems' => $totalRows, // Items in current response
        'totalPages' => $totalPages, // Estimated
        'hasNextPage' => $hasNextPage,
        'hasPrevPage' => $page > 1
    ],
    'filters' => [
        'ptype' => $ptype,
        'pcolor' => $pcolor,
        'pdesign' => $pdesign,
        'pfinish' => $pfinish,
        'psize' => $psize,
        'locid' => $locid,
        'description' => $description,
        'hasdesc' => $hasdesc
    ],
    'execution_time' => round(microtime(true) - $start_time, 4) . 's',
    'cached' => false, // This is fresh data from API
    'cache_duration' => CACHE_DURATION . ' seconds (24 hours)',
    'api_version' => '2.0' // New API version
];

// Save the response to cache for future requests
if (CACHE_ENABLED) {
    save_to_cache($cache_file, $result);
}

// Add debug info in development mode
if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
    $result['debug'] = $debug_info;
    $result['cache_info'] = [
        'cache_enabled' => CACHE_ENABLED,
        'cache_file' => $cache_key,
        'cache_duration' => CACHE_DURATION . ' seconds'
    ];
}

// Return the JSON response
echo json_encode($result);
