<?php
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log the error first
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    
    // Clean any output that might have been sent
    if (ob_get_level()) {
        ob_clean();
    }
    
    $error = [
        'success' => false,
        'message' => 'Server Error',
        'debug' => [
            'error' => $errstr,
            'file' => basename($errfile),
            'line' => $errline
        ]
    ];
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    
    echo json_encode($error);
    exit;
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1); 