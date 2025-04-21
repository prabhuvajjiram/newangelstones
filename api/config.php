<?php
/**
 * API Configuration
 * 
 * This file contains configuration settings for the API endpoints
 */

// Base URL configuration
$apiConfig = [
    'baseUrl' => 'http://localhost:3000',  // Update with your production URL
    'authToken' => 'AngelStones2025ApiToken'  // The token defined in shipping_endpoints.php
];

// In production, you would typically load these values from environment variables or a secure configuration system
// Example:
// $apiConfig['baseUrl'] = getenv('API_BASE_URL') ?: 'http://localhost:3000';
// $apiConfig['authToken'] = getenv('API_AUTH_TOKEN') ?: 'AngelStones2025ApiToken';

return $apiConfig;
