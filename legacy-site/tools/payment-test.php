<?php
/**
 * Payment Test Page
 * This page allows testing of both approved and declined payment confirmations
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Test - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        
        .card {
            background-color: rgba(34, 35, 38, 0.7);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: none;
        }
        
        .form-label {
            color: var(--as-primary);
        }
        
        .form-control {
            background-color: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(214, 183, 114, 0.3);
            color: var(--as-text-light);
        }
        
        .form-control:focus {
            background-color: rgba(30, 30, 30, 0.8);
            border-color: var(--as-primary);
            color: var(--as-text-light);
            box-shadow: 0 0 0 0.25rem rgba(214, 183, 114, 0.25);
        }
        
        .btn-primary {
            background-color: var(--as-primary);
            border-color: var(--as-primary);
            color: #000;
        }
        
        .btn-primary:hover {
            background-color: #c4a85e;
            border-color: #c4a85e;
            color: #000;
        }
        
        .btn-outline-primary {
            border-color: var(--as-primary);
            color: var(--as-primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--as-primary);
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="images/ag_logo.svg" alt="Angel Stones Logo" class="logo" onerror="this.onerror=null; this.src='images/Angel Granites Logo_350dpi.png'; this.onerror=null;" />
            <h1>Payment Test Page</h1>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <h3>Approved Payment Test</h3>
                    <p>Use this form to test a successful payment confirmation.</p>
                    
                    <form action="payment-confirmation.php" method="get">
                        <div class="mb-3">
                            <label for="invoice1" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice1" name="invoice" value="AG-<?php echo date('YmdHis'); ?>-A">
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount1" class="form-label">Amount</label>
                            <input type="text" class="form-control" id="amount1" name="amount" value="199.99">
                        </div>
                        
                        <div class="mb-3">
                            <label for="name1" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="name1" name="name" value="John Doe">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email1" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email1" name="email" value="john.doe@example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone1" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone1" name="phone" value="555-123-4567">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address1" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address1" name="address" value="123 Main St, Anytown, USA">
                        </div>
                        
                        <div class="mb-3">
                            <label for="txnid1" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="txnid1" name="txnid" value="TXN<?php echo rand(100000, 999999); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="approval1" class="form-label">Approval Code</label>
                            <input type="text" class="form-control" id="approval1" name="approval" value="AP<?php echo rand(10000, 99999); ?>">
                        </div>
                        
                        <input type="hidden" name="status" value="approved">
                        
                        <button type="submit" class="btn btn-primary">Test Approved Payment</button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <h3>Declined Payment Test</h3>
                    <p>Use this form to test a declined payment notification.</p>
                    
                    <form action="payment-confirmation.php" method="get">
                        <div class="mb-3">
                            <label for="invoice2" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice2" name="invoice" value="AG-<?php echo date('YmdHis'); ?>-D">
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount2" class="form-label">Amount</label>
                            <input type="text" class="form-control" id="amount2" name="amount" value="299.99">
                        </div>
                        
                        <div class="mb-3">
                            <label for="name2" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="name2" name="name" value="Jane Smith">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email2" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email2" name="email" value="jane.smith@example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone2" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone2" name="phone" value="555-987-6543">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address2" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address2" name="address" value="456 Oak St, Somewhere, USA">
                        </div>
                        
                        <div class="mb-3">
                            <label for="txnid2" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="txnid2" name="txnid" value="TXN<?php echo rand(100000, 999999); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="approval2" class="form-label">Approval Code</label>
                            <input type="text" class="form-control" id="approval2" name="approval" value="">
                        </div>
                        
                        <div class="mb-3">
                            <label for="decline_reason" class="form-label">Decline Reason</label>
                            <select class="form-control" id="decline_reason" name="decline_reason">
                                <option value="Insufficient funds">Insufficient funds</option>
                                <option value="Card expired">Card expired</option>
                                <option value="Invalid card number">Invalid card number</option>
                                <option value="Transaction declined by issuer">Transaction declined by issuer</option>
                                <option value="CVV verification failed">CVV verification failed</option>
                                <option value="Address verification failed">Address verification failed</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="status" value="declined">
                        
                        <button type="submit" class="btn btn-outline-primary">Test Declined Payment</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h3>How to Use This Test Page</h3>
                    <p>This page allows you to test both approved and declined payment scenarios:</p>
                    <ol>
                        <li>Fill out the form with test data or use the pre-filled values</li>
                        <li>Click the appropriate button to simulate an approved or declined payment</li>
                        <li>You will be redirected to the payment confirmation page with the appropriate status</li>
                        <li>An email will be sent to the configured recipient (da@theangelstones.com)</li>
                    </ol>
                    <p>This helps verify that both payment confirmation and decline scenarios work correctly.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
