<?php
require_once 'includes/config.php';
require_once 'includes/auth_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling with debugging
function handleError($message) {
    error_log("Auth Error: " . $message);
    $_SESSION['auth_error'] = $message;
    header('Location: login.php?error=' . urlencode($message));
    exit;
}

// Debug: Log the incoming request
error_log("Received callback with code: " . $_GET['code']);

// Check for error parameter in the callback
if (isset($_GET['error'])) {
    handleError("Google authentication error: " . $_GET['error']);
}

// Check for the authorization code
if (!isset($_GET['code'])) {
    handleError("No authorization code received");
}

// Exchange the authorization code for tokens
$token_url = "https://oauth2.googleapis.com/token";
$token_data = [
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

error_log("Requesting token with data: " . print_r($token_data, true));

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    handleError("cURL Error during token exchange: " . $err);
}

error_log("Token response: " . $response);

$tokens = json_decode($response, true);
if (!isset($tokens['access_token'])) {
    handleError("Failed to get access token. Response: " . $response);
}

// Get user info with the access token
$userinfo_url = "https://www.googleapis.com/oauth2/v2/userinfo";
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokens['access_token']
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    handleError("cURL Error during user info request: " . $err);
}

error_log("User info response: " . $response);

$user_info = json_decode($response, true);
if (!isset($user_info['email'])) {
    handleError("Failed to get user email. Response: " . $response);
}

// Validate email domain
if (!validateUserEmail($user_info['email'])) {
    handleError("Email domain not allowed. Please use your @theangelstones.com account.");
}

// Store user info in session
$_SESSION['user_email'] = $user_info['email'];
$_SESSION['user_name'] = $user_info['name'];
$_SESSION['access_token'] = $tokens['access_token'];
if (isset($tokens['refresh_token'])) {
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
}
$_SESSION['token_expires'] = time() + $tokens['expires_in'];
$_SESSION['logged_in'] = true;

error_log("Session data set: " . print_r($_SESSION, true));

// Redirect to the dashboard or home page
header('Location: index.php');
exit;
