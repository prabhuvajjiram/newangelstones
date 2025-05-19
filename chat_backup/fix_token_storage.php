<?php
/**
 * Fix Token Storage for RingCentral
 * 
 * This script transfers the authenticated token from our debug script to the standard token location
 * used by RingCentralTeamMessagingClient.php
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "RingCentral Token Storage Fix\n";
echo "============================\n\n";

// Check for the debug token
$debugTokenPath = __DIR__ . '/secure_storage/debug_token.json';
if (!file_exists($debugTokenPath)) {
    echo "Error: Debug token not found. Please run cli_jwt_debug.php first.\n";
    exit(1);
}

// Read the debug token
$debugToken = json_decode(file_get_contents($debugTokenPath), true);
if (!$debugToken || !isset($debugToken['access_token'])) {
    echo "Error: Invalid debug token format.\n";
    exit(1);
}

// Extract token details
$accessToken = $debugToken['access_token'];
$expiresIn = $debugToken['expires_in'] ?? 3600;
$tokenType = $debugToken['token_type'] ?? 'bearer';
$refreshToken = $debugToken['refresh_token'] ?? '';

// Create the standard token format expected by RingCentralTeamMessagingClient
$standardToken = [
    'access_token' => $accessToken,
    'refresh_token' => $refreshToken,
    'expires_at' => time() + $expiresIn,
    'token_type' => $tokenType
];

// Define possible token paths to update
$tokenPaths = [
    // Default token path in RingCentralTeamMessagingClient
    __DIR__ . '/.ringcentral_token.json',
    
    // Additional possible token paths
    __DIR__ . '/secure_storage/rc_token.json',
    __DIR__ . '/secure_storage/ringcentral_token.json'
];

// Write token to all possible locations
$success = false;
foreach ($tokenPaths as $tokenPath) {
    if (file_put_contents($tokenPath, json_encode($standardToken, JSON_PRETTY_PRINT))) {
        echo "✓ Saved token to: $tokenPath\n";
        $success = true;
    } else {
        echo "✗ Failed to save token to: $tokenPath\n";
    }
}

if ($success) {
    echo "\nSuccess! The token has been saved in the format expected by RingCentralTeamMessagingClient.\n";
    echo "Your authentication should now work correctly.\n";
} else {
    echo "\nError: Failed to save the token. Please check file permissions.\n";
}

// Now create a simple test script to verify the fix
$testScript = <<<'EOT'
<?php
/**
 * Test Token Storage Fix
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RingCentralTeamMessagingClient.php';

echo "RingCentral Token Test\n";
echo "====================\n\n";

// Create client
$client = new RingCentralTeamMessagingClient([
    'serverUrl' => RINGCENTRAL_SERVER,
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN
]);

// Check authentication
echo "Testing authentication...\n";
if ($client->isAuthenticated()) {
    echo "✓ Authentication successful!\n";
    
    // Get access token
    $token = $client->getAccessToken();
    if ($token) {
        echo "✓ Access token retrieved: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "✗ Failed to get access token\n";
    }
    
    // Try to list chats
    echo "\nTesting API call (listChats)...\n";
    try {
        $chats = $client->listChats('Team');
        if ($chats && isset($chats['records'])) {
            $count = count($chats['records']);
            echo "✓ Retrieved $count team chats\n";
            
            if ($count > 0) {
                $firstChat = $chats['records'][0];
                echo "  - ID: " . ($firstChat['id'] ?? 'unknown') . "\n";
                echo "  - Name: " . ($firstChat['name'] ?? 'unknown') . "\n";
            }
        } else {
            echo "✗ No chats found or invalid response\n";
        }
    } catch (Exception $e) {
        echo "✗ API call error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Authentication failed\n";
}

echo "\nTest completed.\n";
EOT;

file_put_contents(__DIR__ . '/test_token_fix.php', $testScript);
echo "\nCreated test script: test_token_fix.php\n";
echo "Run it with: php chat/test_token_fix.php\n";
?>
