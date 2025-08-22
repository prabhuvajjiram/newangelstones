<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$configFile = __DIR__ . '/mobile-config.json';

if (file_exists($configFile)) {
    echo file_get_contents($configFile);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration not found']);
}
?>