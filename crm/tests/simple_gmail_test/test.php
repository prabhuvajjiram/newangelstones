<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "PHP is working<br>";

// Test config file
if (file_exists('config.php')) {
    echo "config.php exists<br>";
    require_once 'config.php';
    echo "config.php loaded<br>";
    echo "Client ID: " . (defined('GMAIL_CLIENT_ID') ? 'is defined' : 'not defined') . "<br>";
}

// Test autoloader
if (file_exists('vendor/autoload.php')) {
    echo "vendor/autoload.php exists<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    echo "autoloader loaded<br>";
    
    // Test PHPMailer class
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "PHPMailer class exists<br>";
    } else {
        echo "PHPMailer class not found<br>";
    }
} else {
    echo "vendor/autoload.php not found - Composer dependencies may not be installed<br>";
}

// Check session
session_start();
echo "Session started<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Directory contents
echo "<br>Directory contents:<br>";
$files = scandir(__DIR__);
echo "<pre>" . print_r($files, true) . "</pre>";
