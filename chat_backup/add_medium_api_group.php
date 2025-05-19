<?php
/**
 * RingCentral API Group Updater
 * 
 * This script adds the X-RingCentral-API-Group: medium header to all API requests
 * to increase the rate limit from 10 requests/min to 40 requests/min
 */

define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Set up logging
$logFile = __DIR__ . '/api_group_update.log';

function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, $timestamp . ' [' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

// Check if this is a CLI run or web run
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>RingCentral API Group Update</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1 { color: #0067b8; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
            .success { color: green; }
            .error { color: red; }
            code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <h1>RingCentral API Group Update</h1>
        <p>This utility adds the <code>X-RingCentral-API-Group: medium</code> header to all API requests to increase the rate limit from 10 requests/min to 40 requests/min.</p>
        <h2>Progress:</h2>
        <pre>';
}

logMessage('Starting API Group update');
echo "RingCentral API Group Update\n";
echo "===========================\n\n";

// Original file path
$originalFile = __DIR__ . '/RingCentralTeamMessagingClient.php';
$backupFile = __DIR__ . '/RingCentralTeamMessagingClient.php.bak.' . date('YmdHis');

// Verify the original file exists
if (!file_exists($originalFile)) {
    logMessage("Original file not found: $originalFile", 'ERROR');
    echo "ERROR: Original file not found: $originalFile\n";
    exit(1);
}

// Create backup
if (copy($originalFile, $backupFile)) {
    logMessage("Created backup at: $backupFile");
    echo "✓ Created backup at: $backupFile\n";
} else {
    logMessage("Failed to create backup", 'ERROR');
    echo "ERROR: Failed to create backup\n";
    exit(1);
}

// Read the file content
$content = file_get_contents($originalFile);
if ($content === false) {
    logMessage("Failed to read original file", 'ERROR');
    echo "ERROR: Failed to read original file\n";
    exit(1);
}

// Add API Group property to the class if it doesn't exist
$classPropertyPattern = '/class RingCentralTeamMessagingClient \{\s+private \$accessToken;.*?public \$enableDebug = false;/s';
$classPropertyReplacement = "class RingCentralTeamMessagingClient {\n    private \$accessToken;\n    private \$refreshToken;\n    private \$tokenExpiresAt;\n    private \$clientId;\n    private \$clientSecret;\n    private \$username;\n    private \$password;\n    private \$extension;\n    private \$jwtToken; // JWT token for authentication\n    private \$serverUrl;\n    private \$tokenPath;\n    private \$logFile;\n    private \$teamChatId; // Default team chat ID for sending messages\n    \n    // Error tracking and debugging\n    private \$lastError = '';\n    private \$authErrors = [];\n    private \$lastResponse = '';\n    private \$lastHttpCode = 0;\n    public \$enableDebug = false; // Enable detailed debugging\n    \n    // API Group setting\n    private \$apiGroup = 'medium'; // Using medium group (40 requests/min) instead of heavy (10 requests/min)";

$updatedContent = preg_replace($classPropertyPattern, $classPropertyReplacement, $content);

// Add the getStandardHeaders method after the constructor
$constructorPattern = '/public function __construct\(array \$config = \[\]\) \{.*?this->loadToken\(\);\s+\}/s';
$constructorReplacement = "public function __construct(array \$config = []) {
        // Set default server URL if not specified
        \$this->serverUrl = \$config['serverUrl'] ?? 'https://platform.ringcentral.com';
        
        // Set credentials
        \$this->clientId = \$config['clientId'] ?? '';
        \$this->clientSecret = \$config['clientSecret'] ?? '';
        \$this->username = \$config['username'] ?? '';
        \$this->password = \$config['password'] ?? '';
        \$this->extension = \$config['extension'] ?? '';
        \$this->jwtToken = \$config['jwtToken'] ?? ''; // JWT token for authentication
        
        // Optional configurations
        \$this->tokenPath = \$config['tokenPath'] ?? __DIR__ . '/.ringcentral_token.json';
        \$this->logFile = \$config['logFile'] ?? __DIR__ . '/ringcentral_chat.log';
        \$this->teamChatId = \$config['teamChatId'] ?? null; // Default chat ID to post to
        
        // Set API group if specified
        \$this->apiGroup = \$config['apiGroup'] ?? \$this->apiGroup;
        
        // Load token if exists
        \$this->loadToken();
    }
    
    /**
     * Get standard headers for API requests including the medium API group
     * 
     * @param bool \$includeAuth Whether to include Authorization header
     * @return array Array of headers
     */
    private function getStandardHeaders(\$includeAuth = true) {
        \$headers = [
            'X-RingCentral-API-Group: ' . \$this->apiGroup
        ];
        
        if (\$includeAuth && \$this->accessToken) {
            \$headers[] = 'Authorization: Bearer ' . \$this->accessToken;
        }
        
        return \$headers;
    }";

$updatedContent = preg_replace($constructorPattern, $constructorReplacement, $updatedContent);

// Update all API calls to include the API group header
$curlPatterns = [
    // 1. JWT Authentication
    '/curl_setopt\(\$ch, CURLOPT_HTTPHEADER, \[\s+\'Authorization: Basic \' \. base64_encode\(\$this->clientId \. \':\' \. \$this->clientSecret\),\s+\'Content-Type: application\/x-www-form-urlencoded\'\s+\]\);/s' 
        => "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode(\$this->clientId . ':' . \$this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
            'X-RingCentral-API-Group: ' . \$this->apiGroup
        ]);",
    
    // 2. Password Authentication
    '/curl_setopt\(\$ch, CURLOPT_HTTPHEADER, \[\s+\'Authorization: Basic \' \. base64_encode\(\$this->clientId \. \':\' \. \$this->clientSecret\),\s+\'Content-Type: application\/x-www-form-urlencoded\'\s+\]\);/s' 
        => "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode(\$this->clientId . ':' . \$this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
            'X-RingCentral-API-Group: ' . \$this->apiGroup
        ]);",
    
    // 3. List Chats API
    '/curl_setopt\(\$ch, CURLOPT_HTTPHEADER, \[\s+\'Authorization: Bearer \' \. \$this->accessToken\s+\]\);/s' 
        => "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . \$this->accessToken,
            'X-RingCentral-API-Group: ' . \$this->apiGroup
        ]);",
    
    // 4. Post Message API
    '/curl_setopt\(\$ch, CURLOPT_HTTPHEADER, \[\s+\'Authorization: Bearer \' \. \$this->accessToken,\s+\'Content-Type: application\/json\'\s+\]\);/s' 
        => "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . \$this->accessToken,
            'Content-Type: application/json',
            'X-RingCentral-API-Group: ' . \$this->apiGroup
        ]);"
];

$updatedCount = 0;
foreach ($curlPatterns as $pattern => $replacement) {
    $count = 0;
    $updatedContent = preg_replace($pattern, $replacement, $updatedContent, -1, $count);
    $updatedCount += $count;
}

// Write the updated content back to the file
if (file_put_contents($originalFile, $updatedContent)) {
    logMessage("Successfully updated file with API Group header");
    echo "✓ Successfully updated file with API Group header\n";
    echo "✓ API calls updated: $updatedCount\n";
} else {
    logMessage("Failed to write updated file", 'ERROR');
    echo "ERROR: Failed to write updated file\n";
    exit(1);
}

// Print usage instructions
echo "\nInstructions:\n";
echo "1. The RingCentral client now uses the 'medium' API group\n";
echo "2. This increases your rate limit from 10 requests/min to 40 requests/min\n";
echo "3. A backup of your original file was created at: $backupFile\n";
echo "4. If you experience any issues, you can restore the backup\n";

if (!$isCli) {
    echo '</pre>
    <h2>Instructions:</h2>
    <ul>
        <li>The RingCentral client now uses the \'medium\' API group</li>
        <li>This increases your rate limit from 10 requests/min to 40 requests/min</li>
        <li>A backup of your original file was created at: ' . htmlspecialchars($backupFile) . '</li>
        <li>If you experience any issues, you can restore the backup</li>
    </ul>
    </body>
    </html>';
}

logMessage('API Group update completed successfully');
echo "\nDone!\n";
