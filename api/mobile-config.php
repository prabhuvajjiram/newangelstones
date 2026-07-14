<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$configFile = __DIR__ . '/mobile-config.json';

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!is_array($config)) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid configuration']);
        exit;
    }

    unset($config['api_endpoints']['monument_business_api_key']);
    unset($config['payment']['url']);
    $config['features']['payment_enabled'] = false;

    echo json_encode($config, JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration not found']);
}
