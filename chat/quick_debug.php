<?php
/**
 * Quick Debugging Tool for RingCentral Production Issues
 * This minimal script provides just the essential diagnostics
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set error reporting for maximum visibility
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Output as plain text for ease of reading
header('Content-Type: text/plain');

echo "=== RINGCENTRAL QUICK DEBUG ===\n\n";

// Check configuration
echo "== Configuration Check ==\n";
$requiredConfigs = [
    'RINGCENTRAL_CLIENT_ID', 
    'RINGCENTRAL_CLIENT_SECRET', 
    'RINGCENTRAL_JWT_TOKEN', 
    'RINGCENTRAL_SERVER',
    'RINGCENTRAL_TEAM_CHAT_ID'
];

foreach ($requiredConfigs as $config) {
    echo "$config: " . (defined($config) && !empty(constant($config)) ? "OK" : "MISSING") . "\n";
}

// Test JWT Token
echo "\n== JWT Token Check ==\n";
if (defined('RINGCENTRAL_JWT_TOKEN') && !empty(RINGCENTRAL_JWT_TOKEN)) {
    $tokenParts = explode('.', RINGCENTRAL_JWT_TOKEN);
    
    if (count($tokenParts) !== 3) {
        echo "ERROR: Invalid JWT token format\n";
    } else {
        echo "Token format: VALID\n";
        
        // Decode payload to check expiration
        $payloadBase64 = $tokenParts[1];
        $payloadJSON = base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadBase64 . '=='));
        $payload = json_decode($payloadJSON, true);
        
        if (isset($payload['exp'])) {
            $expTime = (int)$payload['exp'];
            $currentTime = time();
            $diff = $expTime - $currentTime;
            
            echo "Expiration time: " . date('Y-m-d H:i:s', $expTime) . "\n";
            echo "Current time:    " . date('Y-m-d H:i:s', $currentTime) . "\n";
            
            if ($diff > 0) {
                echo "Status: VALID (expires in " . gmdate("H:i:s", $diff) . ")\n";
            } else {
                echo "Status: EXPIRED (" . gmdate("H:i:s", abs($diff)) . " ago)\n";
                echo "\nFIX: Generate a new JWT token and update the RINGCENTRAL_JWT_TOKEN value\n";
            }
        } else {
            echo "WARNING: Token has no expiration\n";
        }
    }
} else {
    echo "ERROR: JWT token is not defined\n";
}

// Test authentication (direct method to avoid dependencies)
echo "\n== Authentication Test ==\n";
try {
    // Direct JWT authentication
    $endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => RINGCENTRAL_JWT_TOKEN
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);  // Include headers in the output
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if (!empty($curlError)) {
        echo "CURL Error: $curlError\n";
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Status: SUCCESS\n";
        $data = json_decode($body, true);
        echo "Access token: " . substr($data['access_token'], 0, 10) . "...\n";
        echo "Expires in: " . $data['expires_in'] . " seconds\n";
    } else {
        echo "Status: FAILED\n";
        echo "Response: $body\n";
        
        $errorData = json_decode($body, true);
        if (isset($errorData['error'])) {
            echo "\nError type: " . $errorData['error'] . "\n";
            
            // Provide specific solutions based on error
            if ($errorData['error'] == 'invalid_client') {
                echo "\nFIX: Check your RINGCENTRAL_CLIENT_ID and RINGCENTRAL_CLIENT_SECRET values\n";
            } else if ($errorData['error'] == 'invalid_grant') {
                echo "\nFIX: Your JWT token is invalid or expired. Generate a new token.\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Simple subscription check
if ($httpCode >= 200 && $httpCode < 300) {
    echo "\n== Subscription Check ==\n";
    $data = json_decode($body, true);
    $accessToken = $data['access_token'];
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/subscription';
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if (!empty($curlError)) {
        echo "CURL Error: $curlError\n";
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $subscriptions = json_decode($response, true);
        $records = $subscriptions['records'] ?? [];
        echo "Active subscriptions: " . count($records) . "\n";
        
        if (count($records) > 0) {
            foreach ($records as $index => $sub) {
                echo "\nSubscription #" . ($index + 1) . ":\n";
                echo "  ID: " . ($sub['id'] ?? 'N/A') . "\n";
                echo "  Status: " . ($sub['status'] ?? 'N/A') . "\n";
                echo "  Expiration: " . ($sub['expirationTime'] ?? 'N/A') . "\n";
                
                $deliveryMode = $sub['deliveryMode'] ?? [];
                echo "  Webhook URL: " . ($deliveryMode['address'] ?? 'N/A') . "\n";
            }
        } else {
            echo "FIX: No subscriptions found. Create one using create_subscription.php\n";
        }
    } else {
        echo "Failed to check subscriptions: $response\n";
    }
}

echo "\n== System Information ==\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

echo "\n=== END DEBUG REPORT ===\n";
?>
