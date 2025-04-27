<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs using modern PHP methods instead of deprecated FILTER_SANITIZE_STRING
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Get and sanitize POST data
    $name = isset($_POST["name"]) ? sanitizeInput($_POST["name"]) : '';
    $email = isset($_POST["email"]) ? sanitizeInput($_POST["email"]) : '';
    $mobile = isset($_POST["mobile"]) ? sanitizeInput($_POST["mobile"]) : '';
    $subject = isset($_POST["subject"]) ? sanitizeInput($_POST["subject"]) : '';
    $messageContent = isset($_POST["message"]) ? $_POST["message"] : ''; // Allow HTML in message content
    
    // Check if required fields are provided
    if (empty($name) || empty($email) || empty($subject) || empty($messageContent)) {
        echo "error: Required fields are missing";
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "error: Invalid email format";
        exit;
    }
    
    // Set recipient based on message type or use default
    $to = "info@theangelstones.com"; // Default recipient
    
    // Check if this is a payment confirmation email
    if (strpos($subject, 'Payment Confirmation') !== false) {
        $to = "da@theangelstones.com, da@theangelstones.com";
    }
    
    // Set email headers
    $headers = "From: info@theangelstones.com\r\n"; // Always use a domain-matching From address
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: $email\r\n";
    }
    $headers .= "Return-Path: info@theangelstones.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Create HTML message
    $message = "<html><body>";
    
    // Check if this is a payment confirmation or regular contact form
    if (strpos($subject, 'Payment Confirmation') !== false) {
        $message .= "<h1>Angel Stones - Payment Confirmation</h1>";
    } else {
        $message .= "<h1>Angel Stones - Website Enquiry Form</h1>";
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
    
    $message .= "</body></html>";
    
    // Log email attempt for debugging
    $logFile = fopen(__DIR__ . '/email_log.txt', 'a');
    fwrite($logFile, "=== Email Send Attempt: " . date('Y-m-d H:i:s') . " ===\n");
    fwrite($logFile, "To: $to\n");
    fwrite($logFile, "Subject: $subject\n");
    fwrite($logFile, "From Name: $name\n");
    fwrite($logFile, "From Email: $email\n");
    
    // Send email and log result
    $mailResult = mail($to, $subject, $message, $headers);
    fwrite($logFile, "Mail Result: " . ($mailResult ? "Success" : "Failed") . "\n");
    
    if (!$mailResult) {
        $error = error_get_last();
        if ($error) {
            fwrite($logFile, "PHP Error: " . print_r($error, true) . "\n");
        }
    }
    
    fwrite($logFile, "=== End of Log Entry ===\n\n");
    fclose($logFile);
    
    // Return response to client
    if ($mailResult) {
        echo "success";
    } else {
        echo "error: Failed to send email";
    }
} else {
    echo "error: Invalid request method";
}
?>
