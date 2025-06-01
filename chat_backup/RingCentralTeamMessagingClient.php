<?php
/**
 * RingCentral Team Messaging Client
 * Simplified client for interacting with RingCentral Engage Digital Messaging API
 * Following the latest RingCentral API patterns
 */

class RingCentralTeamMessagingClient {
    private $accessToken;
    private $refreshToken;
    private $tokenExpiresAt;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $extension;
    private $jwtToken; // JWT token for authentication
    private $serverUrl;
    private $tokenPath;
    private $logFile;
    private $teamChatId; // Default team chat ID for sending messages
    
    // Error tracking and debugging
    private $lastError = '';
    private $authErrors = [];
    private $lastResponse = '';
    private $lastHttpCode = 0;
    public $enableDebug = false; // Enable detailed debugging
    
    // API Group setting
    private $apiGroup = 'medium'; // Using medium group (40 requests/min) instead of heavy (10 requests/min)

    /**
     * Constructor - initialize with credentials
     */
    public function __construct(array $config = []) {
        // Set default server URL if not specified
        $this->serverUrl = $config['serverUrl'] ?? 'https://platform.ringcentral.com';
        
        // Set credentials
        $this->clientId = $config['clientId'] ?? '';
        $this->clientSecret = $config['clientSecret'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->extension = $config['extension'] ?? '';
        $this->jwtToken = $config['jwtToken'] ?? ''; // JWT token for authentication
        
        // Optional configurations
        $this->tokenPath = $config['tokenPath'] ?? __DIR__ . '/.ringcentral_token.json';
        $this->logFile = $config['logFile'] ?? __DIR__ . '/ringcentral_chat.log';
        $this->teamChatId = $config['teamChatId'] ?? null; // Default chat ID to post to
        
        // Set API group if specified
        $this->apiGroup = $config['apiGroup'] ?? $this->apiGroup;
        
        // Load token if exists
        $this->loadToken();
    }
    
    /**
     * Log message to file
     */
    public function log($message, $level = 'INFO') {
        if (empty($this->logFile)) {
            return;
        }
        
        $timestamp = date('[Y-m-d H:i:s] ');
        $logMessage = $timestamp . '[' . $level . '] ' . $message . PHP_EOL;
        
        try {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Silently fail if we can't write to log
        }
    }
    
    /**
     * Get standard headers for API requests including the medium API group
     * 
     * @param bool $includeAuth Whether to include Authorization header
     * @return array Array of headers
     */
    private function getStandardHeaders($includeAuth = true) {
        $headers = [
            'X-RingCentral-API-Group: ' . $this->apiGroup,
            'Content-Type: application/json'
        ];
        
        if ($includeAuth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
        return $headers;
    }
    
    /**
     * Check if we have a valid token
     */
    private function hasValidToken() {
        if (empty($this->accessToken)) {
            return false;
        }
        
        // Check if token is expired
        if ($this->tokenExpiresAt && $this->tokenExpiresAt < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Load token from file
     */
    private function loadToken() {
        if (!file_exists($this->tokenPath)) {
            return false;
        }
        
        $tokenData = json_decode(file_get_contents($this->tokenPath), true);
        if (!is_array($tokenData)) {
            return false;
        }
        
        $this->accessToken = $tokenData['access_token'] ?? '';
        $this->refreshToken = $tokenData['refresh_token'] ?? '';
        $this->tokenExpiresAt = $tokenData['expires_at'] ?? 0;
        
        if ($this->enableDebug) {
            $this->log('Loaded token, expires at: ' . date('Y-m-d H:i:s', $this->tokenExpiresAt));
        }
        
        return !empty($this->accessToken);
    }
    
    /**
     * Save token to file
     */
    private function saveToken() {
        $tokenData = [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->tokenExpiresAt
        ];
        
        // Ensure directory exists
        $tokenDir = dirname($this->tokenPath);
        if (!is_dir($tokenDir) && $tokenDir !== '.') {
            mkdir($tokenDir, 0755, true);
        }
        
        // Save token data
        if (file_put_contents($this->tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT))) {
            if ($this->enableDebug) {
                $this->log('Saved token to ' . $this->tokenPath);
            }
            return true;
        }
        
        // Log error
        $this->log('Failed to save token to ' . $this->tokenPath, 'ERROR');
        return false;
    }
    
    /**
     * Authenticate with RingCentral to get an access token
     */
    private function authenticate() {
        // Check if we have a valid token already
        if ($this->hasValidToken()) {
            return true;
        }
        
        // Determine auth method - OAuth vs Password vs JWT
        $authMethod = $this->jwtToken ? 'jwt' : ($this->username && $this->password ? 'password' : 'oauth');
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/oauth/token';
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'X-RingCentral-API-Group: ' . $this->apiGroup
        ];
        
        // JWT authentication
        if ($authMethod === 'jwt') {
            $params = [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->jwtToken
            ];
            
            // Add authorization header
            $headers[] = 'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);
            
            if ($this->enableDebug) {
                $this->log('Authenticating with JWT');
            }
        }
        // Password authentication
        else if ($authMethod === 'password') {
            $params = [
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
                'extension' => $this->extension,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
            
            if ($this->enableDebug) {
                $this->log('Authenticating with password');
            }
        }
        // Refresh token authentication
        else if (!empty($this->refreshToken)) {
            $params = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
            
            if ($this->enableDebug) {
                $this->log('Authenticating with refresh token');
            }
        }
        // No valid auth method
        else {
            $this->lastError = 'No valid authentication method available';
            return false;
        }
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Execute request
        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Error handling
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->lastError = 'CURL error: ' . $error;
            return false;
        }
        
        curl_close($ch);
        
        // Decode response
        $this->lastResponse = $response;
        $responseData = json_decode($response, true);
        
        // Check for errors
        if (!is_array($responseData) || !isset($responseData['access_token'])) {
            $errorMessage = $responseData['error_description'] ?? ($responseData['error'] ?? 'Unknown error');
            $this->lastError = 'Authentication failed: ' . $errorMessage;
            
            // Add to errors array
            $this->authErrors[] = [
                'time' => date('Y-m-d H:i:s'),
                'message' => $errorMessage,
                'response' => $responseData,
                'http_code' => $this->lastHttpCode
            ];
            
            if ($this->enableDebug) {
                $this->log('Authentication failed: ' . $errorMessage, 'ERROR');
            }
            
            return false;
        }
        
        // Save token data
        $this->accessToken = $responseData['access_token'];
        $this->refreshToken = $responseData['refresh_token'] ?? '';
        $this->tokenExpiresAt = time() + $responseData['expires_in'] - 60; // Subtract 60 seconds for safety
        
        // Save token to file
        $this->saveToken();
        
        if ($this->enableDebug) {
            $this->log('Authentication successful, token expires at: ' . date('Y-m-d H:i:s', $this->tokenExpiresAt));
        }
        
        return true;
    }
    
    /**
     * Get access token - alias for authenticate
     */
    public function getAccessToken() {
        if ($this->authenticate()) {
            return $this->accessToken;
        }
        return null;
    }
    
    /**
     * Check if client is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated() {
        return $this->hasValidToken() || $this->authenticate();
    }
    
    /**
     * Get a list of available chats/teams
     */
    public function listChats($type = null) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/glip/teams';
        if ($type) {
            $endpoint .= '?type=' . urlencode($type);
        }
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Error handling
        if ($response === false || $httpCode != 200) {
            return ['error' => true, 'message' => 'Failed to retrieve chats, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        return json_decode($response, true);
    }
    
    /**
     * Post a message to a chat
     */
    public function postMessage($chatId, $message, $attachments = []) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Use default chat ID if none provided
        if (empty($chatId) && !empty($this->teamChatId)) {
            $chatId = $this->teamChatId;
        }
        
        // Check if chat ID is valid
        if (empty($chatId)) {
            return ['error' => true, 'message' => 'No chat ID provided'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/glip/chats/' . $chatId . '/posts';
        $data = ['text' => $message];
        
        // Add attachments if any
        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Error handling
        if ($response === false || ($httpCode != 200 && $httpCode != 201)) {
            return ['error' => true, 'message' => 'Failed to post message, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        return json_decode($response, true);
    }
    
    /**
     * Get chat posts/messages
     */
    public function getChatPosts($chatId, $limit = 30) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Use default chat ID if none provided
        if (empty($chatId) && !empty($this->teamChatId)) {
            $chatId = $this->teamChatId;
        }
        
        // Check if chat ID is valid
        if (empty($chatId)) {
            return ['error' => true, 'message' => 'No chat ID provided'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/glip/chats/' . $chatId . '/posts?recordCount=' . $limit;
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Error handling
        if ($response === false || $httpCode != 200) {
            return ['error' => true, 'message' => 'Failed to retrieve chat posts, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        return json_decode($response, true);
    }
    
    /**
     * Get current subscriptions (webhooks)
     */
    public function getSubscriptions() {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/subscription';
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for errors
        if ($httpCode >= 400) {
            return ['error' => true, 'message' => 'Failed to retrieve subscriptions, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        $data = json_decode($response, true);
        return $data['records'] ?? [];
    }
    
    /**
     * Create a new subscription (webhook)
     * Following RingCentral API documentation: https://developers.ringcentral.com/api-reference/Subscriptions/createSubscription
     */
    public function createSubscription($subscriptionData) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/subscription';
        
        // Validate subscription data format according to RingCentral docs
        if (!isset($subscriptionData['eventFilters']) || !is_array($subscriptionData['eventFilters'])) {
            return ['error' => true, 'message' => 'Invalid subscription data: eventFilters is required and must be an array'];
        }
        
        if (!isset($subscriptionData['deliveryMode']) || !is_array($subscriptionData['deliveryMode'])) {
            return ['error' => true, 'message' => 'Invalid subscription data: deliveryMode is required and must be an object'];
        }
        
        if (!isset($subscriptionData['deliveryMode']['transportType'])) {
            return ['error' => true, 'message' => 'Invalid subscription data: deliveryMode.transportType is required'];
        }
        
        if ($subscriptionData['deliveryMode']['transportType'] === 'WebHook' && !isset($subscriptionData['deliveryMode']['address'])) {
            return ['error' => true, 'message' => 'Invalid subscription data: deliveryMode.address is required for WebHook transport'];
        }
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));
        
        if ($this->enableDebug) {
            $this->log('Creating subscription with data: ' . json_encode($subscriptionData, JSON_PRETTY_PRINT));
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check for errors
        if ($httpCode >= 400) {
            if ($this->enableDebug) {
                $this->log('Failed to create subscription, HTTP code: ' . $httpCode . ', Response: ' . $response, 'ERROR');
            }
            return ['error' => true, 'message' => 'Failed to create subscription, HTTP code: ' . $httpCode, 'response' => $response];
        }
        
        // Decode and return response
        $result = json_decode($response, true);
        
        if ($this->enableDebug) {
            $this->log('Subscription created successfully: ' . json_encode($result, JSON_PRETTY_PRINT));
        }
        
        return $result;
    }
    
    /**
     * Delete a subscription (webhook)
     */
    public function deleteSubscription($subscriptionId) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/subscription/' . $subscriptionId;
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 204 means success with no content
        if ($httpCode == 204 || $httpCode == 200) {
            return true;
        } else {
            if ($this->enableDebug) {
                $this->log('Failed to delete subscription, HTTP code: ' . $httpCode . ', Response: ' . $response, 'ERROR');
            }
            return false;
        }
    }
    
    /**
     * Create a new team
     */
    public function createTeam($name, $description = '', $members = []) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/glip/teams';
        $data = [
            'name' => $name,
            'description' => $description
        ];
        
        // Add members if any
        if (!empty($members)) {
            $data['members'] = $members;
        }
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Error handling
        if ($response === false || ($httpCode != 200 && $httpCode != 201)) {
            return ['error' => true, 'message' => 'Failed to create team, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        return json_decode($response, true);
    }
    
    /**
     * Get chat/team info
     */
    public function getTeamInfo($teamId) {
        // Ensure we're authenticated
        if (!$this->authenticate()) {
            return ['error' => true, 'message' => 'Not authenticated'];
        }
        
        // Use default chat ID if none provided
        if (empty($teamId) && !empty($this->teamChatId)) {
            $teamId = $this->teamChatId;
        }
        
        // Check if team ID is valid
        if (empty($teamId)) {
            return ['error' => true, 'message' => 'No team ID provided'];
        }
        
        // Prepare request
        $endpoint = $this->serverUrl . '/restapi/v1.0/glip/teams/' . $teamId;
        
        // Make request
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getStandardHeaders());
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Error handling
        if ($response === false || $httpCode != 200) {
            return ['error' => true, 'message' => 'Failed to retrieve team info, HTTP code: ' . $httpCode];
        }
        
        // Decode and return response
        return json_decode($response, true);
    }
}
?>
