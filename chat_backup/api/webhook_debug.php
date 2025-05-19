<?php
/**
 * RingCentral Webhook Debug Endpoint
 * 
 * Simple script to log incoming webhook data for debugging
 */

// Set proper content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Define log file
$logFile = __DIR__ . '/../webhook_debug.log';

// Log all incoming data
$rawData = file_get_contents('php://input');
$timestamp = date('[Y-m-d H:i:s] ');
$httpMethod = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$headersJson = json_encode($headers);

// Create a comprehensive log entry
$logEntry = "{$timestamp} HTTP METHOD: {$httpMethod}\n";
$logEntry .= "{$timestamp} HEADERS: {$headersJson}\n";
$logEntry .= "{$timestamp} PAYLOAD: {$rawData}\n";
$logEntry .= "{$timestamp} ------------------------\n";

// Write to log file
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Return a success response to RingCentral
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook data received and logged',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
