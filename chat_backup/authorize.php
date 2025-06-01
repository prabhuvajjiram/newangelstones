<?php
/**
 * RingCentral OAuth Authorization Initiator
 * This file starts the 3-legged OAuth flow for RingCentral
 */

// Include configuration
define('LOCAL_ENTRY_POINT', true); // Required to load config
require_once __DIR__ . '/config.php';

// Log redirect URI for debugging
$logFile = __DIR__ . '/ringcentral_chat.log';
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . '[INFO] Authorization initiated with redirect URI: ' . RINGCENTRAL_REDIRECT_URI . PHP_EOL, FILE_APPEND);

// Build the authorization URL using the RINGCENTRAL_AUTH_URL constant from config
$authUrl = RINGCENTRAL_AUTH_URL . '?' . http_build_query([
    'response_type' => 'code',
    'client_id' => RINGCENTRAL_CLIENT_ID,
    'redirect_uri' => RINGCENTRAL_REDIRECT_URI,
    'state' => md5(uniqid(rand(), true)) // Security feature to prevent CSRF
]);

// Redirect to RingCentral authorization page
header('Location: ' . $authUrl);
exit;
?>
