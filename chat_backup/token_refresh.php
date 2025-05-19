<?php
/**
 * RingCentral OAuth Token Refresh Script
 * 
 * This script refreshes the RingCentral OAuth token
 * Can be run manually or via cron job
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set up logging
$logFile = __DIR__ . '/token_refresh.log';

function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Check if this is a CLI run
$isCli = (php_sapi_name() === 'cli');

// If not CLI, set appropriate headers
if (!$isCli) {
    header('Content-Type: text/plain');
}

logMessage('Token refresh started');
echo "RingCentral Token Refresh\n";
echo "========================\n\n";

try {
    // Token file paths
    $tokenPath = __DIR__ . '/secure_storage/rc_token.json';
    $oldTokenPath = __DIR__ . '/.ringcentral_token.json';
    
    // Make sure secure directory exists
    if (!is_dir(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0755, true);
        logMessage('Created directory: ' . dirname($tokenPath));
    }
    
    // Check if token file exists
    if (file_exists($tokenPath)) {
        $tokenFile = $tokenPath;
    } elseif (file_exists($oldTokenPath)) {
        $tokenFile = $oldTokenPath;
    } else {
        throw new Exception("No token file found. Please authenticate with RingCentral first.");
    }
    
    // Read token data
    $tokenData = json_decode(file_get_contents($tokenFile), true);
    
    if (!isset($tokenData['refresh_token'])) {
        throw new Exception("No refresh token found in token file. Please re-authenticate with RingCentral.");
    }
    
    // Check if token needs refresh
    $expiresAt = $tokenData['expires_at'] ?? 0;
    $refreshTokenExpiresAt = $tokenData['refresh_token_expires_at'] ?? 0;
    $currentTime = time();
    
    // Log token information
    echo "Current time: " . date('Y-m-d H:i:s', $currentTime) . "\n";
    echo "Access token expires: " . date('Y-m-d H:i:s', $expiresAt) . "\n";
    echo "Refresh token expires: " . ($refreshTokenExpiresAt ? date('Y-m-d H:i:s', $refreshTokenExpiresAt) : 'unknown') . "\n\n";
    
    if ($expiresAt > $currentTime + 600) {
        echo "Access token is still valid (expires in " . round(($expiresAt - $currentTime) / 60) . " minutes).\n";
        logMessage("Access token still valid. No refresh needed.");
        exit;
    }
    
    // If refresh token is expired, we need to re-authenticate
    if ($refreshTokenExpiresAt && $refreshTokenExpiresAt <= $currentTime) {
        throw new Exception("Refresh token has expired. Please re-authenticate with RingCentral.");
    }
    
    // Prepare to refresh the token
    echo "Access token expired or will expire soon. Refreshing...\n";
    logMessage("Refreshing token");
    
    // Perform token refresh using curl
    $ch = curl_init(RINGCENTRAL_TOKEN_URL);
    $postFields = http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $tokenData['refresh_token']
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception("cURL error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Process the response
    if ($httpCode == 200) {
        $newTokenData = json_decode($response, true);
        
        if (!isset($newTokenData['access_token'])) {
            throw new Exception("Invalid response from RingCentral. No access token received.");
        }
        
        // Update token data
        $newTokenData['expires_at'] = time() + $newTokenData['expires_in'];
        if (isset($newTokenData['refresh_token_expires_in'])) {
            $newTokenData['refresh_token_expires_at'] = time() + $newTokenData['refresh_token_expires_in'];
        }
        
        // Keep refresh token if not returned in the response
        if (!isset($newTokenData['refresh_token']) && isset($tokenData['refresh_token'])) {
            $newTokenData['refresh_token'] = $tokenData['refresh_token'];
        }
        
        // Save updated token data
        if (file_put_contents($tokenPath, json_encode($newTokenData))) {
            echo "Token successfully refreshed and saved!\n";
            echo "New access token expires: " . date('Y-m-d H:i:s', $newTokenData['expires_at']) . "\n";
            logMessage("Token refreshed successfully");
            
            // Also update legacy token location for backward compatibility
            if (file_exists($oldTokenPath)) {
                file_put_contents($oldTokenPath, json_encode($newTokenData));
                echo "Also updated legacy token file for compatibility.\n";
            }
        } else {
            throw new Exception("Failed to save new token data.");
        }
    } else {
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error_description']) 
            ? $errorData['error_description'] 
            : "Unknown error (HTTP $httpCode)";
        
        throw new Exception("Failed to refresh token: $errorMessage");
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Please re-authenticate at: " . (isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}/chat/authorize.php" : "YOUR_SERVER_URL/chat/authorize.php") . "\n";
    logMessage("Error refreshing token: " . $e->getMessage(), 'ERROR');
}

// Suggest setting up a cron job
if ($isCli) {
    echo "\nTo automate token refresh, add this cron job:\n";
    echo "0 */6 * * * php " . __FILE__ . " > /dev/null 2>&1\n";
}
?>
