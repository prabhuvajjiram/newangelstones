<?php
/**
 * Shipping API Test Script
 * 
 * This script tests the shipping API endpoints.
 * It simulates API calls with proper authorization headers.
 */

// Load configuration
$config = require_once __DIR__ . '/config.php';

// Configuration
$baseUrl = $config['baseUrl'];
$authToken = $config['authToken'];

// Function to simulate API calls
function callApi($endpoint, $token) {
    global $baseUrl;
    
    // Build full URL
    $url = "$baseUrl/api/shipping_endpoints.php?endpoint=$endpoint";
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Handle errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'status' => 0,
            'error' => $error
        ];
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'status' => $httpCode,
        'data' => $data
    ];
}

// Test listShipments endpoint
echo "Testing listShipments endpoint...\n";
$result = callApi('listShipments', $authToken);

if ($result['success']) {
    echo "SUCCESS (HTTP {$result['status']})\n";
    echo "Found {$result['data']['count']} shipments\n";
    
    // Print first 5 shipments
    $shipments = $result['data']['shipments'] ?? [];
    $count = min(5, count($shipments));
    
    if ($count > 0) {
        echo "First $count shipments:\n";
        for ($i = 0; $i < $count; $i++) {
            echo "- {$shipments[$i]}\n";
        }
    } else {
        echo "No shipments found\n";
    }
} else {
    echo "FAILED (HTTP {$result['status']})\n";
    echo "Error: " . json_encode($result['data']) . "\n";
}

echo "\n";

// Test getShippingDetails endpoint (with the first shipment if available)
if ($result['success'] && !empty($result['data']['shipments'])) {
    $shipmentId = $result['data']['shipments'][0];
    
    echo "Testing getShippingDetails endpoint with ID: $shipmentId...\n";
    $detailResult = callApi("getShippingDetails/$shipmentId", $authToken);
    
    if ($detailResult['success']) {
        echo "SUCCESS (HTTP {$detailResult['status']})\n";
        echo "Shipment details: \n";
        print_r($detailResult['data']);
    } else {
        echo "FAILED (HTTP {$detailResult['status']})\n";
        echo "Error: " . json_encode($detailResult['data']) . "\n";
    }
} else {
    echo "Skipping getShippingDetails test - no shipments available\n";
}

// Test with invalid token
echo "\nTesting with invalid token...\n";
$invalidResult = callApi('listShipments', 'invalid_token');

if (!$invalidResult['success'] && $invalidResult['status'] === 401) {
    echo "SUCCESS - Unauthorized response (HTTP 401) as expected\n";
} else {
    echo "FAILED - Expected 401 unauthorized, got HTTP {$invalidResult['status']}\n";
}
