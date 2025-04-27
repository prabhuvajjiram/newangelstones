<?php
/**
 * Payment Email Sender
 * Using PHPMailer with Gmail SMTP
 */

// Define secure access
define('SECURE_ACCESS', true);

// Load configuration from secure file
$config_path = __DIR__ . '/email_config.php';
if (!file_exists($config_path)) {
    error_log('Email configuration file not found');
    die('Email configuration error');
}
require_once $config_path;

require_once __DIR__ . '/crm/vendor/phpmailer/PHPMailer.php';
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send a payment confirmation email using Gmail SMTP
 * 
 * @param string $invoice Invoice number
 * @param string $amount Payment amount
 * @param string $date Payment date
 * @param string $name Customer name
 * @param string $email Customer email
 * @param string $phone Customer phone
 * @param string $address Customer address
 * @param string $txnId Transaction ID
 * @param string $approvalCode Approval code
 * @param string $paymentStatus Payment status (approved or declined)
 * @param string $declineReason Reason for decline if payment was declined
 * @return array Result with success status and message
 */
function sendPaymentConfirmationEmail($invoice, $amount, $date, $name, $email, $phone, $address, $txnId, $approvalCode, $paymentStatus = 'approved', $declineReason = '') {
    try {
        $mail = new PHPMailer();
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Gmail credentials
        $mail->From = SMTP_FROM_EMAIL;
        $mail->FromName = SMTP_FROM_NAME;
        $mail->addAddress(SMTP_TO_EMAIL);
        
        // Content
        $mail->isHTML(true);
        
        // Set subject based on payment status
        if (strtolower($paymentStatus) == 'declined') {
            $mail->Subject = "Payment Declined - Invoice #" . $invoice;
        } else {
            $mail->Subject = "Payment Confirmation - Invoice #" . $invoice;
        }
        
        // Create HTML message body
        $body = "<html><body>";
        
        if (strtolower($paymentStatus) == 'declined') {
            $body .= "<h1 style='color: #e74c3c;'>Angel Stones - Payment Declined</h1>";
        } else {
            $body .= "<h1 style='color: #d6b772;'>Angel Stones - Payment Confirmation</h1>";
        }
        
        $body .= "<p><strong>Invoice #:</strong> " . $invoice . "</p>";
        $body .= "<p><strong>Amount:</strong> $" . $amount . "</p>";
        $body .= "<p><strong>Date:</strong> " . $date . "</p>";
        $body .= "<p><strong>Status:</strong> " . ucfirst($paymentStatus) . "</p>";
        
        if (strtolower($paymentStatus) == 'declined' && !empty($declineReason)) {
            $body .= "<p><strong>Decline Reason:</strong> " . $declineReason . "</p>";
        }
        
        // Only include customer information if available
        if (!empty($name)) {
            $body .= "<p><strong>Customer Name:</strong> " . $name . "</p>";
        }
        if (!empty($email)) {
            $body .= "<p><strong>Email:</strong> " . $email . "</p>";
        }
        if (!empty($phone)) {
            $body .= "<p><strong>Phone:</strong> " . $phone . "</p>";
        }
        if (!empty($address)) {
            $body .= "<p><strong>Address:</strong> " . $address . "</p>";
        }
        if (!empty($txnId)) {
            $body .= "<p><strong>Transaction ID:</strong> " . $txnId . "</p>";
        }
        if (!empty($approvalCode)) {
            $body .= "<p><strong>Approval Code:</strong> " . $approvalCode . "</p>";
        }
        
        $body .= "</body></html>";
        $mail->Body = $body;
        
        // Send email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log('Payment email error: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// If this file is called directly, handle the request
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Check if this is a POST request with payment data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get payment details from POST data
        $invoice = $_POST['invoice'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $date = $_POST['date'] ?? date("F j, Y, g:i a");
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $txnId = $_POST['txnid'] ?? '';
        $approvalCode = $_POST['approval'] ?? '';
        $paymentStatus = $_POST['status'] ?? 'approved';
        $declineReason = $_POST['decline_reason'] ?? '';
        
        // Normalize payment status
        $paymentStatus = strtolower(trim($paymentStatus));
        if ($paymentStatus != 'declined' && $paymentStatus != 'decline' && $paymentStatus != 'failed' && $paymentStatus != 'failure' && $paymentStatus != 'error' && $paymentStatus != 'rejected') {
            $paymentStatus = 'approved';
        } else {
            $paymentStatus = 'declined';
        }
        
        // Send email
        $result = sendPaymentConfirmationEmail(
            $invoice, $amount, $date, $name, $email, $phone, $address, $txnId, $approvalCode, $paymentStatus, $declineReason
        );
        
        // Return result as JSON
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        // Not a POST request
        header('HTTP/1.1 405 Method Not Allowed');
        echo 'Method not allowed';
    }
}
?>
