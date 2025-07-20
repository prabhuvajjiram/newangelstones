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

// Add cache control headers to prevent Cloudflare and browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Cloudflare-CDN-Cache-Control: no-cache'); // Specific Cloudflare directive

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API endpoint URL
$url = 'https://monument.business/GV/GVOBPInventory/GetAllStockdetailsSummaryforall';

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

// Check if token is provided in the request, otherwise use the default
$token = isset($params['token']) ? $params['token'] : '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A';

// Get parameters from request or set defaults
$page = isset($params['page']) ? intval($params['page']) : 1;
$pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 1000;
$ptype = isset($params['ptype']) ? $params['ptype'] : '';
$pcolor = isset($params['pcolor']) ? $params['pcolor'] : '';
$pdesign = isset($params['pdesign']) ? $params['pdesign'] : '';
$pfinish = isset($params['pfinish']) ? $params['pfinish'] : '';
$psize = isset($params['psize']) ? $params['psize'] : '';
$locid = isset($params['locid']) ? $params['locid'] : '';  // Don't set a default, let the client specify

// Request parameters for API call
$api_params = [
    'sort' => '',
    'page' => $page,
    'pageSize' => $pageSize,
    'group' => '',
    'filter' => '',
    'token' => $token,
    'hasdesc' => 'false',
    'description' => '',
    'ptype' => $ptype,
    'pcolor' => $pcolor,
    'pdesign' => $pdesign,
    'pfinish' => $pfinish,
    'psize' => $psize,
    'locid' => $locid
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

// Set cURL options with better error handling
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($api_params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, // For development only - should be true in production
    CURLOPT_SSL_VERIFYHOST => false, // For development only - should be 2 in production
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Referer: https://monument.business/GV/GVOBPInventory/ShowInventoryAll/' . $token
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

// Close cURL session
curl_close($ch);

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

// Get total items count
$total = 0;
if (isset($data['Total'])) {
    $total = intval($data['Total']);
} elseif (isset($data['total'])) {
    $total = intval($data['total']);
} elseif (is_array($items)) {
    $total = count($items);
}

// Calculate total pages
$totalPages = ceil($total / $pageSize) ?: 1;

// Prepare the successful response
$result = [
    'success' => true,
    'data' => $items,
    'pagination' => [
        'page' => $page,
        'pageSize' => $pageSize,
        'totalItems' => $total,
        'totalPages' => $totalPages,
        'hasNextPage' => $page < $totalPages,
        'hasPrevPage' => $page > 1
    ],
    'filters' => [
        'ptype' => $ptype,
        'pcolor' => $pcolor,
        'pdesign' => $pdesign,
        'pfinish' => $pfinish,
        'psize' => $psize,
        'locid' => $locid
    ],
    'execution_time' => round(microtime(true) - $start_time, 4) . 's'
];

// Add debug info in development mode
if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
    $result['debug'] = $debug_info;
}

// Return the JSON response
echo json_encode($result);
