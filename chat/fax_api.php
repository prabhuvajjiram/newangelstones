<?php
/**
 * RingCentral Fax API - Standalone Implementation
 * 
 * Send faxes via RingCentral API
 * API Reference: https://developers.ringcentral.com/api-reference/Fax/createFaxMessage
 * 
 * Endpoint: POST /restapi/v1.0/account/~/extension/~/fax
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

// Include security layer
require_once __DIR__ . '/fax_security.php';

// Initialize security (optional - can be disabled for testing)
$securityEnabled = file_exists(__DIR__ . '/fax_security_config.php');
if ($securityEnabled) {
    $security = new FaxSecurityManager(__DIR__ . '/fax_security_config.php');
    $security->authorize(); // Will exit if authorization fails
}

/**
 * RingCentral Fax Client
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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            $this->accessToken = $tokenData['access_token'];
            
            // Save token with expiration
            $tokenData['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);
            file_put_contents($this->tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT));
            
            $this->log("JWT authentication successful");
            return true;
        } else {
            $this->log("JWT authentication failed: HTTP $httpCode - $response", 'ERROR');
            return false;
        }
    }
    
    /**
     * Authenticate using OAuth (load from existing token)
     */
    private function authenticateOAuth() {
        if ($this->accessToken) {
            return true;
        }
        
        $this->log("No valid OAuth token found. Please authenticate first.", 'ERROR');
        return false;
    }
    
    /**
     * Send a fax
     * 
     * @param array $params Fax parameters
     *   - to: array|string Recipient fax number(s) (required)
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
            
            $coverOptions = [
                'to_name' => $params['to_name'] ?? '',
                'to_company' => $params['to_company'] ?? '',
                'to_fax' => $toFax,
                'from_name' => $params['from_name'] ?? 'Angel Granites',
                'message' => $params['coverPageText'] ?? '',
                'pages' => (isset($params['attachments']) ? count($params['attachments']) : 0) + 1,
                'urgent' => $params['urgent'] ?? false,
                'confidential' => $params['confidential'] ?? false
            ];
            
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
        
        if (isset($params['coverIndex'])) {
            $json['coverIndex'] = (int)$params['coverIndex'];
        }
        
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"json\"\r\n";
        $body .= "Content-Type: application/json\r\n\r\n";
        $body .= json_encode($json) . "\r\n";
        
        // Add file attachments from file paths
        if (!empty($params['attachments'])) {
            foreach ($params['attachments'] as $filePath) {
                if (!file_exists($filePath)) {
                    $this->log("File not found: $filePath", 'WARN');
                    continue;
                }
                
                $fileName = basename($filePath);
                $fileContent = file_get_contents($filePath);
                $mimeType = $this->getMimeType($filePath);
                
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"attachment\"; filename=\"{$fileName}\"\r\n";
                $body .= "Content-Type: {$mimeType}\r\n\r\n";
                $body .= $fileContent . "\r\n";
            }
        }
        
        // Add file attachments from data
        if (!empty($params['attachment_data'])) {
            foreach ($params['attachment_data'] as $file) {
                $fileName = $file['name'] ?? 'document.pdf';
                $fileContent = $file['content'] ?? '';
                $mimeType = $file['type'] ?? 'application/pdf';
                
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"attachment\"; filename=\"{$fileName}\"\r\n";
                $body .= "Content-Type: {$mimeType}\r\n\r\n";
                $body .= $fileContent . "\r\n";
            }
        }
        
        $body .= "--{$boundary}--\r\n";
        
        // Send request
        $url = $this->serverUrl . '/restapi/v1.0/account/~/extension/~/fax';
        
        $this->log("Sending fax to: " . json_encode($params['to']));
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: multipart/form-data; boundary=' . $boundary,
                'Content-Length: ' . strlen($body)
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->log("cURL error: $curlError", 'ERROR');
            return [
                'success' => false,
                'error' => 'Connection error: ' . $curlError
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $this->log("Fax sent successfully. Message ID: " . ($responseData['id'] ?? 'unknown'));
            return [
                'success' => true,
                'message_id' => $responseData['id'] ?? null,
                'uri' => $responseData['uri'] ?? null,
                'data' => $responseData
            ];
        } else {
            $errorMsg = $responseData['message'] ?? $responseData['error_description'] ?? 'Unknown error';
            $this->log("Fax send failed: HTTP $httpCode - $errorMsg", 'ERROR');
            return [
                'success' => false,
                'error' => $errorMsg,
                'http_code' => $httpCode,
                'response' => $responseData
            ];
        }
    }
    
    /**
     * Get fax status
     * 
     * @param string $messageId Message ID from sendFax response
     * @return array Fax status information
     */
    public function getFaxStatus($messageId) {
        if (!$this->accessToken) {
            if (!$this->authenticate()) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed'
                ];
            }
        }
        
        $url = $this->serverUrl . '/restapi/v1.0/account/~/extension/~/message-store/' . $messageId;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode == 200) {
            return [
                'success' => true,
                'data' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Failed to get fax status',
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Get MIME type for file
     */
    private function getMimeType($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

// If this file is called directly (not included), handle as API endpoint
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json');
    
    // Enable CORS
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $allowedOrigins = [
            'https://theangelstones.com',
            'https://www.theangelstones.com',
            'http://localhost'
        ];
        
        if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
    }
    
    // Handle OPTIONS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Initialize fax client
    $faxClient = new RingCentralFaxClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
        'authType' => defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'jwt',
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json'
    ]);
    
    // Handle GET request for status check
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'status') {
            if (!isset($_GET['message_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'message_id parameter is required'
                ]);
                exit;
            }
            
            $result = $faxClient->getFaxStatus($_GET['message_id']);
            http_response_code($result['success'] ? 200 : ($result['http_code'] ?? 400));
            echo json_encode($result);
            exit;
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action. Use ?action=status&message_id=YOUR_MESSAGE_ID'
            ]);
            exit;
        }
    }
    
    // Only accept POST requests for sending fax
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST to send fax or GET with ?action=status&message_id=ID to check status.'
        ]);
        exit;
    }
    
    try {
        // Get input data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            // Send fax
            $result = $faxClient->sendFax($input);
            
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            // Form data input
            $params = [
                'to' => $_POST['to'] ?? '',
                'faxResolution' => $_POST['faxResolution'] ?? 'High',
                'coverPageText' => $_POST['coverPageText'] ?? '',
            ];
            
            if (isset($_POST['coverIndex'])) {
                $params['coverIndex'] = (int)$_POST['coverIndex'];
            }
            
            // Handle uploaded files
            if (!empty($_FILES['attachment'])) {
                $params['attachment_data'] = [];
                
                // Handle multiple files
                if (is_array($_FILES['attachment']['name'])) {
                    for ($i = 0; $i < count($_FILES['attachment']['name']); $i++) {
                        if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
                            $params['attachment_data'][] = [
                                'name' => $_FILES['attachment']['name'][$i],
                                'content' => file_get_contents($_FILES['attachment']['tmp_name'][$i]),
                                'type' => $_FILES['attachment']['type'][$i]
                            ];
                        }
                    }
                } else {
                    if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                        $params['attachment_data'][] = [
                            'name' => $_FILES['attachment']['name'],
                            'content' => file_get_contents($_FILES['attachment']['tmp_name']),
                            'type' => $_FILES['attachment']['type']
                        ];
                    }
                }
            }
            
            $result = $faxClient->sendFax($params);
            
        } else {
            throw new Exception('Unsupported content type. Use application/json or multipart/form-data');
        }
        
        // Return result
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
