<?php
/**
 * Debug RingCentralTeamMessagingClient
 * 
 * This script provides detailed debugging of the client operations
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Enable direct output
echo "RingCentral Client Debug\n";
echo "=======================\n\n";

// Create log function
function debug_log($message, $level = 'INFO') {
    $timestamp = date('[Y-m-d H:i:s]');
    echo "{$timestamp} [{$level}] {$message}\n";
    file_put_contents(__DIR__ . '/debug_client.log', "{$timestamp} [{$level}] {$message}\n", FILE_APPEND);
}

// Check the token file directly
$tokenPath = __DIR__ . '/.ringcentral_token.json';
debug_log("Checking token file: {$tokenPath}");

if (file_exists($tokenPath)) {
    $tokenData = json_decode(file_get_contents($tokenPath), true);
    debug_log("Token file exists, contains: " . print_r($tokenData, true));
    
    if (isset($tokenData['access_token'])) {
        debug_log("Access token: " . substr($tokenData['access_token'], 0, 10) . "...", 'TOKEN');
        debug_log("Expires at: " . date('Y-m-d H:i:s', $tokenData['expires_at']), 'TOKEN');
        
        if ($tokenData['expires_at'] < time()) {
            debug_log("Token is EXPIRED!", 'ERROR');
        } else {
            debug_log("Token is valid for " . ($tokenData['expires_at'] - time()) . " more seconds", 'TOKEN');
        }
    } else {
        debug_log("Token file does not contain access_token", 'ERROR');
    }
} else {
    debug_log("Token file does not exist!", 'ERROR');
}

// Create a client with debugging enabled
debug_log("Creating RingCentralTeamMessagingClient instance");
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN,
    'tokenPath' => $tokenPath,
    'logFile' => __DIR__ . '/debug_client.log'
]);
$client->enableDebug = true;

// Check client properties using Reflection
debug_log("Inspecting client properties with Reflection");
$reflection = new ReflectionClass($client);
$props = $reflection->getProperties();

foreach ($props as $prop) {
    $prop->setAccessible(true);
    $name = $prop->getName();
    $value = $prop->getValue($client);
    
    // Skip logging the actual tokens
    if (in_array($name, ['accessToken', 'refreshToken', 'jwtToken', 'clientSecret'])) {
        debug_log("Property {$name}: " . (empty($value) ? 'EMPTY' : substr($value, 0, 5) . '...'), 'PROP');
    } else {
        if (is_array($value) || is_object($value)) {
            debug_log("Property {$name}: " . print_r($value, true), 'PROP');
        } else {
            debug_log("Property {$name}: {$value}", 'PROP');
        }
    }
}

// Test direct API access using the token from the file
if (isset($tokenData['access_token'])) {
    debug_log("Testing direct API access with token");
    $accessToken = $tokenData['access_token'];
    
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/teams';
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
        'X-RingCentral-API-Group: medium'
    ]);
    
    debug_log("Sending direct API request to {$endpoint}");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    debug_log("HTTP Status: {$httpCode}");
    
    if ($httpCode == 200) {
        $jsonResponse = json_decode($response, true);
        if (isset($jsonResponse['records'])) {
            debug_log("API access successful, retrieved " . count($jsonResponse['records']) . " teams", 'SUCCESS');
        } else {
            debug_log("API access returned unexpected format: " . substr($response, 0, 100), 'WARNING');
        }
    } else {
        debug_log("API access failed: {$response}", 'ERROR');
    }
}

// Now test the client's isAuthenticated method
debug_log("\nTesting client isAuthenticated method");
try {
    $result = $client->isAuthenticated();
    debug_log("isAuthenticated result: " . ($result ? 'true' : 'false'));
    
    if ($result) {
        debug_log("Authentication successful!", 'SUCCESS');
        
        // Test getAccessToken
        debug_log("Testing getAccessToken method");
        $token = $client->getAccessToken();
        debug_log("getAccessToken result: " . (empty($token) ? 'EMPTY' : substr($token, 0, 10) . '...'));
        
        // Test listChats
        debug_log("\nTesting listChats method");
        $chats = $client->listChats('Team');
        
        if (isset($chats['records'])) {
            debug_log("listChats successful, retrieved " . count($chats['records']) . " chats", 'SUCCESS');
            
            if (count($chats['records']) > 0) {
                debug_log("First chat ID: " . $chats['records'][0]['id']);
                
                // Test posting a message
                $chatId = $chats['records'][0]['id'];
                debug_log("\nTesting postMessage method");
                $message = "Test message from debug_client.php at " . date('Y-m-d H:i:s');
                $postResult = $client->postMessage($chatId, $message);
                
                if (isset($postResult['id'])) {
                    debug_log("postMessage successful, message ID: " . $postResult['id'], 'SUCCESS');
                } else {
                    debug_log("postMessage failed: " . print_r($postResult, true), 'ERROR');
                }
            }
        } else {
            debug_log("listChats failed: " . print_r($chats, true), 'ERROR');
        }
    } else {
        debug_log("Authentication failed!", 'ERROR');
        
        // Try to get the last error
        $lastErrorProp = $reflection->getProperty('lastError');
        $lastErrorProp->setAccessible(true);
        $lastError = $lastErrorProp->getValue($client);
        
        if (!empty($lastError)) {
            debug_log("Last error: {$lastError}", 'ERROR');
        }
        
        // Try to get the auth errors array
        $authErrorsProp = $reflection->getProperty('authErrors');
        $authErrorsProp->setAccessible(true);
        $authErrors = $authErrorsProp->getValue($client);
        
        if (!empty($authErrors)) {
            debug_log("Auth errors: " . print_r($authErrors, true), 'ERROR');
        }
    }
} catch (Exception $e) {
    debug_log("Exception: " . $e->getMessage(), 'ERROR');
    debug_log($e->getTraceAsString(), 'ERROR');
}

debug_log("\nDebugging complete");
?>
