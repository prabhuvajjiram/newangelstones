<?php
/**
 * Comprehensive Authentication Fix for RingCentral 
 */

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERROR: [$errno] $errstr in $errfile on line $errline\n";
    return true;
});

// Define output function with both console and file logging
function output($message, $level = 'INFO') {
    $logfile = __DIR__ . '/auth_fix.log';
    $formatted = date('[Y-m-d H:i:s]') . " [$level] $message\n";
    echo $formatted;
    file_put_contents($logfile, $formatted, FILE_APPEND);
}

output("RingCentral Authentication Fix", "START");
output("===========================");

// Check and create secure storage directory
$secureDir = __DIR__ . '/secure_storage';
if (!is_dir($secureDir)) {
    if (mkdir($secureDir, 0755, true)) {
        output("Created secure_storage directory", "SUCCESS");
    } else {
        output("Failed to create secure_storage directory", "ERROR");
    }
} else {
    output("Secure storage directory exists");
}

// Define client file path
$clientFile = __DIR__ . '/RingCentralTeamMessagingClient.php';
output("Client file: $clientFile");

// Check if file exists
if (!file_exists($clientFile)) {
    output("Client file not found!", "ERROR");
    die();
}

// Create a backup of the original file
$backupFile = $clientFile . '.bak.' . date('YmdHis');
if (copy($clientFile, $backupFile)) {
    output("Created backup at: $backupFile", "SUCCESS");
} else {
    output("Failed to create backup file", "ERROR");
    die();
}

// Read the client class file
$content = file_get_contents($clientFile);
if ($content === false) {
    output("Failed to read client file", "ERROR");
    die();
}

// Scan for errors in the class definition
output("Scanning for errors in class definition...");

// Fix 1: Check for duplicate properties
$matches = [];
preg_match_all('/private \$apiGroup/', $content, $matches);
if (count($matches[0]) > 1) {
    output("Found duplicate apiGroup property, fixing...", "FIX");
    $content = preg_replace(
        '/\/\/ API Group setting\s+private \$apiGroup = \'medium\';.*\/\/ API Group setting\s+private \$apiGroup = \'medium\';/s', 
        "// API Group setting\n    private \$apiGroup = 'medium';", 
        $content
    );
}

// Fix 2: Ensure hasValidToken method exists and works
if (strpos($content, 'function hasValidToken') === false) {
    output("hasValidToken method is missing, adding...", "FIX");
    // Add the method at the beginning of the class
    $content = preg_replace(
        '/(class RingCentralTeamMessagingClient \{.*?\/\*\*\s+\* Constructor)/s',
        "$1\n
    /**
     * Check if we have a valid token
     */
    private function hasValidToken() {
        if (empty(\$this->accessToken)) {
            return false;
        }
        
        // Check if token is expired
        if (\$this->tokenExpiresAt && \$this->tokenExpiresAt < time()) {
            return false;
        }
        
        return true;
    }
    
    /**",
        $content
    );
}

// Fix 3: Ensure isAuthenticated method exists and works
if (strpos($content, 'function isAuthenticated') === false) {
    output("isAuthenticated method is missing, adding...", "FIX");
    // Add after getAccessToken method
    $content = str_replace(
        "    /**
     * Get access token - alias for authenticate
     */
    public function getAccessToken() {
        if (\$this->authenticate()) {
            return \$this->accessToken;
        }
        return null;
    }",
        "    /**
     * Get access token - alias for authenticate
     */
    public function getAccessToken() {
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

// Fix 4: Fix loadToken method
if (strpos($content, 'loadToken') !== false) {
    output("Checking loadToken method...");
    // Make sure it checks for file_exists
    if (strpos($content, 'file_exists($this->tokenPath)') === false) {
        output("loadToken method needs file_exists check, fixing...", "FIX");
        $content = preg_replace(
            '/private function loadToken\(\) \{.*?}/s',
            "private function loadToken() {
        // Check if token file exists
        if (!file_exists(\$this->tokenPath)) {
            return false;
        }
        
        // Read token data
        \$tokenData = json_decode(file_get_contents(\$this->tokenPath), true);
        if (!is_array(\$tokenData)) {
            return false;
        }
        
        // Set token properties
        \$this->accessToken = \$tokenData['access_token'] ?? '';
        \$this->refreshToken = \$tokenData['refresh_token'] ?? '';
        \$this->tokenExpiresAt = \$tokenData['expires_at'] ?? 0;
        
        return true;
    }",
            $content
        );
    }
}

// Fix 5: Add proper error handling to authentication method
if (strpos($content, 'authenticate') !== false) {
    output("Checking authenticate method...");
    // Add detailed error handling
    if (strpos($content, '$this->lastError = $error->getMessage()') === false) {
        output("Adding better error handling to authenticate method...", "FIX");
        $content = preg_replace(
            '/catch \(Exception \$error\) \{.*?return false;/s',
            "catch (Exception \$error) {
            \$this->lastError = \$error->getMessage();
            \$this->authErrors[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => \$error->getMessage(),
                'code' => \$error->getCode()
            ];
            if (\$this->enableDebug) {
                \$this->log('Authentication error: ' . \$error->getMessage(), 'ERROR');
            }
            return false;",
            $content
        );
    }
}

// Fix 6: Check getStandardHeaders method
if (strpos($content, 'getStandardHeaders') !== false) {
    output("Checking getStandardHeaders method...");
    // Make sure it's properly defined
    if (strpos($content, 'private function getStandardHeaders') !== false) {
        output("getStandardHeaders method looks good");
    } else {
        output("getStandardHeaders method has issues, fixing...", "FIX");
        $content = preg_replace(
            '/function getStandardHeaders.*?\{.*?\}/s',
            "private function getStandardHeaders(\$includeAuth = true) {
        \$headers = [
            'X-RingCentral-API-Group: ' . \$this->apiGroup,
            'Content-Type: application/json'
        ];
        
        if (\$includeAuth && \$this->accessToken) {
            \$headers[] = 'Authorization: Bearer ' . \$this->accessToken;
        }
        
        return \$headers;
    }",
            $content
        );
    }
}

// Fix 7: Add log method if missing
if (strpos($content, 'function log(') === false) {
    output("log method is missing, adding...", "FIX");
    // Add at the beginning of the class
    $content = preg_replace(
        '/(class RingCentralTeamMessagingClient \{.*?\/\*\*\s+\* Constructor)/s',
        "$1\n
    /**
     * Log message to file
     */
    public function log(\$message, \$level = 'INFO') {
        if (empty(\$this->logFile)) {
            return;
        }
        
        \$timestamp = date('[Y-m-d H:i:s]');
        \$formattedMessage = sprintf(\"%s [%s] %s\\n\", \$timestamp, \$level, \$message);
        
        try {
            file_put_contents(\$this->logFile, \$formattedMessage, FILE_APPEND);
        } catch (Exception \$e) {
            // Silently fail if we can't write to log
        }
    }
    
    /**",
        $content
    );
}

// Write the fixed content back to the file
if (file_put_contents($clientFile, $content) !== false) {
    output("Successfully updated RingCentralTeamMessagingClient.php", "SUCCESS");
} else {
    output("Failed to write updated content to file", "ERROR");
    die();
}

// Create a test script with detailed error reporting
output("Creating authentication test script...");
$testScript = __DIR__ . '/verify_auth.php';
$testContent = <<<'EOT'
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
EOT;

if (file_put_contents($testScript, $testContent) !== false) {
    output("Created verification script at: $testScript", "SUCCESS");
} else {
    output("Failed to create verification script", "ERROR");
}

output("\nFix completed. Run the verify_auth.php script to test authentication.", "DONE");
?>
