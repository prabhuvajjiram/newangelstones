<?php
require_once 'includes/config.php';
require_once 'includes/auth_config.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter');
}

// Check for errors
if (isset($_GET['error'])) {
    die('Gmail authorization failed: ' . htmlspecialchars($_GET['error']));
}

// Check for code
if (!isset($_GET['code'])) {
    die('No authorization code received');
}

// Exchange code for tokens
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ])
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    error_log('Gmail auth curl error: ' . curl_error($ch));
    die('Failed to connect to Google servers');
}
curl_close($ch);

$tokens = json_decode($response, true);
if (!isset($tokens['access_token'])) {
    error_log('Gmail token error: ' . print_r($response, true));
    die('Failed to get Gmail access token');
}

// Store tokens in database
$stmt = $pdo->prepare("
    UPDATE users 
    SET oauth_token = ?,
        refresh_token = ?,
        token_expires = DATE_ADD(NOW(), INTERVAL ? SECOND)
    WHERE id = ?
");

try {
    $stmt->execute([
        $tokens['access_token'],
        $tokens['refresh_token'] ?? null,
        $tokens['expires_in'] ?? 3600,
        $_SESSION['user_id']
    ]);

    // Store in session for immediate use
    $_SESSION['access_token'] = $tokens['access_token'];

    // Redirect back
    $redirect_url = $_SESSION['auth_redirect'] ?? 'dashboard.php';
    unset($_SESSION['auth_redirect'], $_SESSION['oauth_state']);
    header('Location: ' . $redirect_url);
    exit();
} catch (PDOException $e) {
    error_log('Database error storing Gmail tokens: ' . $e->getMessage());
    die('Failed to store Gmail authorization. Please try again.');
}
