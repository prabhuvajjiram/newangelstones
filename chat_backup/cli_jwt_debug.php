<?php
/**
 * CLI JWT Authentication Debug
 * Designed for command line usage
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set up CLI output
echo "RingCentral JWT Authentication Debug\n";
echo "==================================\n\n";

// Create log function for CLI
function debugLog($message, $type = 'info') {
    $prefix = '  ';
    switch ($type) {
        case 'error':   $prefix = '❌ '; break;
        case 'success': $prefix = '✅ '; break;
        case 'warning': $prefix = '⚠️  '; break;
        case 'info':    $prefix = 'ℹ️  '; break;
    }
    
    echo "{$prefix}{$message}\n";
}

// Check configuration
echo "Configuration Check:\n";

if (!defined('RINGCENTRAL_JWT_TOKEN') || empty(RINGCENTRAL_JWT_TOKEN)) {
    debugLog('JWT token is not defined or empty', 'error');
} else {
    $tokenPreview = substr(RINGCENTRAL_JWT_TOKEN, 0, 20) . '...' . substr(RINGCENTRAL_JWT_TOKEN, -5);
    debugLog("JWT token looks valid: {$tokenPreview}", 'success');
    
    // Check for common JWT format issues
    $parts = explode('.', RINGCENTRAL_JWT_TOKEN);
    if (count($parts) !== 3) {
        debugLog('JWT token does not have 3 parts (header.payload.signature)', 'error');
    } else {
        debugLog('JWT token has the correct format (header.payload.signature)', 'success');
    }
}

if (!defined('RINGCENTRAL_CLIENT_ID') || empty(RINGCENTRAL_CLIENT_ID)) {
    debugLog('Client ID is not defined or empty', 'error');
} else {
    debugLog("Client ID: " . RINGCENTRAL_CLIENT_ID, 'success');
}

if (!defined('RINGCENTRAL_CLIENT_SECRET') || empty(RINGCENTRAL_CLIENT_SECRET)) {
    debugLog('Client Secret is not defined or empty', 'error');
} else {
    debugLog("Client Secret: " . substr(RINGCENTRAL_CLIENT_SECRET, 0, 3) . '...' . substr(RINGCENTRAL_CLIENT_SECRET, -3), 'success');
}

if (!defined('RINGCENTRAL_SERVER') || empty(RINGCENTRAL_SERVER)) {
    debugLog('Server URL is not defined or empty', 'error');
} else {
    debugLog("Server URL: " . RINGCENTRAL_SERVER, 'success');
}

echo "\nAuthentication Request:\n";

$endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
debugLog("Endpoint: {$endpoint}", 'info');

// Prepare request
$data = [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => RINGCENTRAL_JWT_TOKEN
];

debugLog("Request data prepared", 'info');

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

// Execute request
debugLog("Sending request...", 'info');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for CURL errors
if ($response === false) {
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    debugLog("CURL Error ({$errno}): {$error}", 'error');
} else {
    debugLog("HTTP Status Code: {$httpCode}", $httpCode == 200 ? 'success' : 'warning');
    
    // Parse the response if JSON
    if ($response && $httpCode == 200) {
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse) {
            echo "\nAuthentication Success:\n";
            
            if (isset($jsonResponse['access_token'])) {
                debugLog("Access token received: " . substr($jsonResponse['access_token'], 0, 15) . '...', 'success');
                debugLog("Token expires in: " . ($jsonResponse['expires_in'] ?? 'unknown') . " seconds", 'success');
                debugLog("Token type: " . ($jsonResponse['token_type'] ?? 'unknown'), 'info');
                
                // Write token to file for inspection
                $tokenFile = __DIR__ . '/secure_storage/debug_token.json';
                file_put_contents($tokenFile, json_encode($jsonResponse, JSON_PRETTY_PRINT));
                debugLog("Saved token to: {$tokenFile}", 'info');
            }
        }
    } else if ($response) {
        // Try to parse error response
        $jsonError = json_decode($response, true);
        if ($jsonError) {
            echo "\nAuthentication Error Details:\n";
            debugLog("Error: " . ($jsonError['error'] ?? 'Unknown error'), 'error');
            debugLog("Description: " . ($jsonError['error_description'] ?? 'No description'), 'error');
            
            // Write error to file for inspection
            $errorFile = __DIR__ . '/secure_storage/auth_error.json';
            file_put_contents($errorFile, json_encode($jsonError, JSON_PRETTY_PRINT));
            debugLog("Saved error details to: {$errorFile}", 'info');
        } else {
            debugLog("Raw response: " . $response, 'warning');
        }
    }
}

// Get info about the request
$info = curl_getinfo($ch);
curl_close($ch);

// JWT token decode and inspection
echo "\nJWT Token Inspection:\n";
$tokenParts = explode('.', RINGCENTRAL_JWT_TOKEN);
if (count($tokenParts) === 3) {
    // Decode header
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])), true);
    echo "JWT Header:\n";
    if ($header) {
        foreach ($header as $key => $value) {
            echo "  - {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
    echo "\nJWT Payload:\n";
    if ($payload) {
        foreach ($payload as $key => $value) {
            if ($key == 'exp' || $key == 'iat' || $key == 'nbf') {
                echo "  - {$key}: " . $value . " (" . date('Y-m-d H:i:s', $value) . ")\n";
            } else {
                echo "  - {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
        }
    }
    
    // Check expiration
    if (isset($payload['exp'])) {
        $expiryTime = $payload['exp'];
        $currentTime = time();
        
        echo "\nToken Validity:\n";
        if ($expiryTime < $currentTime) {
            debugLog("JWT Token is EXPIRED!", 'error');
            debugLog("Expired at: " . date('Y-m-d H:i:s', $expiryTime), 'error');
            debugLog("Current time: " . date('Y-m-d H:i:s', $currentTime), 'info');
            debugLog("Token expired " . ($currentTime - $expiryTime) . " seconds ago", 'error');
        } else {
            debugLog("JWT Token is VALID!", 'success');
            debugLog("Expires at: " . date('Y-m-d H:i:s', $expiryTime), 'success');
            debugLog("Current time: " . date('Y-m-d H:i:s', $currentTime), 'info');
            debugLog("Token will expire in " . ($expiryTime - $currentTime) . " seconds (" . 
                     round(($expiryTime - $currentTime) / 3600, 1) . " hours)", 'info');
        }
    } else {
        debugLog("JWT Token does not contain an expiration claim!", 'warning');
    }
}

// Show recommendations
echo "\nRecommendations:\n";
echo "1. Check that your JWT token is valid and not expired\n";
echo "2. Verify client ID and client secret are correct\n";
echo "3. Ensure the server URL is correct\n";
echo "4. If using a sandbox account, use https://platform.devtest.ringcentral.com\n";
echo "5. For production, use https://platform.ringcentral.com\n";
echo "6. Check for network connectivity issues\n";

echo "\nDebug completed.\n";
?>
