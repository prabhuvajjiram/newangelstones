<?php
/**
 * RingCentral Subscription Auto-Renewal
 * 
 * This script is designed to be run as a scheduled task (cron job)
 * to automatically renew the RingCentral subscription before it expires.
 * 
 * Recommended schedule: Once every 80 days
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Set log file
$logFile = __DIR__ . '/subscription_renewal.log';

// Simple logging function
function logMessage($message) {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    $logLine = $timestamp . ' ' . $message . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND);
    
    // Check if running from CLI (scheduled task)
    if (php_sapi_name() == 'cli') {
        echo $logLine;
    }
}

// Format output for HTML or CLI
if (php_sapi_name() != 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>RingCentral Subscription Renewal</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.5; }
            .success { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>RingCentral Subscription Renewal</h1>
        <div id="output">
    ';
}

logMessage("Starting subscription renewal process");

try {
    // Initialize the client
    $client = new RingCentralTeamMessagingClient();
    
    // Enable detailed debugging
    $client->enableDebug = true;
    
    if (!$client->isAuthenticated()) {
        // Get detailed authentication errors
        $authErrors = $client->getAuthErrors();
        $errorDetail = !empty($authErrors) ? json_encode($authErrors) : 'No specific error details available';
        throw new Exception("RingCentral client failed to authenticate. Error details: {$errorDetail}");
    }
    
    logMessage("Successfully authenticated with RingCentral");
    
    // Get access token
    $accessToken = $client->getAccessToken();
    
    // Check for existing subscriptions first
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
    
    if ($httpCode == 200) {
        $subscriptions = json_decode($response, true);
        logMessage("Found " . count($subscriptions['records']) . " existing subscriptions");
        
        $needToCreate = true;
        $notificationUrl = 'https://theangelstones.com/chat/api/webhook.php';
        
        // Check existing subscriptions
        foreach ($subscriptions['records'] as $subscription) {
            $deliveryMode = $subscription['deliveryMode'] ?? [];
            $existingUrl = $deliveryMode['address'] ?? '';
            
            if ($existingUrl == $notificationUrl) {
                // Found our subscription
                $subscriptionId = $subscription['id'];
                $expiresIn = 7776000; // Maximum allowed: 90 days
                
                logMessage("Found our subscription (ID: {$subscriptionId}). Renewing it.");
                
                // Renew by updating expiration time
                $ch = curl_init($endpoint . '/' . $subscriptionId);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]);
                
                $putData = [
                    'eventFilters' => ["/restapi/v1.0/glip/posts"],
                    'expiresIn' => $expiresIn
                ];
                
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($putData));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                
                $renewResponse = curl_exec($ch);
                $renewHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($renewHttpCode >= 200 && $renewHttpCode < 300) {
                    $renewData = json_decode($renewResponse, true);
                    logMessage("Successfully renewed subscription until: " . ($renewData['expirationTime'] ?? 'Unknown'));
                    $needToCreate = false;
                } else {
                    logMessage("Failed to renew subscription (HTTP {$renewHttpCode}). Will create a new one.");
                }
                
                break;
            }
        }
        
        // Create new subscription if needed
        if ($needToCreate) {
            logMessage("No matching subscription found. Creating a new one.");
            
            // Set subscription parameters
            $deliveryMode = 'WebHook';
            $expiresIn = 7776000; // Maximum allowed: 90 days
            
            // Create the subscription
            $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/subscription';
            
            // Prepare the request
            $data = [
                'eventFilters' => ["/restapi/v1.0/glip/posts"],
                'deliveryMode' => [
                    'transportType' => $deliveryMode,
                    'address' => $notificationUrl
                ],
                'expiresIn' => $expiresIn
            ];
            
            // Send the request
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $subscriptionData = json_decode($response, true);
                logMessage("Successfully created subscription with ID: " . ($subscriptionData['id'] ?? 'Unknown'));
                logMessage("Expires at: " . ($subscriptionData['expirationTime'] ?? 'Unknown'));
            } else {
                $errorData = json_decode($response, true);
                throw new Exception("Failed to create subscription. HTTP Code: {$httpCode}. Error: " . json_encode($errorData));
            }
        }
    } else {
        throw new Exception("Failed to retrieve existing subscriptions. HTTP Code: {$httpCode}");
    }
    
    logMessage("Subscription renewal process completed successfully");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    
    if (php_sapi_name() != 'cli') {
        echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
    }
}

// Format output for HTML
if (php_sapi_name() != 'cli') {
    echo '<h3>Log Output</h3>';
    echo '<pre>';
    if (file_exists($logFile)) {
        echo htmlspecialchars(file_get_contents($logFile));
    } else {
        echo 'No log file found.';
    }
    echo '</pre>';
    
    echo '</div>
    <p><a href="create_subscription.php">Back to Subscription Creation</a></p>
    </body>
    </html>';
}
?>
