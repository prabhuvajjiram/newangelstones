<?php
// Capture data from Converge response
$amount = isset($_POST['Total']) ? $_POST['Total'] : (isset($_GET['amount']) ? $_GET['amount'] : '');
$invoice = isset($_POST['Invoice Number']) ? $_POST['Invoice Number'] : (isset($_GET['invoice']) ? $_GET['invoice'] : '');
$name = isset($_POST['Sample Name']) ? $_POST['Sample Name'] : (isset($_GET['name']) ? $_GET['name'] : '');
$email = isset($_POST['sample@email.com']) ? $_POST['sample@email.com'] : (isset($_GET['email']) ? $_GET['email'] : '');
$phone = isset($_POST['Phone']) ? $_POST['Phone'] : (isset($_GET['phone']) ? $_GET['phone'] : '');
$address = isset($_POST['105 Sample Street']) ? $_POST['105 Sample Street'] : (isset($_GET['address']) ? $_GET['address'] : '');
$txnId = isset($_POST['Merchant Transaction ID']) ? $_POST['Merchant Transaction ID'] : (isset($_GET['txnid']) ? $_GET['txnid'] : '');
$approvalCode = isset($_POST['Approval Number']) ? $_POST['Approval Number'] : (isset($_GET['approval']) ? $_GET['approval'] : '');

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
        }
    }
}

// Fallback to GET parameters if still empty
if (empty($invoice) && isset($_GET['invoice'])) $invoice = $_GET['invoice'];
if (empty($amount) && isset($_GET['amount'])) $amount = $_GET['amount'];
if (empty($name) && isset($_GET['name'])) $name = $_GET['name'];
if (empty($email) && isset($_GET['email'])) $email = $_GET['email'];
if (empty($phone) && isset($_GET['phone'])) $phone = $_GET['phone'];
if (empty($address) && isset($_GET['address'])) $address = $_GET['address'];
if (empty($txnId) && isset($_GET['txnid'])) $txnId = $_GET['txnid'];
if (empty($approvalCode) && isset($_GET['approval'])) $approvalCode = $_GET['approval'];

// If still no invoice, use a default
if (empty($invoice)) {
    $invoice = "INV-" . date('YmdHis');
}

// If still no amount, use a default
if (empty($amount)) {
    $amount = "0.00";
}

// Set date
$date = date("F j, Y, g:i a");

// Set page title
$pageTitle = "Payment Confirmation";
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
            color: var(--as-primary);
            margin-bottom: 20px;
            display: block;
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
            flex: 1;
        }
        
        .receipt-value {
            flex: 2;
            text-align: right;
        }
        
        .btn-return {
            display: inline-block;
            background-color: transparent;
            color: var(--as-primary);
            border: 2px solid var(--as-primary);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-family: var(--as-font);
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .btn-return:hover {
            background-color: var(--as-primary);
            color: var(--as-bg-dark-deep-2);
        }
        
        .btn-download {
            display: inline-block;
            background-color: var(--as-primary);
            color: var(--as-bg-dark-deep-2);
            border: 2px solid var(--as-primary);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-family: var(--as-font);
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-download:hover {
            background-color: transparent;
            color: var(--as-primary);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: auto;
            background-color: rgba(34, 35, 38, 0.9);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .logo {
                max-width: 200px;
            }
            
            .confirmation-card {
                padding: 20px;
            }
            
            .receipt-row {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .receipt-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Try SVG logo first, with fallbacks -->
            <img src="images/Angel Granites Logo.svg" alt="Angel Granites Logo" class="logo" onerror="this.onerror=null; this.src='images/Angel Granites Logo_350dpi.png'; this.onerror=null;" />
            <h1><?php echo $pageTitle; ?></h1>
        </div>
        
        <div class="confirmation-card">
            <div class="confirmation-message">
                <i class="fas fa-check-circle"></i>
                <h2>Payment Successful!</h2>
                <p>Thank you for your payment. Your transaction has been completed successfully.</p>
            </div>
            
            <div class="receipt-details">
                <?php if (!empty($invoice)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Invoice Number:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($invoice); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($amount)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Amount Paid:</div>
                    <div class="receipt-value">$<?php echo htmlspecialchars($amount); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($name)): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Customer Name:</div>
                    <div class="receipt-value"><?php echo htmlspecialchars($name); ?></div>
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
                    <div class="receipt-value">Completed</div>
                </div>
            </div>
            
            <div class="text-center" style="margin-top: 30px; text-align: center;">
                <a href="https://theangelstones.com" class="btn-return">
                    <i class="fas fa-arrow-left me-2"></i> Return to Angel Granites
                </a>
                
                <a href="receipt-generator.php?invoice=<?php echo urlencode($invoice); ?>&amount=<?php echo urlencode($amount); ?>&name=<?php echo urlencode($name); ?>&email=<?php echo urlencode($email); ?>&phone=<?php echo urlencode($phone); ?>&address=<?php echo urlencode($address); ?>&txnid=<?php echo urlencode($txnId); ?>&approval=<?php echo urlencode($approvalCode); ?>" class="btn-download" target="_blank">
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
