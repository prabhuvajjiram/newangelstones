<?php
/**
 * Direct Authentication Fix for RingCentral
 * 
 * This script fixes authentication by obtaining a token directly and making it accessible
 * to the RingCentralTeamMessagingClient class
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "RingCentral Direct Authentication Fix\n";
echo "===================================\n\n";

// Include the config file to get credentials
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Check if required constants are defined
if (!defined('RINGCENTRAL_JWT_TOKEN') || !defined('RINGCENTRAL_CLIENT_ID') || !defined('RINGCENTRAL_CLIENT_SECRET')) {
    die("Error: Required RingCentral credentials are not defined in config.php\n");
}

// Ensure secure storage directory exists
$storageDir = __DIR__ . '/secure_storage';
if (!is_dir($storageDir)) {
    if (!mkdir($storageDir, 0755, true)) {
        die("Error: Failed to create secure storage directory\n");
    }
}

// Direct authentication function
function authenticateWithRingCentral() {
    $serverUrl = RINGCENTRAL_SERVER;
    $endpoint = $serverUrl . '/restapi/oauth/token';
    
    // Prepare request data
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => RINGCENTRAL_JWT_TOKEN
    ];
    
    // Set up CURL request
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
        'X-RingCentral-API-Group: medium'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    echo "Authenticating with RingCentral...\n";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        die("Error: CURL failed with error - $error\n");
    }
    
    curl_close($ch);
    
    // Process response
    $jsonResponse = json_decode($response, true);
    if ($httpCode != 200 || !$jsonResponse || !isset($jsonResponse['access_token'])) {
        echo "Error: Authentication failed. HTTP code: $httpCode\n";
        echo "Response: " . print_r($jsonResponse, true) . "\n";
        return false;
    }
    
    // Save token data
    $tokenData = [
        'access_token' => $jsonResponse['access_token'],
        'refresh_token' => $jsonResponse['refresh_token'] ?? '',
        'expires_at' => time() + ($jsonResponse['expires_in'] ?? 3600),
        'token_type' => $jsonResponse['token_type'] ?? 'bearer'
    ];
    
    return $tokenData;
}

// Get token data
$tokenData = authenticateWithRingCentral();
if (!$tokenData) {
    die("Error: Failed to authenticate with RingCentral\n");
}

echo "Authentication successful!\n";
echo "Access token: " . substr($tokenData['access_token'], 0, 10) . "...\n";
echo "Expires at: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . "\n\n";

// Save token to all possible locations
$tokenPaths = [
    __DIR__ . '/.ringcentral_token.json',
    __DIR__ . '/secure_storage/rc_token.json',
    __DIR__ . '/secure_storage/ringcentral_token.json'
];

foreach ($tokenPaths as $path) {
    if (file_put_contents($path, json_encode($tokenData, JSON_PRETTY_PRINT))) {
        echo "Saved token to: $path\n";
    } else {
        echo "Failed to save token to: $path\n";
    }
}

// Create a simple verification script
$verifyScript = <<<'EOT'
<?php
/**
 * Verify RingCentral Authentication
 */

// Include necessary files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

echo "RingCentral Authentication Verification\n";
echo "=====================================\n\n";

// Load token directly
$tokenPath = __DIR__ . '/.ringcentral_token.json';
if (!file_exists($tokenPath)) {
    die("Error: Token file not found at $tokenPath\n");
}

$tokenData = json_decode(file_get_contents($tokenPath), true);
if (!$tokenData || !isset($tokenData['access_token'])) {
    die("Error: Invalid token format in $tokenPath\n");
}

// Display token information
echo "Access token: " . substr($tokenData['access_token'], 0, 10) . "...\n";
echo "Expires at: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . "\n";
if (time() > $tokenData['expires_at']) {
    echo "WARNING: Token has expired!\n";
} else {
    echo "Token is valid for " . ($tokenData['expires_at'] - time()) . " more seconds\n";
}

// Make a direct API call using the token
$endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/teams';
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenData['access_token'],
    'Content-Type: application/json',
    'X-RingCentral-API-Group: medium'
]);

echo "\nTesting API call to list teams...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse && isset($jsonResponse['records'])) {
        $count = count($jsonResponse['records']);
        echo "Success! Retrieved $count teams\n";
        
        if ($count > 0) {
            $firstTeam = $jsonResponse['records'][0];
            echo "First team: " . ($firstTeam['name'] ?? 'unknown') . " (ID: " . ($firstTeam['id'] ?? 'unknown') . ")\n";
        }
    } else {
        echo "API call succeeded but returned unexpected format\n";
    }
} else {
    echo "API call failed with HTTP code $httpCode\n";
    echo "Response: $response\n";
}

echo "\nVerification completed.\n";
EOT;

file_put_contents(__DIR__ . '/verify_direct_auth.php', $verifyScript);
echo "\nCreated verification script: verify_direct_auth.php\n";
echo "Run it to verify direct API access is working.\n";

// Create a fix script for RingCentralTeamMessagingClient.php
$fixScript = <<<'EOT'
<?php
/**
 * Fix RingCentralTeamMessagingClient.php
 */

// Define the class file path
$classFile = __DIR__ . '/RingCentralTeamMessagingClient.php';
if (!file_exists($classFile)) {
    die("Error: RingCentralTeamMessagingClient.php not found\n");
}

// Create a backup
$backupFile = $classFile . '.bak.' . date('YmdHis');
if (!copy($classFile, $backupFile)) {
    die("Error: Failed to create backup file\n");
}

echo "Created backup at: $backupFile\n";

// Read the file content
$content = file_get_contents($classFile);

// Fix duplicate apiGroup property
$pattern = '/\/\/ API Group setting\s+private \$apiGroup = \'medium\';.*\/\/ API Group setting\s+private \$apiGroup = \'medium\';/s';
$replacement = "// API Group setting\n    private \$apiGroup = 'medium';";
$content = preg_replace($pattern, $replacement, $content);

// Add isAuthenticated method if missing
if (strpos($content, 'function isAuthenticated') === false) {
    $pattern = '/public function getAccessToken\(\) \{.*?return null;\s+\}/s';
    $replacement = "public function getAccessToken() {
        if (\$this->authenticate()) {
            return \$this->accessToken;
        }
        return null;
    }
    
    /**
     * Check if client is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated() {
        return \$this->hasValidToken() || \$this->authenticate();
    }";
    
    $content = preg_replace($pattern, $replacement, $content);
}

// Fix hasValidToken method
$pattern = '/private function hasValidToken\(\) \{.*?return true;\s+\}/s';
$replacement = "private function hasValidToken() {
        if (empty(\$this->accessToken)) {
            return false;
        }
        
        // Check if token is expired
        if (\$this->tokenExpiresAt && \$this->tokenExpiresAt < time()) {
            return false;
        }
        
        return true;
    }";

$content = preg_replace($pattern, $replacement, $content);

// Fix loadToken method
$pattern = '/private function loadToken\(\) \{.*?return true;\s+\}/s';
$replacement = "private function loadToken() {
        if (!file_exists(\$this->tokenPath)) {
            return false;
        }
        
        \$tokenData = json_decode(file_get_contents(\$this->tokenPath), true);
        if (!is_array(\$tokenData)) {
            return false;
        }
        
        \$this->accessToken = \$tokenData['access_token'] ?? '';
        \$this->refreshToken = \$tokenData['refresh_token'] ?? '';
        \$this->tokenExpiresAt = \$tokenData['expires_at'] ?? 0;
        
        if (\$this->enableDebug) {
            \$this->log('Loaded token, expires at: ' . date('Y-m-d H:i:s', \$this->tokenExpiresAt));
        }
        
        return !empty(\$this->accessToken);
    }";

$content = preg_replace($pattern, $replacement, $content);

// Save the updated content
if (file_put_contents($classFile, $content) === false) {
    die("Error: Failed to write to class file\n");
}

echo "Successfully fixed RingCentralTeamMessagingClient.php\n";
EOT;

file_put_contents(__DIR__ . '/fix_client_class.php', $fixScript);
echo "\nCreated class fix script: fix_client_class.php\n";
echo "Run it to fix the RingCentralTeamMessagingClient class.\n";

echo "\nDirect authentication completed. Use the following steps to complete the fix:\n";
echo "1. Run: php chat/fix_client_class.php\n";
echo "2. Run: php chat/verify_direct_auth.php\n";
?>
