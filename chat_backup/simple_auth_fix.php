<?php
/**
 * Simple Authentication Fix for RingCentral
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Function to directly authenticate with RingCentral
function authenticate_with_ringcentral() {
    $endpoint = RINGCENTRAL_SERVER . '/restapi/oauth/token';
    
    // Prepare request
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => RINGCENTRAL_JWT_TOKEN
    ];
    
    // Prepare CURL
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(RINGCENTRAL_CLIENT_ID . ':' . RINGCENTRAL_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
        'X-RingCentral-API-Group: medium'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check response
    if ($response === false) {
        echo "CURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $jsonResponse = json_decode($response, true);
    if ($httpCode != 200 || !isset($jsonResponse['access_token'])) {
        echo "Authentication failed with HTTP code $httpCode\n";
        echo "Response: " . print_r($jsonResponse, true) . "\n";
        return false;
    }
    
    return [
        'access_token' => $jsonResponse['access_token'],
        'refresh_token' => $jsonResponse['refresh_token'] ?? '',
        'expires_at' => time() + ($jsonResponse['expires_in'] ?? 3600),
        'token_type' => $jsonResponse['token_type'] ?? 'bearer'
    ];
}

// Simple function to fix the RingCentralTeamMessagingClient.php file
function fix_client_class() {
    // File path to the class
    $filePath = __DIR__ . '/RingCentralTeamMessagingClient.php';
    
    // Check if file exists
    if (!file_exists($filePath)) {
        echo "Error: RingCentralTeamMessagingClient.php not found\n";
        return false;
    }
    
    // Create backup
    $backupPath = $filePath . '.bak.' . date('YmdHis');
    if (!copy($filePath, $backupPath)) {
        echo "Error: Failed to create backup\n";
        return false;
    }
    
    echo "Created backup at: $backupPath\n";
    
    // Read file content
    $content = file_get_contents($filePath);
    
    // Fix 1: Remove duplicate apiGroup property if present
    $matches = [];
    preg_match_all('/private \$apiGroup = \'medium\';/', $content, $matches);
    if (count($matches[0]) > 1) {
        echo "Fixing duplicate apiGroup property\n";
        $content = preg_replace('/(\/\/ API Group setting\s+private \$apiGroup = \'medium\';).*?(\/\/ API Group setting\s+private \$apiGroup = \'medium\';)/s', '$1', $content);
    }
    
    // Fix 2: Add isAuthenticated method if not present
    if (strpos($content, 'function isAuthenticated') === false) {
        echo "Adding isAuthenticated method\n";
        $content = str_replace(
            "public function getAccessToken() {
        if (\$this->authenticate()) {
            return \$this->accessToken;
        }
        return null;
    }",
            "public function getAccessToken() {
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
    }",
            $content
        );
    }
    
    // Write fixed content back to file
    if (file_put_contents($filePath, $content) === false) {
        echo "Error: Failed to write fixed content to file\n";
        return false;
    }
    
    echo "Fixed RingCentralTeamMessagingClient.php successfully\n";
    return true;
}

// Create secure storage directory if it doesn't exist
$storageDir = __DIR__ . '/secure_storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
    echo "Created secure_storage directory\n";
}

// Run authentication and fix the client class
echo "Simple Authentication Fix for RingCentral\n";
echo "====================================\n\n";

// Step 1: Fix the client class
echo "Step 1: Fixing RingCentralTeamMessagingClient class...\n";
if (fix_client_class()) {
    echo "Class fixed successfully\n\n";
} else {
    echo "Failed to fix class\n\n";
}

// Step 2: Get authentication token
echo "Step 2: Getting authentication token...\n";
$token = authenticate_with_ringcentral();
if ($token) {
    echo "Authentication successful\n";
    echo "Access token: " . substr($token['access_token'], 0, 10) . "...\n";
    echo "Expires at: " . date('Y-m-d H:i:s', $token['expires_at']) . "\n\n";
    
    // Save token to different locations
    $tokenPaths = [
        __DIR__ . '/.ringcentral_token.json',
        __DIR__ . '/secure_storage/rc_token.json'
    ];
    
    foreach ($tokenPaths as $path) {
        if (file_put_contents($path, json_encode($token, JSON_PRETTY_PRINT))) {
            echo "Saved token to: $path\n";
        } else {
            echo "Failed to save token to: $path\n";
        }
    }
    
    echo "\nFix completed successfully. The RingCentral integration should now work correctly.\n";
    
    // Test direct API call
    echo "\nTesting direct API call...\n";
    $endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/teams';
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token['access_token'],
        'Content-Type: application/json',
        'X-RingCentral-API-Group: medium'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $jsonResponse = json_decode($response, true);
        if (isset($jsonResponse['records'])) {
            echo "API test successful! Retrieved " . count($jsonResponse['records']) . " teams\n";
        } else {
            echo "API returned unexpected format\n";
        }
    } else {
        echo "API test failed with HTTP code $httpCode\n";
    }
} else {
    echo "Authentication failed\n";
}
?>
