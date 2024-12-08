<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

// Function to generate authorization URL
function getAuthUrl() {
    $params = [
        'client_id' => GMAIL_CLIENT_ID,
        'redirect_uri' => GMAIL_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => implode(' ', GMAIL_SCOPES),
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    return GMAIL_AUTH_URL . '?' . http_build_query($params);
}

// Function to send email
function sendEmail($to, $subject, $message, $access_token) {
    $mail = new PHPMailer();
    
    try {
        $mail->isSMTP();
        $mail->setFrom($_SESSION['email']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->setOAuth(new OAuth([
            'clientId' => GMAIL_CLIENT_ID,
            'clientSecret' => GMAIL_CLIENT_SECRET,
            'refreshToken' => $_SESSION['refresh_token'],
            'userName' => $_SESSION['email'],
            'accessToken' => $access_token
        ]));
        
        return $mail->send();
    } catch (Exception $e) {
        throw new Exception('Email Error: ' . $mail->ErrorInfo);
    }
}

// Function to make API requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    return json_decode($response, true);
}

// Main logic
$error = '';
$success = '';

try {
    if (!isset($_SESSION['access_token'])) {
        // Not authenticated, show login button
        echo '<h1>Gmail Test</h1>';
        echo '<a href="' . getAuthUrl() . '" class="button">Connect Gmail Account</a>';
    } else {
        // Check if token is expired
        if (time() >= $_SESSION['token_expires']) {
            // Token expired, need to refresh
            $refresh_data = http_build_query([
                'client_id' => GMAIL_CLIENT_ID,
                'client_secret' => GMAIL_CLIENT_SECRET,
                'refresh_token' => $_SESSION['refresh_token'],
                'grant_type' => 'refresh_token'
            ]);
            
            $response = makeRequest(GMAIL_TOKEN_URL, 'POST', $refresh_data);
            
            if (isset($response['access_token'])) {
                $_SESSION['access_token'] = $response['access_token'];
                $_SESSION['token_expires'] = time() + ($response['expires_in'] - 300); // 5 minutes buffer
            } else {
                throw new Exception('Failed to refresh token');
            }
        }
        
        // Show email interface
        echo '<h1>Gmail Test</h1>';
        echo '<p>Connected as: ' . htmlspecialchars($_SESSION['email']) . '</p>';
        echo '<hr>';
        
        // Handle email sending
        if (isset($_POST['send'])) {
            $to = $_POST['to'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            
            if (sendEmail($to, $subject, $message, $_SESSION['access_token'])) {
                $success = 'Email sent successfully!';
            }
        }
        
        // Show send email form
        if ($success) {
            echo '<div class="success">' . htmlspecialchars($success) . '</div>';
        }
        if ($error) {
            echo '<div class="error">' . htmlspecialchars($error) . '</div>';
        }
        
        echo '<h2>Send Test Email</h2>';
        echo '<form method="post">';
        echo '<div><label>To:</label><input type="email" name="to" required></div>';
        echo '<div><label>Subject:</label><input type="text" name="subject" required></div>';
        echo '<div><label>Message:</label><textarea name="message" required></textarea></div>';
        echo '<div><button type="submit" name="send">Send Email</button></div>';
        echo '</form>';
        
        echo '<p><a href="logout.php">Disconnect Gmail Account</a></p>';
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    echo '<div class="error">' . htmlspecialchars($error) . '</div>';
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 0 20px;
}

h1, h2 {
    color: #333;
}

form {
    margin: 20px 0;
}

form div {
    margin: 10px 0;
}

label {
    display: inline-block;
    width: 100px;
    font-weight: bold;
}

input[type="email"],
input[type="text"] {
    width: 300px;
    padding: 5px;
}

textarea {
    width: 300px;
    height: 100px;
    padding: 5px;
}

button, .button {
    background: #4285f4;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    border-radius: 4px;
}

button:hover, .button:hover {
    background: #357abd;
}

.success {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}
</style>
