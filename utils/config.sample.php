<?php
/**
 * Sample Configuration File
 * Rename to config.php and update with your database credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'shipping_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Configuration
define('API_VERSION', '1.0.0');
define('ENABLE_CORS', true);
define('DEBUG_MODE', true); // Set to false in production

// Rate Limiting
define('RATE_LIMIT_ENABLED', false);
define('RATE_LIMIT_REQUESTS', 100); // Requests per hour