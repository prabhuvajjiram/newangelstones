<?php
/**
 * RingCentral Fax API - Security Layer
 * 
 * Multiple security options:
 * 1. API Key Authentication
 * 2. IP Whitelist
 * 3. Rate Limiting
 * 4. Session-based authentication (for logged-in users)
 */

class FaxSecurityManager {
    private $config;
    private $logFile;
    private $rateLimitFile;
    
    public function __construct($configFile = null) {
        $this->logFile = __DIR__ . '/logs/fax_security.log';
        $this->rateLimitFile = __DIR__ . '/secure_storage/rate_limits.json';
        
        // Load configuration
        if ($configFile && file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [
                // API Key Authentication
                'api_keys' => [
                    'your-secret-key-here' => ['name' => 'Main App', 'enabled' => true],
                    // Add more keys as needed
                ],
                
                // IP Whitelist (empty = allow all)
                'ip_whitelist' => [
                    // '127.0.0.1',
                    // '::1',
                    // Add your server IPs here
                ],
                
                // Rate Limiting
                'rate_limit' => [
                    'enabled' => true,
                    'max_requests_per_hour' => 50,
                    'max_requests_per_day' => 200,
                ],
                
                // Session authentication (for logged-in users)
                'require_session' => false,
                'session_user_key' => 'user_id', // Key in $_SESSION to check
            ];
        }
        
        // Ensure directories exist
        $this->ensureDirectories();
    }
    
    /**
     * Ensure required directories exist
     */
    private function ensureDirectories() {
        $dirs = [
            dirname($this->logFile),
            dirname($this->rateLimitFile)
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Main security check - call this before processing fax requests
     */
    public function authorize() {
        try {
            // 1. Check IP Whitelist
            if (!empty($this->config['ip_whitelist'])) {
                $this->checkIPWhitelist();
            }
            
            // 2. Check API Key (if provided in request)
            if (isset($_SERVER['HTTP_X_API_KEY']) || isset($_GET['api_key']) || isset($_POST['api_key'])) {
                $this->checkAPIKey();
            }
            
            // 3. Check Session (if required)
            if ($this->config['require_session']) {
                $this->checkSession();
            }
            
            // 4. Check Rate Limit
            if ($this->config['rate_limit']['enabled']) {
                $this->checkRateLimit();
            }
            
            // Log successful authorization
            $this->log('Authorization successful for ' . $this->getClientIdentifier());
            
            return true;
            
        } catch (Exception $e) {
            $this->log('Authorization failed: ' . $e->getMessage(), 'WARNING');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'http_code' => 403
            ]);
            exit;
        }
    }
    
    /**
     * Check if IP is whitelisted
     */
    private function checkIPWhitelist() {
        $clientIP = $this->getClientIP();
        
        if (!in_array($clientIP, $this->config['ip_whitelist'])) {
            throw new Exception('Access denied: IP not whitelisted');
        }
    }
    
    /**
     * Check API Key
     */
    private function checkAPIKey() {
        // Get API key from various sources
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? null;
        
        if (!$apiKey) {
            throw new Exception('API key required');
        }
        
        // Check if key exists and is enabled
        if (!isset($this->config['api_keys'][$apiKey])) {
            throw new Exception('Invalid API key');
        }
        
        if (!$this->config['api_keys'][$apiKey]['enabled']) {
            throw new Exception('API key is disabled');
        }
    }
    
    /**
     * Check session authentication
     */
    private function checkSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userKey = $this->config['session_user_key'];
        
        if (!isset($_SESSION[$userKey]) || empty($_SESSION[$userKey])) {
            throw new Exception('Authentication required: Please log in');
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        $identifier = $this->getClientIdentifier();
        $limits = $this->loadRateLimits();
        $now = time();
        
        // Initialize if not exists
        if (!isset($limits[$identifier])) {
            $limits[$identifier] = [
                'hourly' => [],
                'daily' => []
            ];
        }
        
        // Clean old entries
        $limits[$identifier]['hourly'] = array_filter(
            $limits[$identifier]['hourly'],
            function($timestamp) use ($now) {
                return $timestamp > ($now - 3600); // Last hour
            }
        );
        
        $limits[$identifier]['daily'] = array_filter(
            $limits[$identifier]['daily'],
            function($timestamp) use ($now) {
                return $timestamp > ($now - 86400); // Last 24 hours
            }
        );
        
        // Check limits
        $hourlyCount = count($limits[$identifier]['hourly']);
        $dailyCount = count($limits[$identifier]['daily']);
        
        if ($hourlyCount >= $this->config['rate_limit']['max_requests_per_hour']) {
            throw new Exception('Rate limit exceeded: Maximum ' . 
                $this->config['rate_limit']['max_requests_per_hour'] . ' requests per hour');
        }
        
        if ($dailyCount >= $this->config['rate_limit']['max_requests_per_day']) {
            throw new Exception('Rate limit exceeded: Maximum ' . 
                $this->config['rate_limit']['max_requests_per_day'] . ' requests per day');
        }
        
        // Add current request
        $limits[$identifier]['hourly'][] = $now;
        $limits[$identifier]['daily'][] = $now;
        
        // Save limits
        $this->saveRateLimits($limits);
    }
    
    /**
     * Get client identifier for rate limiting
     */
    private function getClientIdentifier() {
        // Use API key if provided
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? null;
        if ($apiKey && isset($this->config['api_keys'][$apiKey])) {
            return 'api_' . md5($apiKey);
        }
        
        // Use session if available
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$this->config['session_user_key']])) {
            return 'user_' . $_SESSION[$this->config['session_user_key']];
        }
        
        // Fall back to IP
        return 'ip_' . $this->getClientIP();
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $ip;
    }
    
    /**
     * Load rate limits from file
     */
    private function loadRateLimits() {
        if (!file_exists($this->rateLimitFile)) {
            return [];
        }
        
        $data = file_get_contents($this->rateLimitFile);
        return json_decode($data, true) ?: [];
    }
    
    /**
     * Save rate limits to file
     */
    private function saveRateLimits($limits) {
        file_put_contents($this->rateLimitFile, json_encode($limits, JSON_PRETTY_PRINT));
    }
    
    /**
     * Log security events
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('[Y-m-d H:i:s]');
        $ip = $this->getClientIP();
        $logEntry = "$timestamp [$level] [$ip] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Generate a new API key
     */
    public static function generateAPIKey($name = 'New Key') {
        $key = bin2hex(random_bytes(32));
        return [
            'key' => $key,
            'config' => [
                $key => [
                    'name' => $name,
                    'enabled' => true,
                    'created' => date('Y-m-d H:i:s')
                ]
            ]
        ];
    }
}

/**
 * Security Configuration Generator
 * Run this file directly to generate a new API key
 */
if (basename($_SERVER['PHP_SELF']) === 'fax_security.php' && php_sapi_name() === 'cli') {
    echo "\n===========================================\n";
    echo "RingCentral Fax API - Security Key Generator\n";
    echo "===========================================\n\n";
    
    $name = readline("Enter key name (default: Main App): ");
    if (empty($name)) {
        $name = 'Main App';
    }
    
    $result = FaxSecurityManager::generateAPIKey($name);
    
    echo "\n✅ New API Key Generated!\n\n";
    echo "Key: " . $result['key'] . "\n\n";
    echo "Add this to your fax_security_config.php:\n\n";
    echo "<?php\n";
    echo "return [\n";
    echo "    'api_keys' => [\n";
    echo "        '" . $result['key'] . "' => [\n";
    echo "            'name' => '" . $name . "',\n";
    echo "            'enabled' => true,\n";
    echo "            'created' => '" . date('Y-m-d H:i:s') . "'\n";
    echo "        ],\n";
    echo "    ],\n";
    echo "    // ... other config\n";
    echo "];\n\n";
    
    echo "Usage in curl:\n";
    echo "curl -X POST https://theangelstones.com/chat/fax_api.php \\\n";
    echo "  -H \"X-API-Key: " . $result['key'] . "\" \\\n";
    echo "  -F \"to=+17062627693\" \\\n";
    echo "  -F \"attachment=@document.pdf\"\n\n";
}
?>
