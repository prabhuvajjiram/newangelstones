<?php
session_start();
require_once 'config.php';

if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for access token
        $token_data = http_build_query([
            'code' => $_GET['code'],
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'redirect_uri' => GMAIL_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ]);
        
        $ch = curl_init(GMAIL_TOKEN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $token_data);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $token_info = json_decode($response, true);
        
        if (!isset($token_info['access_token'])) {
            throw new Exception('Failed to get access token. Response: ' . $response . ' HTTP Code: ' . $http_code);
        }
        
        // Store tokens in session
        $_SESSION['access_token'] = $token_info['access_token'];
        $_SESSION['refresh_token'] = $token_info['refresh_token'] ?? null;
        $_SESSION['token_expires'] = time() + ($token_info['expires_in'] - 300); // 5 minutes buffer
        
        // Get user email
        $ch = curl_init(GMAIL_USER_INFO_URL . '?access_token=' . $token_info['access_token']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $user_info = json_decode($response, true);
        $_SESSION['email'] = $user_info['email'];
        
        // Redirect back to main page
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<p><a href="index.php">Try Again</a></p>';
    }
} else {
    echo '<div class="error">No authorization code received</div>';
    echo '<p><a href="index.php">Try Again</a></p>';
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 0 20px;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

a {
    color: #4285f4;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
