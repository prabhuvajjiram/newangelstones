<?php
/**
 * Verify RingCentral Authentication
 */

// Include necessary files
define('LOCAL_ENTRY_POINT', true);
require_once __DIR__ . '/config.php';

echo "RingCentral Authentication Verification\n";
echo "=====================================\n\n";

// Load token directly
$tokenPath = __DIR__ . '/.ringcentral_token.json';
if (!file_exists($tokenPath)) {
    die("Error: Token file not found at $tokenPath\n");
}

$tokenData = json_decode(file_get_contents($tokenPath), true);
if (!$tokenData || !isset($tokenData['access_token'])) {
    die("Error: Invalid token format in $tokenPath\n");
}

// Display token information
echo "Access token: " . substr($tokenData['access_token'], 0, 10) . "...\n";
echo "Expires at: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . "\n";
if (time() > $tokenData['expires_at']) {
    echo "WARNING: Token has expired!\n";
} else {
    echo "Token is valid for " . ($tokenData['expires_at'] - time()) . " more seconds\n";
}

// Make a direct API call using the token
$endpoint = RINGCENTRAL_SERVER . '/restapi/v1.0/glip/teams';
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenData['access_token'],
    'Content-Type: application/json',
    'X-RingCentral-API-Group: medium'
]);

echo "\nTesting API call to list teams...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse && isset($jsonResponse['records'])) {
        $count = count($jsonResponse['records']);
        echo "Success! Retrieved $count teams\n";
        
        if ($count > 0) {
            $firstTeam = $jsonResponse['records'][0];
            echo "First team: " . ($firstTeam['name'] ?? 'unknown') . " (ID: " . ($firstTeam['id'] ?? 'unknown') . ")\n";
        }
    } else {
        echo "API call succeeded but returned unexpected format\n";
    }
} else {
    echo "API call failed with HTTP code $httpCode\n";
    echo "Response: $response\n";
}

echo "\nVerification completed.\n";