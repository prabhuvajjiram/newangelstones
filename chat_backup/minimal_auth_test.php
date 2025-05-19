<?php
/**
 * Minimal Authentication Test 
 * With detailed error reporting
 */

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

echo "Starting minimal authentication test...\n";

// First verify config constants
echo "Configuration check:\n";
echo "RINGCENTRAL_CLIENT_ID: " . (defined('RINGCENTRAL_CLIENT_ID') ? 'Defined' : 'NOT DEFINED') . "\n";
echo "RINGCENTRAL_CLIENT_SECRET: " . (defined('RINGCENTRAL_CLIENT_SECRET') ? 'Defined' : 'NOT DEFINED') . "\n";
echo "RINGCENTRAL_JWT_TOKEN: " . (defined('RINGCENTRAL_JWT_TOKEN') ? 'Defined (' . substr(RINGCENTRAL_JWT_TOKEN, 0, 10) . '...)' : 'NOT DEFINED') . "\n";
echo "RINGCENTRAL_SERVER: " . (defined('RINGCENTRAL_SERVER') ? RINGCENTRAL_SERVER : 'NOT DEFINED') . "\n";
echo "\n";

// Create the secure_storage directory if it doesn't exist
if (!is_dir(__DIR__ . '/secure_storage')) {
    echo "Creating secure_storage directory...\n";
    mkdir(__DIR__ . '/secure_storage', 0755, true);
}

// Check if RingCentralTeamMessagingClient class file exists
$clientFile = __DIR__ . '/RingCentralTeamMessagingClient.php';
echo "Client file check: " . (file_exists($clientFile) ? 'Exists' : 'MISSING') . "\n";

// Include the client class
require_once $clientFile;

// Try to instantiate the client class
echo "Creating client instance...\n";
try {
    $client = new RingCentralTeamMessagingClient([
        'serverUrl' => RINGCENTRAL_SERVER,
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json',
        'logFile' => __DIR__ . '/debug_auth.log'
    ]);
    echo "Client instantiated successfully\n";
} catch (Throwable $e) {
    echo "ERROR instantiating client: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Define a simple log method for debugging
function log_debug($message) {
    echo $message . "\n";
    file_put_contents(__DIR__ . '/debug_auth.log', date('[Y-m-d H:i:s]') . ' ' . $message . "\n", FILE_APPEND);
}

// Test authenticate method
echo "\nAttempting authentication...\n";
try {
    // Check if client has hasValidToken method
    echo "Checking if client has required methods:\n";
    echo "- authenticate: " . (method_exists($client, 'authenticate') ? 'Yes' : 'No') . "\n";
    echo "- isAuthenticated: " . (method_exists($client, 'isAuthenticated') ? 'Yes' : 'No') . "\n";
    echo "- getAccessToken: " . (method_exists($client, 'getAccessToken') ? 'Yes' : 'No') . "\n";
    
    // If we have the isAuthenticated method, call it
    if (method_exists($client, 'isAuthenticated')) {
        $authenticated = $client->isAuthenticated();
        echo "Authentication result: " . ($authenticated ? 'SUCCESS' : 'FAILED') . "\n";
        
        if ($authenticated) {
            // Get access token
            $token = $client->getAccessToken();
            echo "Access token: " . (empty($token) ? 'Empty' : substr($token, 0, 10) . '...') . "\n";
            
            // Try a simple API call
            echo "\nTesting API call...\n";
            $chats = $client->listChats('Team');
            $count = isset($chats['records']) ? count($chats['records']) : 0;
            echo "Retrieved $count team chats\n";
        }
    } else {
        echo "ERROR: isAuthenticated method doesn't exist\n";
        
        // Try directly calling authenticate
        if (method_exists($client, 'authenticate')) {
            echo "Trying direct authenticate method...\n";
            $authenticated = $client->authenticate();
            echo "Authentication result: " . ($authenticated ? 'SUCCESS' : 'FAILED') . "\n";
        }
    }
} catch (Throwable $e) {
    echo "ERROR during authentication: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";
?>
