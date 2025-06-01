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