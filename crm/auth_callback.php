<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth_config.php';

// Define admin base URL
$server_name = $_SERVER['SERVER_NAME'];
if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
    define('ADMIN_BASE_URL', '/crm/');
} else if ($server_name === 'localhost' || $server_name === '127.0.0.1') {
    define('ADMIN_BASE_URL', 'http://localhost:3000/crm/');
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port = $_SERVER['SERVER_PORT'];
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    define('ADMIN_BASE_URL', $protocol . $server_name . $port_suffix . '/crm/');
}

if (!isset($_GET['code'])) {
    $_SESSION['error'] = "Authorization code not received";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}

// Exchange authorization code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = array(
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
);

// Initialize cURL session for token request
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development
$token_response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));
    $_SESSION['error'] = "Failed to connect to Google servers";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}
curl_close($ch);

$token_data = json_decode($token_response, true);

if (!isset($token_data['access_token'])) {
    error_log('Token error: ' . print_r($token_response, true));
    $_SESSION['error'] = "Failed to get access token";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}

// Get user info using the access token
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token_data['access_token']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development
$user_info_response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));
    $_SESSION['error'] = "Failed to get user information";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}
curl_close($ch);

$google_user = json_decode($user_info_response, true);

if (!isset($google_user['email'])) {
    error_log('User info error: ' . print_r($user_info_response, true));
    $_SESSION['error'] = "Failed to get user email";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$google_user['email']]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_login = NOW(),
                google_id = ?,
                oauth_token = ?,
                first_name = ?,
                last_name = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $google_user['id'],
            $token_data['access_token'],
            $google_user['given_name'] ?? '',
            $google_user['family_name'] ?? '',
            $user['id']
        ]);
    } else {
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, google_id, oauth_provider, oauth_token, first_name, last_name, created_at)
            VALUES (?, ?, ?, 'google', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $google_user['email'],
            $google_user['email'],
            $google_user['id'],
            $token_data['access_token'],
            $google_user['given_name'] ?? '',
            $google_user['family_name'] ?? ''
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Assign roles based on email
        if ($google_user['email'] === 'info@theangelstones.com') {
            // Get admin role ID
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
            $stmt->execute();
            $admin_role_id = $stmt->fetchColumn();
            
            // Assign admin role
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $admin_role_id]);
        } else {
            // Assign default staff role
            $stmt = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT ?, id FROM roles WHERE name = 'staff'
            ");
            $stmt->execute([$user_id]);
        }
        
        // Get the new user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'] ?? $pdo->lastInsertId();
    $_SESSION['email'] = $google_user['email'];
    $_SESSION['username'] = $google_user['email'];
    $_SESSION['first_name'] = $google_user['given_name'] ?? '';
    $_SESSION['last_name'] = $google_user['family_name'] ?? '';
    
    // Get user roles
    $stmt = $pdo->prepare("
        SELECT r.name 
        FROM roles r 
        JOIN user_roles ur ON r.id = ur.role_id 
        WHERE ur.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = $stmt->fetchColumn() ?: 'staff'; // Default to staff if no role found

    // Debug log
    error_log("User logged in: " . print_r($user, true));
    error_log("Session role: " . $_SESSION['role']);

    // Redirect to index.php or stored URL after successful login
    $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
    unset($_SESSION['redirect_after_login']); // Clear stored URL
    header('Location: ' . ADMIN_BASE_URL . $redirect_url);
    exit();

} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    $_SESSION['error'] = "Authentication failed. Please try again.";
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit();
}

if (isset($token_data['access_token'])) {
    // Update user's OAuth token in database
    $stmt = $pdo->prepare("UPDATE users SET oauth_token = ? WHERE email = ?");
    $stmt->execute([$token_data['access_token'], $_SESSION['email']]);
}

if (isset($token_data['refresh_token'])) {
    // Update user's Gmail refresh token in database
    $stmt = $pdo->prepare("UPDATE users SET gmail_refresh_token = ? WHERE email = ?");
    $stmt->execute([$token_data['refresh_token'], $_SESSION['email']]);
}
