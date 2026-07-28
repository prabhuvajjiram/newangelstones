<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Payment confirmations must be posted by the payment processor.';
    exit;
}

function paymentField(string $key, string $fallback = ''): string {
    return trim((string)($_POST[$key] ?? $fallback));
}

function verifyConvergeResponse(array $payload): bool {
    $secret = getenv('CONVERGE_RESPONSE_SECRET') ?: '';
    $signature = $_SERVER['HTTP_X_CONVERGE_SIGNATURE'] ?? ($payload['signature'] ?? '');

    if ($secret === '' || $signature === '') {
        return false;
    }

    $signedFields = [
        $payload['ssl_txn_id'] ?? '',
        $payload['ssl_amount'] ?? '',
        $payload['ssl_invoice_number'] ?? '',
        $payload['ssl_result'] ?? '',
    ];
    $expected = hash_hmac('sha256', implode('|', $signedFields), $secret);
    return hash_equals($expected, $signature);
}

$verifiedPayment = verifyConvergeResponse($_POST);

// Capture data from Converge response
$amount = paymentField('ssl_amount', paymentField('Total'));
$invoice = paymentField('ssl_invoice_number', paymentField('Invoice Number'));

// Extract customer information from SSL fields
$firstName = paymentField('ssl_first_name');
$lastName = paymentField('ssl_last_name');
$name = !empty($firstName) || !empty($lastName) ? trim($firstName . ' ' . $lastName) : paymentField('Sample Name');
$company = paymentField('ssl_company');
$email = paymentField('ssl_email', paymentField('sample@email.com'));
$phone = paymentField('ssl_phone', paymentField('Phone'));

// Extract address information from SSL fields
$addressLine = paymentField('ssl_avs_address');
$city = paymentField('ssl_city');
$state = paymentField('ssl_state');
$zip = paymentField('ssl_avs_zip');
$country = paymentField('ssl_country');

// Combine address components if available
$fullAddress = '';
if (!empty($addressLine)) {
    $fullAddress .= $addressLine;
    if (!empty($city) || !empty($state) || !empty($zip)) {
        $fullAddress .= ', ';
    }
}
if (!empty($city)) {
    $fullAddress .= $city;
    if (!empty($state) || !empty($zip)) {
        $fullAddress .= ', ';
    }
}
if (!empty($state)) {
    $fullAddress .= $state;
    if (!empty($zip)) {
        $fullAddress .= ' ';
    }
}
if (!empty($zip)) {
    $fullAddress .= $zip;
}
if (!empty($country) && $country != 'USA' && $country != 'US') {
    $fullAddress .= !empty($fullAddress) ? ', ' . $country : $country;
}

// Use the constructed address or fall back to previous methods
$address = !empty($fullAddress) ? $fullAddress : paymentField('105 Sample Street');

// Extract transaction details
$txnId = paymentField('ssl_txn_id', paymentField('Merchant Transaction ID'));
$approvalCode = paymentField('ssl_approval_code', paymentField('Approval Number'));

// Check for error codes in the response
$errorCode = paymentField('errorCode');
$errorName = paymentField('errorName');
$errorMessage = paymentField('errorMessage');

// Determine payment status based on error codes
if (!empty($errorCode) || !empty($errorName) || !empty($errorMessage)) {
    $paymentStatus = 'declined';
    $declineReason = !empty($errorMessage) ? $errorMessage : (!empty($errorName) ? $errorName : 'Transaction Declined');
    if (!empty($errorCode)) {
        $declineReason = "Error $errorCode: $declineReason";
    }
} else {
    // If no explicit error, check for status in POST or GET
    $paymentStatus = paymentField('ssl_result_message', paymentField('Status'));
    $declineReason = paymentField('Decline Reason');
}

$transactionType = paymentField('ssl_transaction_type');

// If we don't have specific fields, try to get them from the general POST data
if (empty($invoice) || empty($amount) || empty($name) || empty($email) || empty($phone) || empty($address) || empty($txnId) || empty($approvalCode)) {
    foreach ($_POST as $key => $value) {
        if (empty($invoice) && (stripos($key, 'invoice') !== false || stripos($key, 'order') !== false)) {
            $invoice = $value;
        } else if (empty($amount) && (stripos($key, 'amount') !== false || stripos($key, 'total') !== false || stripos($key, 'price') !== false)) {
            $amount = $value;
        } else if (empty($name) && (stripos($key, 'name') !== false || stripos($key, 'customer') !== false)) {
            $name = $value;
        } else if (empty($email) && (stripos($key, 'email') !== false || stripos($key, 'mail') !== false)) {
            $email = $value;
        } else if (empty($phone) && (stripos($key, 'phone') !== false || stripos($key, 'mobile') !== false || stripos($key, 'cell') !== false)) {
            $phone = $value;
        } else if (empty($address) && (stripos($key, 'address') !== false || stripos($key, 'street') !== false || stripos($key, 'location') !== false)) {
            $address = $value;
        } else if (empty($txnId) && (stripos($key, 'transaction') !== false || stripos($key, 'txn') !== false || stripos($key, 'id') !== false)) {
            $txnId = $value;
        } else if (empty($approvalCode) && (stripos($key, 'approval') !== false || stripos($key, 'auth') !== false || stripos($key, 'code') !== false)) {
            $approvalCode = $value;
        } else if (empty($paymentStatus) && (stripos($key, 'status') !== false || stripos($key, 'result') !== false)) {
            $paymentStatus = $value;
        } else if (empty($declineReason) && (stripos($key, 'decline') !== false || stripos($key, 'reason') !== false || stripos($key, 'error') !== false)) {
            $declineReason = $value;
        }
    }
}

// Normalize payment status to either 'approved' or 'declined'
$paymentStatus = strtolower(trim($paymentStatus));
if ($paymentStatus != 'declined' && $paymentStatus != 'decline' && $paymentStatus != 'failed' && $paymentStatus != 'failure' && $paymentStatus != 'error' && $paymentStatus != 'rejected') {
    // Only default to approved if there are no error codes
    if (empty($errorCode) && empty($errorName) && empty($errorMessage)) {
        $paymentStatus = 'approved'; // Default to approved if not explicitly declined and no errors
    } else {
        $paymentStatus = 'declined';
    }
} else {
    $paymentStatus = 'declined';
}

if (!$verifiedPayment) {
    $paymentStatus = 'pending';
    $declineReason = 'Payment response is pending server verification.';
}

// If still no invoice, use a default
if (empty($invoice)) {
    $invoice = "AG-" . date('YmdHis');
}

// If still no amount, use a default
if (empty($amount)) {
    $amount = "0.00";
}

// Set date
$date = date("F j, Y, g:i a");

// Log transaction details for debugging
$logData = [
    'date' => $date,
    'status' => $paymentStatus,
    'invoice' => $invoice,
    'amount' => $amount,
    'name' => $name,
    'company' => $company,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'txnId' => $txnId,
    'approvalCode' => $approvalCode,
    'errorCode' => $errorCode,
    'errorName' => $errorName,
    'errorMessage' => $errorMessage,
    'declineReason' => $declineReason,
    'transactionType' => $transactionType,
    'verified' => $verifiedPayment
];
error_log('Payment transaction data: ' . json_encode($logData, JSON_PRETTY_PRINT));

// Include the payment email helper
require_once __DIR__ . '/send_payment_email.php';

// Send payment confirmation email only after the response is verified.
$emailResult = ['success' => false, 'message' => 'Payment response pending verification'];
if ($verifiedPayment) {
    $emailResult = sendPaymentConfirmationEmail(
        $invoice,
        $amount,
        $date,
        $name,
        $email,
        $phone,
        $address,
        $txnId,
        $approvalCode,
        $paymentStatus,
        $declineReason
    );
}

// Set page title based on payment status
$pageTitle = ($paymentStatus == 'declined') ? "Payment Declined" : (($paymentStatus == 'pending') ? "Payment Received" : "Payment Confirmation");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Angel Granites</title>
    
    <!-- Preload fonts and styles -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Didact+Gothic&display=swap" as="style">
    
    <!-- Load fonts and styles -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <style>
        :root {
            --as-font: "Didact Gothic", sans-serif;
            --as-heading: "Playfair Display", serif;
            --as-primary: #d6b772;
            --as-bg-dark-deep-2: #101010;
            --as-text-light: #cfcfcf;
            --as-danger: #e74c3c;
        }
        
        body {
            font-family: var(--as-font);
            color: var(--as-text-light);
            background: var(--as-bg-dark-deep-2);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }
        
        .header {
            text-align: center;
            padding: 30px 0;
        }
        
        .logo {
            max-width: 250px;
            height: auto;
            margin-bottom: 20px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--as-heading);
            color: var(--as-primary);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        
        .confirmation-card {
            background-color: rgba(34, 35, 38, 0.7);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .confirmation-message {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-message i {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .confirmation-message i.success {
            color: var(--as-primary);
        }
        
        .confirmation-message i.declined {
            color: var(--as-danger);
        }
        
        .receipt-details {
            margin-top: 30px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(214, 183, 114, 0.2);
        }
        
        .receipt-label {
            font-weight: bold;
            color: var(--as-primary);
            flex: 0 0 40%;
        }
        
        .receipt-value {
            flex: 0 0 60%;
            text-align: right;
        }
        
        .btn-return {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--as-primary);
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-return:hover {
            background-color: #c4a764;
            transform: translateY(-2px);
        }
        
        .btn-download {
            display: inline-block;
            padding: 12px 25px;
            background-color: transparent;
            color: var(--as-primary);
            text-decoration: none;
            border: 2px solid var(--as-primary);
            border-radius: 5px;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-download:hover {
            background-color: rgba(214, 183, 114, 0.1);
            transform: translateY(-2px);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.3);
            margin-top: auto;
        }
        
        .footer p {
            margin: 0;
            color: var(--as-text-light);
        }
        
        .error-details {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--as-danger);
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .error-details h3 {
            color: var(--as-danger);
            margin-top: 0;
        }
        
        @media (max-width: 768px) {
            .receipt-row {
                flex-direction: column;
            }
            
            .receipt-label, .receipt-value {
                flex: 0 0 100%;
                text-align: left;
            }
            
            .receipt-value {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Try SVG logo first, with fallbacks -->
            <img src="images/ag_logo.svg" alt="Angel Granites Logo" class="logo" onerror="this.onerror=null; this.src='images/Angel Granites Logo_350dpi.png'; this.onerror=null;" />
            <h1><?php echo $pageTitle; ?></h1>
        </div>
        
        <div class="confirmation-card">
            <div class="confirmation-message">
                <?php if (strtolower($paymentStatus) == 'pending'): ?>
                <i class="fas fa-clock success"></i>
                <h2>Payment Received</h2>
                <p>Your payment response was received and is pending server verification.</p>
                <?php elseif (strtolower($paymentStatus) == 'declined'): ?>
                <i class="fas fa-times-circle declined"></i>
                <h2>Payment Declined</h2>
                <p>Unfortunately, your payment was declined. Please try again or contact us for assistance.</p>
                <?php else: ?>
                <i class="fas fa-check-circle success"></i>
                <h2>Payment Successful!</h2>
                <p>Thank you for your payment. Your transaction has been completed successfully.</p>
                <?php endif; ?>
            </div>
            
            <?php if (strtolower($paymentStatus) == 'declined' && (!empty($errorCode) || !empty($errorName) || !empty($errorMessage))): ?>
            <div class="error-details">
                <h3>Transaction Error Details</h3>
                <?php if (!empty($errorCode)): ?>
                <p><strong>Error Code:</strong> <?php echo htmlspecialchars($errorCode); ?></p>
                <?php endif; ?>
                <?php if (!empty($errorName)): ?>
                <p><strong>Error Type:</strong> <?php echo htmlspecialchars($errorName); ?></p>
                <?php endif; ?>
                <?php if (!empty($errorMessage)): ?>
                <p><strong>Error Message:</strong> <?php echo htmlspecialchars($errorMessage); ?></p>
                <?php endif; ?>
                <p>Please contact our support team for assistance with this transaction.</p>
            </div>
            <?php endif; ?>
            
            <div class="receipt-details">
                <?php if (!empty($invoice)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Invoice Number:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($invoice); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($amount)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Amount:</div>
                    <div class="receipt-value">$<?php echo htmlspecialchars($amount); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($name)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Customer Name:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($name); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($company)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Company:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($company); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($email)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Email:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($email); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($phone)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Phone:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($phone); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($address)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Address:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($address); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($cardNumber)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Card Number:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($cardNumber); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($expDate)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Expiration Date:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($expDate); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transactionType)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Transaction Type:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($transactionType); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($txnId)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Transaction ID:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($txnId); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($approvalCode)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Approval Code:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($approvalCode); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="receipt-row">
                    <div class="receipt-label">Date:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($date); ?></div>
                </div>
                
                <div class="receipt-row">
                    <div class="receipt-label">Payment Method:</div>
                    <div class="receipt-value">Credit Card (Converge)</div>
                </div>
                
                <div class="receipt-row">
                    <div class="receipt-label">Status:</div>
                    <div class="receipt-value"><?php echo ucfirst(htmlspecialchars($paymentStatus)); ?></div>
                </div>
                
                <?php if (strtolower($paymentStatus) == 'declined' && !empty($declineReason)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Decline Reason:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($declineReason); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center" style="margin-top: 30px; text-align: center;">
                <a href="https://theangelstones.com" class="btn-return">
                    <i class="fas fa-arrow-left me-2"></i> Return to Angel Granites
                </a>
                
                <a href="receipt-generator.php?invoice=<?php echo urlencode($invoice); ?>&amount=<?php echo urlencode($amount); ?>&name=<?php echo urlencode($name); ?>&email=<?php echo urlencode($email); ?>&phone=<?php echo urlencode($phone); ?>&address=<?php echo urlencode($address); ?>&txnid=<?php echo urlencode($txnId); ?>&approval=<?php echo urlencode($approvalCode); ?>&status=<?php echo urlencode($paymentStatus); ?><?php if ($paymentStatus == 'declined'): ?>&decline_reason=<?php echo urlencode($declineReason); ?><?php endif; ?>" class="btn-download" target="_blank">
                    <i class="fas fa-download me-2"></i> Download Receipt
                </a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Angel Granites. All rights reserved.</p>
    </div>
</body>
</html>
