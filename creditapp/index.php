<?php
// Credit Application Form for Angel Stones
session_start();
// Always generate a fresh CSRF token for each form load
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$status = $_GET['status'] ?? '';
$errors = [];
if ($status === 'validation_error' && isset($_GET['errors'])) {
    $errors = json_decode(urldecode($_GET['errors']), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credit Application - Angel Stones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Ultra-Modern Design for Angel Stones Credit Application */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            /* Professional Business Color Palette */
            --primary-gradient: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            --secondary-gradient: linear-gradient(135deg, #2b6cb0 0%, #3182ce 100%);
            --accent-gradient: linear-gradient(135deg, #4299e1 0%, #63b3ed 100%);
            --success-gradient: linear-gradient(135deg, #38a169 0%, #48bb78 100%);
            
            /* Professional Colors */
            --primary-color: #1a202c;
            --secondary-color: #2d3748;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --blue-dark: #2c5282;
            --blue-light: #4299e1;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f7fafc;
            --gray-100: #edf2f7;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e0;
            --gray-400: #a0aec0;
            --gray-500: #718096;
            --gray-600: #4a5568;
            --gray-700: #2d3748;
            --gray-800: #1a202c;
            
            /* Modern Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-glow: 0 0 20px rgba(102, 126, 234, 0.4);
            
            /* Modern Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --space-4: 1rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --radius-2xl: 1.5rem;
            
            /* Modern Transitions */
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 25%, #4a5568 50%, #718096 75%, #a0aec0 100%);
            background-attachment: fixed;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 15px;
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            margin: 0;
            padding: 0.5rem;
        }
        
        @media (min-width: 768px) {
            body {
                padding: var(--space-4);
            }
        }
        
        .app-container {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-2xl);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0;
            margin: 1rem auto 2rem auto;
            max-width: 1200px;
            position: relative;
            overflow: visible;
            width: calc(100% - 2rem);
            min-height: fit-content;
        }
        
        @media (min-width: 768px) {
            .app-container {
                border-radius: var(--radius-2xl);
            }
        }
        
        .app-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
            z-index: 10;
        }
        
        .form-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 2rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .form-header {
                padding: 3rem 2rem;
                flex-direction: row;
                justify-content: center;
                gap: 2rem;
            }
        }
        
        .header-logo {
            max-width: 80px;
            height: auto;
            z-index: 3;
            position: relative;
        }
        
        @media (min-width: 768px) {
            .header-logo {
                max-width: 100px;
            }
        }
        
        .header-text {
            z-index: 3;
            position: relative;
        }
        
        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        h1 {
            color: var(--white);
            font-weight: 800;
            font-size: clamp(24px, 4vw, 36px);
            margin: 0 0 0.5rem 0;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-subtitle {
            font-size: 18px;
            margin: 0;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            font-weight: 400;
        }
        
        /* Validation Error Styles */
        .validation-errors {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .validation-errors h5 {
            color: #c53030;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .validation-errors ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .validation-errors li {
            color: #742a2a;
            margin-bottom: 0.25rem;
        }
        
        .form-control.is-invalid {
            border-color: #fc8181;
            box-shadow: 0 0 0 0.2rem rgba(252, 129, 129, 0.25);
        }
        
        .form-section {
            margin: 0;
            border: none;
            background: var(--white);
            position: relative;
        }
        
        .form-section:not(:last-child) {
            border-bottom: 1px solid var(--gray-100);
        }
        
        h4 {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            padding: 1rem 1rem;
            border: none;
            position: relative;
            display: flex;
            align-items: center;
            border-left: 6px solid transparent;
            border-image: var(--accent-gradient) 1;
            transition: var(--transition-normal);
        }
        
        @media (min-width: 768px) {
            h4 {
                font-size: 20px;
                padding: 1.5rem 2rem;
            }
        }
        
        h4::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--accent-gradient);
            margin-right: 1rem;
            border-radius: var(--radius-full);
            box-shadow: 0 0 10px rgba(66, 153, 225, 0.5);
        }
        
        h4:hover {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            transform: translateX(4px);
        }
        
        .form-section-inner {
            padding: 1rem;
            background: var(--white);
            position: relative;
        }
        
        @media (min-width: 768px) {
            .form-section-inner {
                padding: 2rem;
            }
        }
        
        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 15px;
            color: var(--gray-700);
            display: block;
            position: relative;
            padding-left: 1rem;
        }
        
        .form-label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 50%;
        }
        
        .form-label.required {
            color: var(--gray-800);
        }
        
        .form-label.required::after {
            content: ' *';
            color: #e53e3e;
            font-weight: 700;
            font-size: 16px;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition-fast);
            background: var(--white);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--radius-lg);
            padding: 1rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            background: transparent;
            border-radius: var(--radius-lg);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-fast);
        }
        
        .btn-outline-primary:hover {
            background: var(--accent-color);
            color: var(--white);
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: var(--radius-lg);
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(56, 161, 105, 0.1) 0%, rgba(72, 187, 120, 0.1) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(229, 62, 62, 0.1) 0%, rgba(245, 101, 101, 0.1) 100%);
            color: #e53e3e;
            border-left: 4px solid #e53e3e;
        }
        
        .download-section {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-radius: var(--radius-xl);
            padding: 1rem;
            margin: 1rem;
            border: 2px dashed var(--gray-300);
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .download-section {
                padding: 1.5rem;
                margin: 2rem;
            }
        }
        
        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .authorization-box {
            background: linear-gradient(135deg, rgba(66, 153, 225, 0.05) 0%, rgba(99, 179, 237, 0.05) 100%);
            border: 2px solid var(--accent-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .text-danger {
            color: #e53e3e !important;
        }
        
        @media (max-width: 767px) {
            .app-container {
                margin: 0.5rem auto;
                border-radius: var(--radius-lg);
                width: calc(100% - 1rem);
                min-height: auto;
            }
            
            h1 {
                font-size: 1.75rem;
            }
            
            .form-subtitle {
                font-size: 16px;
            }
            
            .btn-primary {
                width: 100%;
                padding: 1rem;
            }
            
            .authorization-box {
                margin: 1rem 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php if (!empty($errors)): ?>
    <div class="validation-errors">
        <h5><i class="bi bi-exclamation-triangle-fill"></i> Please correct the following errors:</h5>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="form-header">
        <img src="/images/logo02.png" alt="Angel Stones LLC" class="header-logo">
        <div class="header-text">
            <h1>Credit Application</h1>
            <p class="form-subtitle">Apply for credit with Angel Stones LLC</p>
        </div>
    </div>
    
    <!-- PDF Download Section -->
    <div class="download-section">
        <h5 class="mb-3"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Alternative Option</h5>
        <p class="mb-3">Prefer to fill out a PDF form manually? Download our printable credit application form.</p>
        <a href="/creditapp/Angelstones Credit AP 2025 updated 08 2025.pdf" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-download"></i> Download PDF Form
        </a>
        <small class="d-block mt-2 text-muted">You can print, fill out, and email the completed form to us.</small>
    </div>
    <?php if($status==='success'): ?>
        <div class="alert alert-success">Your application has been submitted successfully.</div>
    <?php elseif($status==='error'): ?>
        <div class="alert alert-danger">There was an error submitting the form. Please try again.</div>
    <?php endif; ?>
    <form action="submit.php" method="post" id="creditAppForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-section">
            <h4>1. Business Information</h4>
            <div class="form-section-inner">
                <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required">Firm Name</label>
                <input type="text" name="firm_name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Subsidiary Of</label>
                <input type="text" name="subsidiary_of" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Business Type</label>
                <select name="business_type" class="form-control" required id="businessTypeSelect">
                    <option value="">Select Business Type</option>
                    <option value="Corporation">Corporation</option>
                    <option value="Partnership">Partnership</option>
                    <option value="Sole Ownership">Sole Ownership</option>
                    <option value="LLC">LLC</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-6 mb-3" id="otherBusinessTypeDiv" style="display: none;">
                <label class="form-label required">Specify Other Business Type</label>
                <input type="text" name="other_business_type" class="form-control" id="otherBusinessTypeInput">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Federal Tax ID or Primary SSN</label>
                <input type="text" name="federal_tax_id" class="form-control" required placeholder="XX-XXXXXXX or XXX-XX-XXXX">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tax Exempt No</label>
                <input type="text" name="tax_exempt_no" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tax Exempt State</label>
                <input type="text" name="tax_exempt_state" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Shipping Address</label>
                <textarea name="shipping_address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Billing Address</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="sameAsShipping">
                    <label class="form-check-label" for="sameAsShipping">
                        Same as Shipping Address
                    </label>
                </div>
                <textarea name="billing_address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label required">Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Web</label>
                <input type="text" name="web" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Fax</label>
                <input type="text" name="fax" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nature of Business</label>
                <input type="text" name="nature_of_business" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date Organized</label>
                <input type="date" name="date_organized" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">State Organized</label>
                <input type="text" name="state_organized" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Years at Address</label>
                <input type="text" name="years_at_address" class="form-control">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>2. Corporate Officers</h4>
            <div class="form-section-inner">
                <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> At least one officer position is required</p>
                <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">President</label>
                <input type="text" name="officer_president" class="form-control" data-officer-field data-label="President Name">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Vice President</label>
                <input type="text" name="officer_vice_president" class="form-control" data-officer-field data-label="Vice President Name">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Secretary</label>
                <input type="text" name="officer_secretary" class="form-control" data-officer-field data-label="Secretary Name">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Treasurer</label>
                <input type="text" name="officer_treasurer" class="form-control" data-officer-field data-label="Treasurer Name">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>3. Owners / Partners</h4>
            <div class="form-section-inner">
                <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> At least one owner/partner is required</p>
                <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Name</label>
                <input type="text" name="owner1_name" class="form-control" data-owner-field data-label="Owner 1 Name">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Percent</label>
                <input type="text" name="owner1_percent" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 1 Address</label>
                <input type="text" name="owner1_address" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 1 Res Phone</label>
                <input type="text" name="owner1_res_phone" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 1 Cell</label>
                <input type="text" name="owner1_cell" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Name</label>
                <input type="text" name="owner2_name" class="form-control" data-owner-field data-label="Owner 2 Name">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Percent</label>
                <input type="text" name="owner2_percent" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Owner 2 Address</label>
                <input type="text" name="owner2_address" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 2 Res Phone</label>
                <input type="text" name="owner2_res_phone" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Owner 2 Cell</label>
                <input type="text" name="owner2_cell" class="form-control">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>4. Financial / Credit References</h4>
            <div class="form-section-inner">
                <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 1 Name</label>
                <input type="text" name="fin1_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 1 Phone</label>
                <input type="text" name="fin1_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin1_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 1 Address</label>
                <input type="text" name="fin1_address" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 2 Name</label>
                <input type="text" name="fin2_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 2 Phone</label>
                <input type="text" name="fin2_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin2_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 2 Address</label>
                <input type="text" name="fin2_address" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Reference 3 Name</label>
                <input type="text" name="fin3_name" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference 3 Phone</label>
                <input type="text" name="fin3_phone" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Acct #</label>
                <input type="text" name="fin3_acct" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Reference 3 Address</label>
                <input type="text" name="fin3_address" class="form-control">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>5. Lending Institution & Creditor</h4>
            <div class="form-section-inner">
                <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Name</label>
                <input type="text" name="lender_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Phone</label>
                <input type="text" name="lender_phone" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Lender Address</label>
                <input type="text" name="lender_address" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Lender Acct #</label>
                <input type="text" name="lender_acct" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Name</label>
                <input type="text" name="creditor_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Phone</label>
                <input type="text" name="creditor_phone" class="form-control">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Creditor Address</label>
                <input type="text" name="creditor_address" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Creditor Acct #</label>
                <input type="text" name="creditor_acct" class="form-control">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>6. Trade References</h4>
            <div class="form-section-inner">
                <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 1 Name</label>
                <input type="text" name="trade1_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 1 Contact</label>
                <input type="text" name="trade1_contact" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 2 Name</label>
                <input type="text" name="trade2_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 2 Contact</label>
                <input type="text" name="trade2_contact" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 3 Name</label>
                <input type="text" name="trade3_name" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade 3 Contact</label>
                <input type="text" name="trade3_contact" class="form-control">
            </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h4>7. Personal Guarantee & Authorization</h4>
            <div class="form-section-inner">
        <div class="terms-text mb-4">
            <p class="mb-3">
            The undersigned represents that the above stated information is correct and true to the best of his/her knowledge 
            as of the date stated herein. This application is submitted to Angel Stones LLC for the purpose of obtaining 
            credit with Angel Stones LLC.  Upon acceptance of this application by Angel Stones LLC, the undersigned 
            agrees to pay and abide by the terms of payment set forth and agreed upon at the time of purchase. All invoices 
            must be paid within the terms, permitted by and specified on, each sales invoice. All payments for goods and 
            services received beyond the terms set forth and agreed upon are considered late and thus subject to a 1.5% 
            monthly interest charge (18% APR). Furthermore, applicant acknowledges, understands and/or agrees to the 
            financial policy held by Angel Stones LLC which states that any and all customers who knowingly default on 
            their financial obligation to pay for goods and services purchased from Angel Stones LLC will be held 
            responsible for (in addition to all incurred interest charges) all costs associated with the collection of any and all 
            monies owed to Angel Stones LLC including, but not limited to, all collection agency commissions and fees, all 
            attorney fees, all legal filing fees, all court costs (and all fees associated therein) and any and all fees associated 
            with trial proceedings and processes surrounding judgment execution. 
            </p>
            
            <p class="mb-3">
                In consideration of credit extended by Angel Stones LLC the undersigned does jointly and severally personally 
                guarantee to pay and be responsible for payment of all sums, balances, and accounts due to Angel Stones LLC.
            </p>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label required">Full Name (Guarantor 1)</label>
                <input type="text" name="guarantor1_full_name" class="form-control" required data-guarantor-field data-label="Guarantor 1 Full Name">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="guarantor1_date" class="form-control">
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label">Full Name (Guarantor 2)</label>
                <input type="text" name="guarantor2_full_name" class="form-control" data-guarantor-field data-label="Guarantor 2 Full Name">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="guarantor2_date" class="form-control">
            </div>
                </div>
                
                <!-- Unified Authorization & Terms Agreement -->
                <div class="authorization-box">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="authorize" name="authorization" required>
                        <label class="form-check-label fw-bold" for="authorize">
                            <i class="bi bi-shield-check text-primary"></i> Digital Authorization & Terms Agreement
                        </label>
                    </div>
                    <div class="mt-2 small">
                        <p class="mb-2">
                            By checking this box, I hereby authorize and digitally sign this credit application. I certify that all information provided is true and accurate to the best of my knowledge. I understand that this digital authorization has the same legal effect as a handwritten signature.
                        </p>
                        <p class="mb-0">
                            I also agree to Angel Stones' <a href="/terms-of-service.html" target="_blank" class="text-decoration-none">Terms of Service</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

        <div class="text-center" style="padding: 1rem 1rem 2rem 1rem;">
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </div>
        
    </form>
    
    <div class="text-center" style="padding: 1rem; border-top: 1px solid #e2e8f0; margin-top: 1rem; color: #718096;">
        <a href="/privacy-policy.html" style="color: #4299e1; text-decoration: none;">Privacy Policy</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    const form = document.getElementById('creditAppForm');
    // Enhanced client-side validation
    function validateFederalTaxId(taxId) {
        const cleaned = taxId.replace(/[^0-9\-]/g, '');
        return /^\d{2}-\d{7}$/.test(cleaned) || /^\d{3}-\d{2}-\d{4}$/.test(cleaned) || /^\d{9}$/.test(cleaned);
    }
    
    function validatePhone(phone) {
        const cleaned = phone.replace(/[^0-9]/g, '');
        return cleaned.length === 10;
    }
    
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function validateAddress(address) {
        return address.length >= 10 && /\d/.test(address);
    }
    
    function validatePersonName(name) {
        return name.length >= 2 && /^[a-zA-Z\s\.\-']+$/.test(name);
    }
    
    function showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('is-invalid');
            // Remove error class after user starts typing
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            }, { once: true });
        }
    }
    
    function clearFieldError(fieldName) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('is-invalid');
        }
    }
    
    form.addEventListener('submit', function(e){
        e.preventDefault();
        
        // Clear previous validation states
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        let hasErrors = false;
        const errors = [];
        
        // Validate business name
        const businessNameField = document.querySelector('[name="firm_name"]');
        if (businessNameField) {
            const businessName = businessNameField.value.trim();
            if (!businessName || businessName.length < 2) {
                errors.push('Business Name is required (minimum 2 characters)');
                showFieldError('firm_name', 'Invalid business name');
                hasErrors = true;
            }
        }
        
        // Validate business type
        const businessTypeField = document.querySelector('[name="business_type"]');
        if (businessTypeField) {
            const businessType = businessTypeField.value;
            if (!businessType) {
                errors.push('Business Type is required');
                showFieldError('business_type', 'Business type required');
                hasErrors = true;
            } else if (businessType === 'Other') {
                const otherTypeField = document.querySelector('[name="other_business_type"]');
                if (otherTypeField) {
                    const otherType = otherTypeField.value.trim();
                    if (!otherType) {
                        errors.push('Please specify the Other Business Type');
                        showFieldError('other_business_type', 'Other business type required');
                        hasErrors = true;
                    }
                }
            }
        }
        
        // Validate Federal Tax ID
        const federalTaxIdField = document.querySelector('[name="federal_tax_id"]');
        if (federalTaxIdField) {
            const federalTaxId = federalTaxIdField.value.trim();
            if (!federalTaxId || !validateFederalTaxId(federalTaxId)) {
                errors.push('Valid Federal Tax ID or SSN is required (format: XX-XXXXXXX or XXX-XX-XXXX)');
                showFieldError('federal_tax_id', 'Invalid tax ID format');
                hasErrors = true;
            }
        }
        
        // Validate phone
        const phone = document.querySelector('[name="phone"]').value.trim();
        if (!phone || !validatePhone(phone)) {
            errors.push('Valid 10-digit phone number is required');
            showFieldError('phone', 'Invalid phone number');
            hasErrors = true;
        }
        
        // Validate email
        const email = document.querySelector('[name="email"]').value.trim();
        if (!email || !validateEmail(email)) {
            errors.push('Valid email address is required');
            showFieldError('email', 'Invalid email address');
            hasErrors = true;
        }
        
        // Validate addresses
        const shippingAddress = document.querySelector('[name="shipping_address"]').value.trim();
        if (!shippingAddress || !validateAddress(shippingAddress)) {
            errors.push('Shipping Address is required (must include street number)');
            showFieldError('shipping_address', 'Invalid shipping address');
            hasErrors = true;
        }
        
        const billingAddress = document.querySelector('[name="billing_address"]').value.trim();
        if (!billingAddress || !validateAddress(billingAddress)) {
            errors.push('Billing Address is required (must include street number)');
            showFieldError('billing_address', 'Invalid billing address');
            hasErrors = true;
        }
        
        // Validate at least one officer
        const officerFields = document.querySelectorAll('[data-officer-field]');
        const hasOfficer = Array.from(officerFields).some(field => field.value.trim() !== '');
        if (!hasOfficer) {
            errors.push('At least one Corporate Officer is required');
            hasErrors = true;
        }
        
        // Validate officer names
        officerFields.forEach(field => {
            const value = field.value.trim();
            if (value && !validatePersonName(value)) {
                errors.push(`${field.getAttribute('data-label')} must be a valid name (letters only)`);
                showFieldError(field.name, 'Invalid name format');
                hasErrors = true;
            }
        });
        
        // Validate at least one owner
        const ownerFields = document.querySelectorAll('[data-owner-field]');
        const hasOwner = Array.from(ownerFields).some(field => field.value.trim() !== '');
        if (!hasOwner) {
            errors.push('At least one Owner/Partner is required');
            hasErrors = true;
        }
        
        // Validate owner names
        ownerFields.forEach(field => {
            const value = field.value.trim();
            if (value && !validatePersonName(value)) {
                errors.push(`${field.getAttribute('data-label')} must be a valid name (letters only)`);
                showFieldError(field.name, 'Invalid name format');
                hasErrors = true;
            }
        });
        
        // Validate guarantor names
        const guarantorFields = document.querySelectorAll('[data-guarantor-field]');
        guarantorFields.forEach(field => {
            const value = field.value.trim();
            if (value && !validatePersonName(value)) {
                errors.push(`${field.getAttribute('data-label')} must be a valid name (letters only)`);
                showFieldError(field.name, 'Invalid name format');
                hasErrors = true;
            }
        });
        
        // Validate digital authorization
        const authorization = document.querySelector('[name="authorization"]');
        if (!authorization || !authorization.checked) {
            errors.push('Digital Authorization agreement is required');
            hasErrors = true;
        }
        
        if (hasErrors) {
            // Show errors at top of form
            let errorHtml = '<div class="validation-errors"><h5><i class="bi bi-exclamation-triangle-fill"></i> Please correct the following errors:</h5><ul>';
            errors.forEach(error => {
                errorHtml += `<li>${error}</li>`;
            });
            errorHtml += '</ul></div>';
            
            // Remove existing error display
            const existingErrors = document.querySelector('.validation-errors');
            if (existingErrors) {
                existingErrors.remove();
            }
            
            // Insert new errors at top
            const container = document.querySelector('.app-container');
            container.insertAdjacentHTML('afterbegin', errorHtml);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return false;
        }

        // Submit form (reCAPTCHA disabled for local testing)
        form.submit();
    });
    
    // Auto-set today's date for guarantor 1 if empty
    document.addEventListener('DOMContentLoaded', function() {
        const dateField = document.querySelector('input[name="guarantor1_date"]');
        if (dateField && !dateField.value) {
            dateField.value = new Date().toISOString().split('T')[0];
        }
        
        // Handle business type dropdown
        const businessTypeSelect = document.getElementById('businessTypeSelect');
        const otherBusinessTypeDiv = document.getElementById('otherBusinessTypeDiv');
        const otherBusinessTypeInput = document.getElementById('otherBusinessTypeInput');
        
        businessTypeSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                otherBusinessTypeDiv.style.display = 'block';
                otherBusinessTypeInput.required = true;
            } else {
                otherBusinessTypeDiv.style.display = 'none';
                otherBusinessTypeInput.required = false;
                otherBusinessTypeInput.value = '';
            }
        });
        
        // Real-time input formatting and validation
        
        // Smart Federal Tax ID formatting (FEIN: XX-XXXXXXX or SSN: XXX-XX-XXXX)
        const federalTaxIdField = document.querySelector('[name="federal_tax_id"]');
        if (federalTaxIdField) {
            // Add helper text
            const helpText = document.createElement('small');
            helpText.className = 'form-text text-muted';
            helpText.innerHTML = 'FEIN: XX-XXXXXXX (business) or SSN: XXX-XX-XXXX (individual)';
            federalTaxIdField.parentNode.appendChild(helpText);
            
            federalTaxIdField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^0-9]/g, ''); // Remove all non-digits
                
                // Limit to 9 digits max
                if (value.length > 9) {
                    value = value.substring(0, 9);
                }
                
                // Smart formatting based on business context
                if (value.length >= 2) {
                    const businessType = document.querySelector('[name="business_type"]')?.value;
                    const isBusiness = businessType && !businessType.includes('Individual') && !businessType.includes('Sole');
                    
                    // FEIN format for businesses: XX-XXXXXXX
                    if (isBusiness && value.length > 2) {
                        value = value.substring(0, 2) + '-' + value.substring(2);
                    } else if (value.length >= 3) {
                        // SSN format for individuals: XXX-XX-XXXX
                        if (value.length <= 5) {
                            value = value.substring(0, 3) + '-' + value.substring(3);
                        } else {
                            value = value.substring(0, 3) + '-' + value.substring(3, 5) + '-' + value.substring(5);
                        }
                    }
                }
                
                e.target.value = value;
            });
            
            // Business type change handler to reformat tax ID
            const businessTypeField = document.querySelector('[name="business_type"]');
            if (businessTypeField) {
                businessTypeField.addEventListener('change', function() {
                    const taxIdValue = federalTaxIdField.value.replace(/[^0-9]/g, '');
                    if (taxIdValue.length >= 2) {
                        // Trigger reformatting
                        federalTaxIdField.value = taxIdValue;
                        federalTaxIdField.dispatchEvent(new Event('input'));
                    }
                });
            }
            
            federalTaxIdField.addEventListener('blur', function(e) {
                const value = e.target.value.trim();
                if (value && !validateFederalTaxId(value)) {
                    showFieldError('federal_tax_id', 'Invalid format. Use XX-XXXXXXX (FEIN) or XXX-XX-XXXX (SSN)');
                } else {
                    clearFieldError('federal_tax_id');
                }
            });
        }
        
        // Phone number formatting (XXX) XXX-XXXX
        const phoneFields = document.querySelectorAll('[name="phone"], [name="fax"]');
        phoneFields.forEach(field => {
            field.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^0-9]/g, ''); // Remove all non-digits
                
                // Limit to 10 digits
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                // Format as (XXX) XXX-XXXX
                if (value.length >= 6) {
                    value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6);
                } else if (value.length >= 3) {
                    value = '(' + value.substring(0, 3) + ') ' + value.substring(3);
                }
                
                e.target.value = value;
            });
            
            // Prevent non-numeric input
            field.addEventListener('keypress', function(e) {
                if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        });
        
        // Name fields - only allow letters, spaces, periods, hyphens, apostrophes
        const nameFields = document.querySelectorAll('[data-officer-field], [data-owner-field], [data-guarantor-field]');
        nameFields.forEach(field => {
            field.addEventListener('keypress', function(e) {
                if (!/[a-zA-Z\s\.\-']/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            });
            
            // Clean up input on blur
            field.addEventListener('blur', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-Z\s\.\-']/g, '').trim();
            });
        });
        
        // Business name - allow letters, numbers, basic punctuation
        const businessNameField = document.querySelector('[name="firm_name"]');
        if (businessNameField) {
            businessNameField.addEventListener('keypress', function(e) {
                if (!/[a-zA-Z0-9\s\.\,\&\-']/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }
        
        // State field - only allow 2 uppercase letters
        const stateField = document.querySelector('[name="state"]');
        if (stateField) {
            stateField.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
                if (value.length > 2) {
                    value = value.substring(0, 2);
                }
                e.target.value = value;
            });
            
            stateField.addEventListener('keypress', function(e) {
                if (!/[a-zA-Z]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }
        
        // Same as Shipping Address checkbox functionality
        const sameAsShippingCheckbox = document.getElementById('sameAsShipping');
        const shippingAddressField = document.querySelector('[name="shipping_address"]');
        const billingAddressField = document.querySelector('[name="billing_address"]');
        
        if (sameAsShippingCheckbox && shippingAddressField && billingAddressField) {
            sameAsShippingCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    billingAddressField.value = shippingAddressField.value;
                    billingAddressField.readOnly = true;
                    billingAddressField.style.backgroundColor = '#f8f9fa';
                } else {
                    billingAddressField.readOnly = false;
                    billingAddressField.style.backgroundColor = '';
                }
            });
            
            // Update billing when shipping changes (if checkbox is checked)
            shippingAddressField.addEventListener('input', function() {
                if (sameAsShippingCheckbox.checked) {
                    billingAddressField.value = this.value;
                }
            });
        }
    });
</script>
</body>
</html>
