<?php
/**
 * Fax API Security Configuration
 * 
 * IMPORTANT: Keep this file secure! Do not commit to public repositories.
 */

return [
    // ===========================================
    // 1. API KEY AUTHENTICATION
    // ===========================================
    // Generate keys by running: php fax_security.php
    'api_keys' => [
        // Main application key - Generated 2025-10-18
        'af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e' => [
            'name' => 'Main App',
            'enabled' => true,
            'created' => '2025-10-18 18:06:52'
        ],
        // Add more keys here as needed
    ],
    
    // ===========================================
    // 2. IP WHITELIST
    // ===========================================
    // Leave empty to allow all IPs, or add specific IPs
    'ip_whitelist' => [
        // '127.0.0.1',           // Localhost IPv4
        // '::1',                 // Localhost IPv6
        // '203.0.113.0',         // Your server IP
        // '198.51.100.0',        // Your office IP
    ],
    
    // ===========================================
    // 3. RATE LIMITING
    // ===========================================
    'rate_limit' => [
        'enabled' => true,
        'max_requests_per_hour' => 50,   // Max 50 faxes per hour per client
        'max_requests_per_day' => 200,   // Max 200 faxes per day per client
    ],
    
    // ===========================================
    // 4. SESSION AUTHENTICATION
    // ===========================================
    // Set to true if only logged-in users should send faxes
    'require_session' => false,
    'session_user_key' => 'user_id',  // $_SESSION key to check
    
    // ===========================================
    // 5. SECURITY MODE
    // ===========================================
    // Options:
    // 'open' - No authentication (DANGEROUS - not recommended for production)
    // 'api_key' - Require API key
    // 'session' - Require logged-in session
    // 'api_key_or_session' - Accept either API key OR session
    // 'strict' - Require BOTH API key AND session
    'security_mode' => 'api_key',
    
    // ===========================================
    // 6. LOGGING
    // ===========================================
    'log_all_requests' => true,
    'log_failed_auth' => true,
];
?>
