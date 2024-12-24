<?php
class GmailMailer {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
            throw new Exception('Gmail API configuration is missing');
        }
    }

    public function sendEmail($to, $subject, $body, $attachmentPath = null) {
        require_once __DIR__ . '/../vendor/autoload.php';

        try {
            // Get user's tokens from database
            $stmt = $this->pdo->prepare("SELECT oauth_token, refresh_token FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['email']]);
            $user = $stmt->fetch();

            error_log("Gmail tokens for {$_SESSION['email']}: " . print_r($user, true));

            // Check for refresh token
            if (empty($user['refresh_token'])) {
                error_log("No refresh token found for user {$_SESSION['email']}");
                throw new Exception('Gmail authentication required', 401);
            }

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465; // Use SSL port
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS

            // Set debug options
            $mail->SMTPDebug = 4;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Set OAuth using the refresh token
            $oauth = new PHPMailer\PHPMailer\OAuth([
                'clientId' => GOOGLE_CLIENT_ID,
                'clientSecret' => GOOGLE_CLIENT_SECRET,
                'refreshToken' => $user['refresh_token'],
                'userName' => $_SESSION['email']
            ]);

            $mail->setOAuth($oauth);
            $mail->setFrom($_SESSION['email']);
            $mail->addAddress($to);
            
            try {
                $ccAddress = $_SESSION['email'];
                $mail->addCC($ccAddress);
                error_log("Added CC recipient: " . $ccAddress);
            } catch (Exception $e) {
                error_log("Failed to add BCC: " . $e->getMessage());
            }
            try {
                $bccAddress = 'teams@theangelstones.com';
                $mail->addBCC($bccAddress);
                error_log("Added BCC recipient: " . $bccAddress);
            } catch (Exception $e) {
                error_log("Failed to add BCC: " . $e->getMessage());
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($attachmentPath && file_exists($attachmentPath)) {
                error_log("Adding attachment: $attachmentPath");
                $filename = basename($attachmentPath);
                if (!$mail->addAttachment($attachmentPath, $filename)) {
                    throw new Exception("Failed to add attachment");
                }
                error_log("Attachment added successfully");
            }

            error_log("Attempting to send email...");
            $result = $mail->send();
            error_log("Email sent successfully");
            
            return $result;

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            // Check for authentication errors
            if ($e->getCode() === 401 || 
                strpos($e->getMessage(), 'OAuth') !== false || 
                strpos($e->getMessage(), 'authentication') !== false) {
                // Clear stored token to force re-authentication
                $stmt = $this->pdo->prepare("UPDATE users SET refresh_token = NULL WHERE email = ?");
                $stmt->execute([$_SESSION['email']]);
                throw new Exception('Gmail authentication required', 401);
            }
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
}
