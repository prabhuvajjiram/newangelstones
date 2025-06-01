<?php
/**
 * Fix RingCentral Authentication
 * 
 * This script adds the missing isAuthenticated method to the RingCentralTeamMessagingClient class
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define entry point constant
define('LOCAL_ENTRY_POINT', true);

// Path to the RingCentralTeamMessagingClient.php file
$filePath = __DIR__ . '/RingCentralTeamMessagingClient.php';

// Check if the file exists
if (!file_exists($filePath)) {
    die("Error: RingCentralTeamMessagingClient.php not found.");
}

// Create a backup of the original file
$backupPath = $filePath . '.bak.' . date('YmdHis');
if (!copy($filePath, $backupPath)) {
    die("Error: Failed to create backup file.");
}
echo "Created backup at: $backupPath\n";

// Read the file content
$content = file_get_contents($filePath);
if ($content === false) {
    die("Error: Failed to read file content.");
}

// Check if the isAuthenticated method already exists
if (strpos($content, 'function isAuthenticated') !== false) {
    echo "The isAuthenticated method already exists.\n";
    
    // Check if the file has any duplicate property definitions
    if (preg_match_all('/private \$apiGroup/', $content, $matches) > 1) {
        echo "Found duplicate apiGroup property, fixing...\n";
        $content = preg_replace('/\/\/ API Group setting\s+private \$apiGroup = \'medium\';.*$\s+\/\/ API Group setting/m', '// API Group setting', $content);
    }
} else {
    echo "Adding isAuthenticated method...\n";
    
    // Add the isAuthenticated method after the getAccessToken method
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

// Fix the log method if missing
if (strpos($content, 'function log(') === false && strpos($content, 'public function log(') === false) {
    echo "Adding log method...\n";
    
    // Add the log method at the beginning of the class
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

// Write the updated content back to the file
if (file_put_contents($filePath, $content)) {
    echo "Successfully updated RingCentralTeamMessagingClient.php\n";
} else {
    die("Error: Failed to write updated content to file.");
}

// Create the secure_storage directory if it doesn't exist
$secureStorageDir = __DIR__ . '/secure_storage';
if (!is_dir($secureStorageDir)) {
    if (mkdir($secureStorageDir, 0755, true)) {
        echo "Created secure_storage directory.\n";
    } else {
        echo "Failed to create secure_storage directory.\n";
    }
}

echo "\nFix complete. Please try again with your RingCentral authentication.\n";
?>
