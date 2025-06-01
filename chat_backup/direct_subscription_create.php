<?php
/**
 * Direct RingCentral Subscription Creation
 * 
 * This is a simplified, direct script to create a RingCentral subscription
 * without any form handling or complex logic.
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set content type to plaintext for easy debugging
header('Content-Type: text/plain');

// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DIRECT RINGCENTRAL SUBSCRIPTION CREATION ===\n\n";

// Track start time
$startTime = microtime(true);

// Display configuration check
echo "Configuration check:\n";
echo "- RINGCENTRAL_CLIENT_ID: " . (defined('RINGCENTRAL_CLIENT_ID') ? "OK" : "MISSING") . "\n";
echo "- RINGCENTRAL_CLIENT_SECRET: " . (defined('RINGCENTRAL_CLIENT_SECRET') ? "OK" : "MISSING") . "\n";
echo "- RINGCENTRAL_JWT_TOKEN: " . (defined('RINGCENTRAL_JWT_TOKEN') ? "OK" : "MISSING") . "\n";
echo "- RINGCENTRAL_SERVER: " . (defined('RINGCENTRAL_SERVER') ? "OK" : "MISSING") . "\n";
echo "\n";

try {
    // Step 1: Direct JWT authentication
    echo "Step 1: JWT Authentication\n";
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => RINGCENTRAL_JWT_TOKEN
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "Authentication Response HTTP Code: $httpCode\n";
    
    if (!empty($curlError)) {
        throw new Exception("CURL Error: $curlError");
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Authentication failed. HTTP Code: $httpCode, Response: $response");
    }
    
    $authData = json_decode($response, true);
    if (!isset($authData['access_token'])) {
        throw new Exception("No access token in response: $response");
    }
    
    $accessToken = $authData['access_token'];
    echo "Authentication successful. Access token: " . substr($accessToken, 0, 10) . "...\n\n";
    
    // Step 2: Create Subscription
    echo "Step 2: Creating Subscription\n";
    
    // Determine domain
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'theangelstones.com';
    $notificationUrl = 'https://' . $domain . '/chat/api/webhook.php';
    
    echo "Notification URL: $notificationUrl\n";
    $expiresIn = 7776000; // 90 days
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/subscription';
    
    // Prepare the request according to RingCentral documentation
    $subscriptionData = [
        // Use specific event filters for Glip/Team Messaging posts
        'eventFilters' => [
            "/restapi/v1.0/glip/posts",
            // Add subscription notification filter to be alerted before expiration
            // This will alert us when the subscription is about to expire
            "/restapi/v1.0/subscription/~?threshold=86400&interval=3600"
        ],
        'deliveryMode' => [
            'transportType' => 'WebHook',
            'address' => $notificationUrl
        ],
        'expiresIn' => $expiresIn
    ];
    
    echo "Creating subscription with the following data:\n";
    echo json_encode($subscriptionData, JSON_PRETTY_PRINT) . "\n\n";
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "Subscription Creation HTTP Code: $httpCode\n";
    
    if (!empty($curlError)) {
        throw new Exception("CURL Error during subscription creation: $curlError");
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Subscription creation failed. HTTP Code: $httpCode, Response: $response");
    }
    
    $subscriptionResponse = json_decode($response, true);
    echo "Subscription created successfully!\n";
    echo "- Subscription ID: " . ($subscriptionResponse['id'] ?? 'Unknown') . "\n";
    echo "- Expires at: " . ($subscriptionResponse['expirationTime'] ?? 'Unknown') . "\n";
    echo "- Status: " . ($subscriptionResponse['status'] ?? 'Unknown') . "\n\n";
    
    echo "Full subscription response:\n";
    echo json_encode($subscriptionResponse, JSON_PRETTY_PRINT) . "\n\n";
    
    // Step 3: Verify Subscription
    echo "Step 3: Verifying Subscription\n";
    
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
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $verifyData = json_decode($response, true);
        $subscriptions = $verifyData['records'] ?? [];
        
        echo "Verification successful. Found " . count($subscriptions) . " active subscriptions.\n";
        
        if (count($subscriptions) > 0) {
            foreach ($subscriptions as $index => $sub) {
                echo "Subscription #" . ($index + 1) . ":\n";
                echo "- ID: " . ($sub['id'] ?? 'N/A') . "\n";
                echo "- Status: " . ($sub['status'] ?? 'N/A') . "\n";
                echo "- Expiration: " . ($sub['expirationTime'] ?? 'N/A') . "\n";
                
                $deliveryMode = $sub['deliveryMode'] ?? [];
                echo "- Webhook URL: " . ($deliveryMode['address'] ?? 'N/A') . "\n\n";
            }
        }
    } else {
        echo "Warning: Could not verify subscriptions. HTTP Code: $httpCode\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Track execution time
$endTime = microtime(true);
$executionTime = ($endTime - $startTime);

echo "Script completed in $executionTime seconds.\n";
echo "=== END OF REPORT ===\n";
?>
