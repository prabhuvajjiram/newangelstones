<?php
/**
 * API Configuration
 * 
 * This file contains configuration settings for the API endpoints
 */

// Base URL configuration
$apiConfig = [
    'baseUrl' => getenv('API_BASE_URL') ?: 'http://localhost:3000',
    'authToken' => getenv('SHIPPING_API_TOKEN') ?: ''
];

return $apiConfig;
