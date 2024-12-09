<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/auth_config.php';

error_log("Email Auth Callback - Starting");
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

if (!isset($_GET['code'])) {
    $_SESSION['error'] = "Authorization code not received";
    header('Location: quotes.php');
    exit();
}

// Get the correct redirect URI
$redirect_uri = $is_local ? 
    'http://localhost:3000/crm/email_auth_callback.php' : 
    'https://www.theangelstones.com/crm/email_auth_callback.php';

// Exchange authorization code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = array(
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
);

// Get tokens
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$token_response = curl_exec($ch);
curl_close($ch);

error_log("Token response: " . print_r($token_response, true));
error_log("Decoded token data: " . print_r($token_data, true));

$token_data = json_decode($token_response, true);

if (isset($token_data['refresh_token'])) {
    try {
        // Store refresh token in database
        $stmt = $pdo->prepare("UPDATE users SET refresh_token = ? WHERE email = ?");
        $success = $stmt->execute([
            $token_data['refresh_token'],
            $_SESSION['email']
        ]);
        
        if (!$success) {
            error_log("Database error: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Failed to update database");
        }
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT refresh_token FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        $updated = $stmt->fetch();
        error_log("Updated user tokens: " . print_r($updated, true));
        
        $_SESSION['success'] = 'Email authentication successful!';
        
        // Get the stored quote ID and return URL
        $redirect_url = $_SESSION['auth_redirect'] ?? 'quotes.php';
        if (isset($_SESSION['pending_quote_id'])) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 
                            'resend=' . $_SESSION['pending_quote_id'];
            unset($_SESSION['pending_quote_id']);
        }
        unset($_SESSION['auth_redirect']);
        
        header('Location: ' . $redirect_url);
        exit;
    } catch (Exception $e) {
        error_log("Failed to store refresh token: " . $e->getMessage());
        $_SESSION['error'] = "Failed to store authentication token";
        header('Location: quotes.php');
        exit;
    }
} else {
    error_log("No refresh token received. Token data: " . print_r($token_data, true));
    $_SESSION['error'] = "Failed to get refresh token from Google";
    header('Location: quotes.php');
}
exit(); 