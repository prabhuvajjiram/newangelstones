<?php
// Simple autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . str_replace('PHPMailer/PHPMailer/', 'phpmailer/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});
