<?php
/**
 * Debug Authentication Issues
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "RingCentral Authentication Debug\n";
echo "===============================\n\n";

// Initialize client with credentials from config
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN,
    'teamChatId' => RINGCENTRAL_DEFAULT_CHAT_ID ?? RINGCENTRAL_TEAM_CHAT_ID ?? null
]);

// Create a directory for token storage if it doesn't exist
$tokenDir = __DIR__ . '/secure_storage';
if (!file_exists($tokenDir)) {
    mkdir($tokenDir, 0755, true);
    echo "Created token storage directory: $tokenDir\n";
}

// Debug output
echo "Client initialized with properties:\n";
echo "- Server URL: " . RINGCENTRAL_SERVER . "\n";
echo "- JWT Token (first 10 chars): " . substr(RINGCENTRAL_JWT_TOKEN, 0, 10) . "...\n";
echo "- Team Chat ID: " . (RINGCENTRAL_DEFAULT_CHAT_ID ?? RINGCENTRAL_TEAM_CHAT_ID ?? 'Not set') . "\n\n";

// Test hasValidToken method directly
echo "Checking for valid token...\n";
try {
    // Use reflection to access private methods (for debugging only)
    $reflectionClass = new ReflectionClass(RingCentralTeamMessagingClient::class);
    
    $hasValidTokenMethod = $reflectionClass->getMethod('hasValidToken');
    $hasValidTokenMethod->setAccessible(true);
    
    $hasValidToken = $hasValidTokenMethod->invoke($client);
    echo "hasValidToken result: " . ($hasValidToken ? 'true' : 'false') . "\n\n";
} catch (Exception $e) {
    echo "ERROR accessing hasValidToken: " . $e->getMessage() . "\n\n";
}

// Test authenticate method directly
echo "Testing authenticate method...\n";
try {
    // Use reflection for private methods
    $authenticateMethod = $reflectionClass->getMethod('authenticate');
    $authenticateMethod->setAccessible(true);
    
    $authenticated = $authenticateMethod->invoke($client);
    echo "authenticate result: " . ($authenticated ? 'true' : 'false') . "\n\n";
} catch (Exception $e) {
    echo "ERROR calling authenticate: " . $e->getMessage() . "\n\n";
}

// Check public isAuthenticated method
echo "Testing isAuthenticated method...\n";
try {
    $authenticated = $client->isAuthenticated();
    echo "isAuthenticated result: " . ($authenticated ? 'true' : 'false') . "\n\n";
} catch (Exception $e) {
    echo "ERROR calling isAuthenticated: " . $e->getMessage() . "\n\n";
}

// Now try a simple API call
echo "Testing listChats API call...\n";
try {
    $chats = $client->listChats('Team');
    if ($chats && isset($chats['records'])) {
        $count = count($chats['records']);
        echo "SUCCESS: Retrieved $count team chats\n";
    } else {
        echo "FAILED: No chats retrieved\n";
    }
} catch (Exception $e) {
    echo "ERROR making API call: " . $e->getMessage() . "\n\n";
}

echo "\nDone with debugging.\n";
?>
