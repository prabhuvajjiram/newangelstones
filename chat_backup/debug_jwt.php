<?php
/**
 * Debug JWT Authentication
 * 
 * This script provides detailed debugging for JWT authentication issues
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set up output
header('Content-Type: text/html; charset=utf-8');
echo '<h1>Debug JWT Authentication</h1>';

// Create log function
function debugLog($message, $type = 'info') {
    $color = 'black';
    switch ($type) {
        case 'error': $color = 'red'; break;
        case 'success': $color = 'green'; break;
        case 'warning': $color = 'orange'; break;
        case 'info': $color = 'blue'; break;
    }
    
    echo "<div style='color: {$color}; margin: 5px 0;'>{$message}</div>";
}

// Check configuration
echo '<h2>Configuration Check</h2>';

if (!defined('RINGCENTRAL_JWT_TOKEN') || empty(RINGCENTRAL_JWT_TOKEN)) {
    debugLog('Error: JWT token is not defined or empty', 'error');
} else {
    $tokenPreview = substr(RINGCENTRAL_JWT_TOKEN, 0, 20) . '...' . substr(RINGCENTRAL_JWT_TOKEN, -5);
    debugLog("JWT token looks valid: {$tokenPreview}", 'success');
    
    // Check for common JWT format issues
    $parts = explode('.', RINGCENTRAL_JWT_TOKEN);
    if (count($parts) !== 3) {
        debugLog('Error: JWT token does not have 3 parts (header.payload.signature)', 'error');
    } else {
        debugLog('JWT token has the correct format (header.payload.signature)', 'success');
    }
}

if (!defined('RINGCENTRAL_CLIENT_ID') || empty(RINGCENTRAL_CLIENT_ID)) {
    debugLog('Error: Client ID is not defined or empty', 'error');
} else {
    debugLog("Client ID: " . RINGCENTRAL_CLIENT_ID, 'success');
}

if (!defined('RINGCENTRAL_CLIENT_SECRET') || empty(RINGCENTRAL_CLIENT_SECRET)) {
    debugLog('Error: Client Secret is not defined or empty', 'error');
} else {
    debugLog("Client Secret: " . substr(RINGCENTRAL_CLIENT_SECRET, 0, 3) . '...' . substr(RINGCENTRAL_CLIENT_SECRET, -3), 'success');
}

if (!defined('RINGCENTRAL_SERVER') || empty(RINGCENTRAL_SERVER)) {
    debugLog('Error: Server URL is not defined or empty', 'error');
} else {
    debugLog("Server URL: " . RINGCENTRAL_SERVER, 'success');
}

// Now try to make the actual authentication request
echo '<h2>Authentication Request</h2>';

$endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
debugLog("Endpoint: {$endpoint}", 'info');

// Prepare request
$data = [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => RINGCENTRAL_JWT_TOKEN
];

debugLog("Request data: " . json_encode($data, JSON_PRETTY_PRINT), 'info');

// Prepare CURL
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Add debugging options
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);     // Disable host verification for testing
curl_setopt($ch, CURLOPT_VERBOSE, true);         // Enable verbose output

// Create a buffer for CURL debug output
$curlDebug = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $curlDebug);

// Execute request
debugLog("Sending request...", 'info');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get CURL debug output
rewind($curlDebug);
$curlDebugOutput = stream_get_contents($curlDebug);
fclose($curlDebug);

// Check for CURL errors
if ($response === false) {
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    debugLog("CURL Error ({$errno}): {$error}", 'error');
} else {
    debugLog("HTTP Status Code: {$httpCode}", $httpCode == 200 ? 'success' : 'warning');
    debugLog("Response Body: " . $response, $httpCode == 200 ? 'success' : 'warning');
    
    // Parse the response if JSON
    if ($response && $httpCode == 200) {
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse) {
            echo '<h3>Authentication Success</h3>';
            echo '<pre>' . print_r($jsonResponse, true) . '</pre>';
            
            if (isset($jsonResponse['access_token'])) {
                debugLog('Access token received successfully!', 'success');
            }
        }
    } else if ($response) {
        // Try to parse error response
        $jsonError = json_decode($response, true);
        if ($jsonError) {
            echo '<h3>Authentication Error Details</h3>';
            echo '<pre>' . print_r($jsonError, true) . '</pre>';
        }
    }
}

// Show CURL verbose output
echo '<h3>CURL Debug Output</h3>';
echo '<pre style="background-color: #f5f5f5; padding: 10px; overflow-x: auto;">' . htmlspecialchars($curlDebugOutput) . '</pre>';

// Get info about the request
$info = curl_getinfo($ch);
curl_close($ch);

echo '<h3>CURL Request Info</h3>';
echo '<pre>' . print_r($info, true) . '</pre>';

// JWT token decode and inspection
echo '<h2>JWT Token Inspection</h2>';
$tokenParts = explode('.', RINGCENTRAL_JWT_TOKEN);
if (count($tokenParts) === 3) {
    // Decode header
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])), true);
    echo '<h3>JWT Header</h3>';
    echo '<pre>' . print_r($header, true) . '</pre>';
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
    echo '<h3>JWT Payload</h3>';
    echo '<pre>' . print_r($payload, true) . '</pre>';
    
    // Check expiration
    if (isset($payload['exp'])) {
        $expiryTime = $payload['exp'];
        $currentTime = time();
        
        if ($expiryTime < $currentTime) {
            debugLog("JWT Token is EXPIRED! Expired at: " . date('Y-m-d H:i:s', $expiryTime), 'error');
            debugLog("Current time: " . date('Y-m-d H:i:s', $currentTime), 'info');
            debugLog("Token expired " . ($currentTime - $expiryTime) . " seconds ago", 'error');
        } else {
            debugLog("JWT Token is VALID! Expires at: " . date('Y-m-d H:i:s', $expiryTime), 'success');
            debugLog("Current time: " . date('Y-m-d H:i:s', $currentTime), 'info');
            debugLog("Token will expire in " . ($expiryTime - $currentTime) . " seconds", 'info');
        }
    } else {
        debugLog("JWT Token does not contain an expiration claim!", 'warning');
    }
}

// Show recommendations
echo '<h2>Recommendations</h2>';
echo '<ul>';
echo '<li>Check that your JWT token is valid and not expired</li>';
echo '<li>Verify client ID and client secret are correct</li>';
echo '<li>Ensure the server URL is correct (https://platform.ringcentral.com or https://platform.devtest.ringcentral.com)</li>';
echo '<li>If using a sandbox account, make sure to use the devtest URL</li>';
echo '<li>Check for network connectivity issues</li>';
echo '</ul>';

echo '<p><a href="test_chat.html">Back to Test Chat</a></p>';
?>
