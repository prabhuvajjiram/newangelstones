<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify state to prevent CSRF
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter. Possible CSRF attack.');
}

// Check for authorization code
if (!isset($_GET['code'])) {
    die('No authorization code received.');
}

// Exchange authorization code for tokens
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    die('Failed to get access token. Error: ' . $response);
}

$tokens = json_decode($response, true);

// Store tokens in database
try {
    // Store access token
    $stmt = $conn->prepare("
        INSERT INTO email_settings (setting_name, setting_value, encrypted) 
        VALUES ('access_token', ?, true)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$tokens['access_token'], $tokens['access_token']]);

    // Store refresh token if provided
    if (isset($tokens['refresh_token'])) {
        $stmt = $conn->prepare("
            INSERT INTO email_settings (setting_name, setting_value, encrypted) 
            VALUES ('refresh_token', ?, true)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$tokens['refresh_token'], $tokens['refresh_token']]);
    }

    $_SESSION['success_message'] = 'Gmail integration successfully configured!';
    header('Location: settings.php');
    exit();

} catch (PDOException $e) {
    die('Failed to store tokens: ' . $e->getMessage());
}
