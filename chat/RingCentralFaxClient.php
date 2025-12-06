<?php
/**
 * RingCentral Fax Client
 * 
 * Shared client for sending faxes via RingCentral API
 */

class RingCentralFaxClient {
    private $clientId;
    private $clientSecret;
    private $serverUrl;
    private $jwtToken;
    private $accessToken;
    private $tokenPath;
    private $logFile;
    private $authType;
    
    public function __construct(array $config = []) {
        $this->clientId = $config['clientId'] ?? '';
        $this->clientSecret = $config['clientSecret'] ?? '';
        $this->serverUrl = $config['serverUrl'] ?? 'https://platform.ringcentral.com';
        $this->jwtToken = $config['jwtToken'] ?? '';
        $this->authType = $config['authType'] ?? 'jwt';
        $this->tokenPath = $config['tokenPath'] ?? __DIR__ . '/secure_storage/rc_token.json';
        $this->logFile = $config['logFile'] ?? __DIR__ . '/fax_api.log';
        
        // Load existing token
        $this->loadToken();
    }
    
    /**
     * Log message to file
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('[Y-m-d H:i:s] ');
        file_put_contents($this->logFile, $timestamp . '[' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Load access token from file
     */
    private function loadToken() {
        if (file_exists($this->tokenPath)) {
            $tokenData = json_decode(file_get_contents($this->tokenPath), true);
            if ($tokenData && isset($tokenData['access_token'])) {
                // Check if token is still valid
                $expiresAt = $tokenData['expires_at'] ?? 0;
                if ($expiresAt > time()) {
                    $this->accessToken = $tokenData['access_token'];
                    $this->log("Loaded valid access token (expires: " . date('Y-m-d H:i:s', $expiresAt) . ")");
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Save access token to file
     */
    private function saveToken($tokenData) {
        // Ensure directory exists
        $dir = dirname($this->tokenPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Add expiration timestamp
        $tokenData['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);
        
        file_put_contents($this->tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT));
        chmod($this->tokenPath, 0600); // Secure permissions
        
        $this->log("Saved access token (expires: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . ")");
    }
    
    /**
     * Authenticate with RingCentral (JWT or OAuth)
     */
    public function authenticate() {
        if ($this->authType === 'jwt' && !empty($this->jwtToken)) {
            return $this->authenticateJWT();
        } else {
            return $this->authenticateOAuth();
        }
    }
    
    /**
     * Authenticate using JWT
     */
    private function authenticateJWT() {
        $this->log("Authenticating with JWT...");
        
        $ch = curl_init($this->serverUrl . '/restapi/oauth/token');
        
        $postFields = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->jwtToken
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->log("JWT authentication failed: $error", 'ERROR');
            return false;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            $this->saveToken($data);
            $this->log("JWT authentication successful");
            return true;
        } else {
            $errorMsg = $data['error_description'] ?? $data['message'] ?? 'Unknown error';
            $this->log("JWT authentication failed: $errorMsg", 'ERROR');
            return false;
        }
    }
    
    /**
     * Authenticate using OAuth (3-legged)
     */
    private function authenticateOAuth() {
        $this->log("OAuth authentication not implemented", 'ERROR');
        return false;
    }
    
    /**
     * Send a fax
     * 
     * @param array $params Fax parameters:
     *   - to: string Recipient fax number
     *   - faxResolution: string 'High' or 'Low' (default: High)
     *   - coverPageText: string Cover page text
     *   - coverIndex: int Cover page template index (0-4) OR 'custom' for Angel Granite branded cover
     *   - to_name: string Recipient name (for custom cover page)
     *   - to_company: string Recipient company (for custom cover page)
     *   - from_name: string Sender name (for custom cover page, default: Angel Granites)
     *   - urgent: bool Mark as urgent (for custom cover page)
     *   - confidential: bool Mark as confidential (for custom cover page)
     *   - attachments: array Array of file paths to send
     *   - attachment_data: array Array of file data ['name' => 'file.pdf', 'content' => binary, 'type' => 'application/pdf']
     * 
     * @return array Response with success status and message details
     */
    public function sendFax(array $params) {
        // Ensure we have a valid token
        if (!$this->accessToken) {
            if (!$this->authenticate()) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed'
                ];
            }
        }
        
        // Check if custom branded cover page is requested
        if (isset($params['coverIndex']) && $params['coverIndex'] === 'custom') {
            // Generate custom Angel Granite cover page
            require_once __DIR__ . '/generate_coverpage.php';
            
            $toFax = is_array($params['to']) ? $params['to'][0] : $params['to'];
            
            // Calculate page count (attachment count + 1 for cover)
            $pageCount = 1;
            if (isset($params['attachments'])) {
                $pageCount += count($params['attachments']);
            }
            if (isset($params['attachment_data'])) {
                $pageCount += count($params['attachment_data']);
            }
            
            $coverOptions = [
                'to_name' => $params['to_name'] ?? '',
                'to_company' => $params['to_company'] ?? '',
                'to_fax' => $toFax,
                'from_name' => $params['from_name'] ?? 'Angel Granites',
                'message' => $params['coverPageText'] ?? '',
                'pages' => $pageCount,
                'urgent' => $params['urgent'] ?? false,
                'confidential' => $params['confidential'] ?? false
            ];
            
            $this->log("Generating custom cover page with options: " . json_encode($coverOptions));
            
            try {
                $coverPath = AngelGraniteCoverPage::generate($coverOptions);
                
                // Add cover page as first attachment
                if (!isset($params['attachments'])) {
                    $params['attachments'] = [];
                }
                array_unshift($params['attachments'], $coverPath);
                
                // Remove coverIndex and coverPageText since we're using custom cover
                unset($params['coverIndex']);
                unset($params['coverPageText']);
                
                $this->log("Generated custom Angel Granite cover page: $coverPath");
            } catch (Exception $e) {
                $this->log("Failed to generate custom cover page: " . $e->getMessage(), 'ERROR');
            }
        }
        
        // Validate required parameters
        if (empty($params['to'])) {
            return [
                'success' => false,
                'error' => 'Recipient fax number (to) is required'
            ];
        }
        
        // Validate attachments
        if (empty($params['attachments']) && empty($params['attachment_data'])) {
            return [
                'success' => false,
                'error' => 'At least one attachment is required'
            ];
        }
        
        // Prepare multipart form data
        $boundary = '----WebKitFormBoundary' . md5(time());
        $body = '';
        
        // Add JSON metadata
        $json = [
            'to' => is_array($params['to']) ? $params['to'] : [['phoneNumber' => $params['to']]],
            'faxResolution' => $params['faxResolution'] ?? 'High'
        ];
        
        if (!empty($params['coverPageText'])) {
            $json['coverPageText'] = $params['coverPageText'];
        }
        
        if (isset($params['coverIndex']) && is_numeric($params['coverIndex'])) {
            $json['coverIndex'] = (int)$params['coverIndex'];
        }
        
        // Add JSON part
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"json\"\r\n";
        $body .= "Content-Type: application/json\r\n\r\n";
        $body .= json_encode($json) . "\r\n";
        
        // Add file attachments from paths
        if (!empty($params['attachments'])) {
            foreach ($params['attachments'] as $filePath) {
                if (file_exists($filePath)) {
                    $fileName = basename($filePath);
                    $fileContent = file_get_contents($filePath);
                    $mimeType = mime_content_type($filePath);
                    
                    $body .= "--$boundary\r\n";
                    $body .= "Content-Disposition: form-data; name=\"attachment\"; filename=\"$fileName\"\r\n";
                    $body .= "Content-Type: $mimeType\r\n\r\n";
                    $body .= $fileContent . "\r\n";
                }
            }
        }
        
        // Add file attachments from data
        if (!empty($params['attachment_data'])) {
            foreach ($params['attachment_data'] as $file) {
                $fileName = $file['name'];
                $fileContent = $file['content'];
                $mimeType = $file['type'] ?? 'application/pdf';
                
                // Auto-decode base64 content if needed (backward compatible)
                $fileContent = $this->decodeContentIfNeeded($fileContent);
                
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"attachment\"; filename=\"$fileName\"\r\n";
                $body .= "Content-Type: $mimeType\r\n\r\n";
                $body .= $fileContent . "\r\n";
            }
        }
        
        $body .= "--$boundary--\r\n";
        
        // Send fax request
        $url = $this->serverUrl . '/restapi/v1.0/account/~/extension/~/fax';
        
        $this->log("Sending fax to: " . json_encode($json['to']));
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Content-Length: ' . strlen($body)
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->log("Fax send failed: $error", 'ERROR');
            return [
                'success' => false,
                'error' => $error
            ];
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $this->log("Fax sent successfully. Message ID: " . ($data['id'] ?? 'unknown'));
            return [
                'success' => true,
                'message' => 'Fax sent successfully',
                'data' => $data
            ];
        } else {
            $errorMsg = $data['error_description'] ?? $data['message'] ?? 'Unknown error';
            $this->log("Fax send failed (HTTP $httpCode): $errorMsg", 'ERROR');
            return [
                'success' => false,
                'error' => $errorMsg,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Decode content if it's base64 encoded (backward compatible)
     * 
     * Detects if content is base64 and decodes it automatically.
     * If content is already binary, returns it as-is.
     * 
     * @param string $content Content to decode
     * @return string Decoded content or original if not base64
     */
    private function decodeContentIfNeeded($content) {
        // Empty content - return as-is
        if (empty($content)) {
            return $content;
        }
        
        // Check if content looks like base64:
        // - Contains only valid base64 characters (A-Z, a-z, 0-9, +, /, =)
        // - Length is multiple of 4 (with padding)
        // - No binary characters that would indicate it's already decoded
        
        // If content has null bytes or other binary characters, it's already decoded
        if (strpos($content, "\0") !== false) {
            return $content; // Already binary
        }
        
        // Check if it's valid base64 string
        $isBase64 = preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $content);
        
        if ($isBase64 && strlen($content) % 4 === 0) {
            // Try to decode
            $decoded = base64_decode($content, true);
            
            // Verify it decoded successfully
            if ($decoded !== false) {
                // Additional check: re-encode and compare to ensure it was actually base64
                if (base64_encode($decoded) === $content) {
                    $this->log("Detected and decoded base64 content (" . strlen($content) . " -> " . strlen($decoded) . " bytes)");
                    return $decoded;
                }
            }
        }
        
        // Not base64 or decode failed - return original
        return $content;
    }
}
