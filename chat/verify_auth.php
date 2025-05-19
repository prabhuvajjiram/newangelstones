<?php
/**
 * Verify RingCentral Authentication
 * With detailed error reporting
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERROR: [$errno] $errstr in $errfile on line $errline\n";
    return true;
});

// Define entry point
define('LOCAL_ENTRY_POINT', true);

// Include configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

echo "RingCentral Authentication Verification\n";
echo "=====================================\n\n";

echo "Configuration:\n";
echo "- Server URL: " . RINGCENTRAL_SERVER . "\n";
echo "- Client ID: " . (defined('RINGCENTRAL_CLIENT_ID') ? 'Set' : 'NOT SET') . "\n";
echo "- Client Secret: " . (defined('RINGCENTRAL_CLIENT_SECRET') ? 'Set' : 'NOT SET') . "\n";
echo "- JWT Token: " . (defined('RINGCENTRAL_JWT_TOKEN') && RINGCENTRAL_JWT_TOKEN ? substr(RINGCENTRAL_JWT_TOKEN, 0, 10) . '...' : 'NOT SET') . "\n\n";

// Create token storage directory if it doesn't exist
$tokenDir = __DIR__ . '/secure_storage';
if (!is_dir($tokenDir)) {
    if (mkdir($tokenDir, 0755, true)) {
        echo "Created token storage directory\n";
    } else {
        echo "Failed to create token storage directory\n";
    }
}

// Define a simple log function
function log_message($message) {
    echo $message . "\n";
    file_put_contents(__DIR__ . '/auth_test.log', date('[Y-m-d H:i:s]') . ' ' . $message . "\n", FILE_APPEND);
}

// Create client with debug enabled
try {
    log_message("Initializing RingCentral client...");
    $client = new RingCentralTeamMessagingClient([
        'serverUrl' => RINGCENTRAL_SERVER,
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'jwtToken' => RINGCENTRAL_JWT_TOKEN,
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json',
        'logFile' => __DIR__ . '/auth_test.log'
    ]);
    $client->enableDebug = true;
    log_message("Client initialized successfully");
} catch (Throwable $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

// Test authentication
log_message("\nTesting authentication...");
try {
    $authenticated = $client->isAuthenticated();
    log_message("Authentication result: " . ($authenticated ? 'SUCCESS' : 'FAILED'));
    
    if (!$authenticated) {
        // Try to get last error
        $refClass = new ReflectionClass($client);
        $lastErrorProp = $refClass->getProperty('lastError');
        $lastErrorProp->setAccessible(true);
        $lastError = $lastErrorProp->getValue($client);
        
        if ($lastError) {
            log_message("Authentication error: " . $lastError);
        } else {
            log_message("No error message available");
        }
    } else {
        // Try to get token
        $token = $client->getAccessToken();
        log_message("Access token: " . (empty($token) ? 'Empty' : substr($token, 0, 10) . '...'));
        
        // Try to make an API call
        log_message("\nTesting API call...");
        $chats = $client->listChats('Team');
        if (isset($chats['records'])) {
            $count = count($chats['records']);
            log_message("Retrieved $count team chats");
            
            if ($count > 0) {
                $firstChat = $chats['records'][0];
                log_message("First chat: " . ($firstChat['name'] ?? 'Unnamed') . ' (ID: ' . ($firstChat['id'] ?? 'unknown') . ')');
            }
        } else {
            log_message("Failed to retrieve chats data");
        }
    }
} catch (Throwable $e) {
    log_message("ERROR: " . $e->getMessage());
}

log_message("\nVerification completed.");