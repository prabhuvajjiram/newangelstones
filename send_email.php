<?php
// Production version - Angel Stones Contact Form Processing
// Disable error display for production
ini_set('display_errors', 0);
error_reporting(0);

// Define secure access for email config
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Load email configuration
$config_path = __DIR__ . '/email_config.php';
if (!file_exists($config_path)) {
    echo "error: Email configuration not found";
    exit;
}
require_once $config_path;

// Load PHPMailer for better Gmail integration
$phpmailer_path = __DIR__ . '/crm/vendor/phpmailer/PHPMailer.php';
$usePhpMailer = false;

if (file_exists($phpmailer_path)) {
    require_once $phpmailer_path;
    require_once __DIR__ . '/crm/vendor/phpmailer/Exception.php';
    $usePhpMailer = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Enhanced input sanitization
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Enhanced validation function
    function validateField($value, $type = 'text', $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'phone':
                return preg_match('/^[\d\s\-\+\(\)\.]+$/', $value);
            case 'text':
                return strlen($value) <= 1000; // Reasonable length limit
            default:
                return true;
        }
    }
    
    // Get and sanitize POST data with enhanced validation - capture all dynamic fields
    $name = isset($_POST["name"]) ? sanitizeInput($_POST["name"]) : '';
    $email = isset($_POST["email"]) ? sanitizeInput($_POST["email"]) : '';
    $mobile = isset($_POST["mobile"]) ? sanitizeInput($_POST["mobile"]) : '';
    $subject = isset($_POST["subject"]) ? sanitizeInput($_POST["subject"]) : '';
    $messageContent = isset($_POST["message"]) ? $_POST["message"] : ''; // Allow HTML in message content
    
    // Capture additional dynamic fields that might be submitted
    $additionalFields = [];
    $standardFields = ['name', 'email', 'mobile', 'subject', 'message', 'submit', 'action', 'csrf_token'];
    
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $standardFields) && !empty($value)) {
            $additionalFields[$key] = sanitizeInput($value);
        }
    }
    
    // Enhanced validation with specific error messages
    $errors = [];
    
    if (!validateField($name, 'text', true)) {
        $errors[] = "Name is required and must be valid";
    }
    
    if (!validateField($email, 'email', true)) {
        $errors[] = "Valid email address is required";
    }
    
    if (!validateField($subject, 'text', true)) {
        $errors[] = "Subject is required";
    }
    
    if (!validateField($messageContent, 'text', true)) {
        $errors[] = "Message is required";
    }
    
    if (!empty($mobile) && !validateField($mobile, 'phone', false)) {
        $errors[] = "Phone number format is invalid";
    }
    
    // Return validation errors
    if (!empty($errors)) {
        echo "error: " . implode(", ", $errors);
        exit;
    }
    
    // Set recipient based on message type or use default
    $to = "da@theangelstones.com";
    
    // Check if this is a payment confirmation email
    if (strpos($subject, 'Payment Confirmation') !== false) {
        $to = "da@theangelstones.com";
    }
    
    // Create HTML message
    $message = "<html><body>";
    
    // Check if this is a payment confirmation or regular contact form
    if (strpos($subject, 'Payment Confirmation') !== false) {
        $message .= "<h1>Angel Stones - Payment Confirmation</h1>";
    } else {
        $message .= "<h1>Angel Stones - Website Contact Form</h1>";
    }
    
    $message .= "<p><strong>Name:</strong> $name</p>";
    $message .= "<p><strong>Email:</strong> $email</p>";
    
    if (!empty($mobile)) {
        $message .= "<p><strong>Mobile:</strong> $mobile</p>";
    }
    
    $message .= "<p><strong>Subject:</strong> $subject</p>";
    
    // For payment confirmations, the message already contains formatted HTML
    if (strpos($subject, 'Payment Confirmation') !== false) {
        $message .= "<div>$messageContent</div>";
    } else {
        $message .= "<p><strong>Message:</strong></p>";
        $message .= "<p>" . nl2br(htmlspecialchars($messageContent, ENT_QUOTES, 'UTF-8')) . "</p>";
    }
    
    // Add additional dynamic fields if any were submitted
    if (!empty($additionalFields)) {
        $message .= "<h2>Additional Information</h2>";
        $message .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $message .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
        
        foreach ($additionalFields as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $displayValue = is_array($value) ? implode(', ', array_filter($value)) : $value;
            if (!empty($displayValue)) {
                $message .= "<tr><td><strong>{$label}</strong></td><td>{$displayValue}</td></tr>";
            }
        }
        
        $message .= "</table>";
    }
    
    // Add form submission details
    $message .= "<h2>Form Submission Details</h2>";
    $message .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    $message .= "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Value</th></tr>";
    $message .= "<tr><td><strong>Submission Time</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
    $message .= "<tr><td><strong>IP Address</strong></td><td>" . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</td></tr>";
    $message .= "<tr><td><strong>User Agent</strong></td><td>" . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</td></tr>";
    if (isset($_SERVER['HTTP_REFERER'])) {
        $message .= "<tr><td><strong>Referrer</strong></td><td>" . htmlspecialchars($_SERVER['HTTP_REFERER']) . "</td></tr>";
    }
    $message .= "</table>";
    
    $message .= "</body></html>";
    
    $mailResult = false;
    
    // Try PHPMailer first for better Gmail integration
    if ($usePhpMailer) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Email settings
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to, 'Angel Stones Support');
            // Note: This PHPMailer implementation doesn't have addReplyTo method
            // Reply-To will be set in fallback headers
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // Send email
            $mailResult = $mail->send();
            
        } catch (Exception $e) {
            $mailResult = false;
        }
    }
    
    // Fallback to PHP mail() function if PHPMailer fails or is not available
    if (!$mailResult) {
        // Set email headers for fallback
        $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers .= "Reply-To: $email\r\n";
        }
        $headers .= "Return-Path: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $mailResult = mail($to, $subject, $message, $headers);
    }
    
    // Return response to client
    if ($mailResult) {
        echo "success";
    } else {
        echo "error: Failed to send email. Please try again or contact support directly.";
    }
} else {
    echo "error: Invalid request method";
}
?>
