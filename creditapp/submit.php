<?php
// Start session and CSRF protection
session_start();

// Debug flag - set to false to disable debug logging
$debug_enabled = true;

// Create debug directory if it doesn't exist
$debug_dir = __DIR__ . '/debug';
if (!is_dir($debug_dir)) {
    mkdir($debug_dir, 0755, true);
}

// Start debug logging immediately - write to file right away
$debug_file = $debug_dir . '/submit_debug_' . date('Y-m-d_H-i-s') . '.log';
$debug_content = "=== SUBMIT.PHP DEBUG - " . date('Y-m-d H:i:s') . " ===\n";
$debug_content .= "Script started successfully\n";
$debug_content .= "Session ID: " . session_id() . "\n";
$debug_content .= "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$debug_content .= "POST data received: " . (empty($_POST) ? 'NO' : 'YES') . "\n";
$debug_content .= "Debug file path: " . $debug_file . "\n";
$debug_content .= "Debug directory writable: " . (is_writable($debug_dir) ? 'YES' : 'NO') . "\n";

// Force write debug log immediately - this should always happen
file_put_contents($debug_file, $debug_content, LOCK_EX);
chmod($debug_file, 0666); // Ensure file is readable

// CSRF token validation
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $debug_content .= "CSRF validation failed\n";
    $debug_content .= "POST csrf_token: " . ($_POST['csrf_token'] ?? 'NOT SET') . "\n";
    $debug_content .= "SESSION csrf_token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";
    if ($debug_enabled) {
        file_put_contents($debug_file, $debug_content);
    }
    header('Location: index.php?status=error');
    exit;
}

// Invalidate token after successful validation to prevent reuse
unset($_SESSION['csrf_token']);
$debug_content .= "CSRF validation passed\n";

// reCAPTCHA v3 validation (disabled for local testing)

// Check if reCAPTCHA is enabled (set to false for local testing)
$recaptchaEnabled = defined('RECAPTCHA_ENABLED') ? RECAPTCHA_ENABLED : false;

if ($recaptchaEnabled) {
    $debug_content .= "reCAPTCHA validation enabled\n";
    // Load reCAPTCHA secret from config or environment
    $recaptchaSecret = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : (getenv('RECAPTCHA_SECRET_KEY') ?: 'YOUR_SECRET_KEY');
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $recaptchaVerified = false;
    
    if ($recaptchaSecret && $recaptchaResponse) {
        $params = [
            'secret'   => $recaptchaSecret,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        $context = stream_context_create([
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($params),
                'timeout' => 10,
            ]
        ]);
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($verify !== false) {
            $captcha = json_decode($verify, true);
            $recaptchaVerified = ($captcha['success'] ?? false) &&
                ($captcha['score'] ?? 0) >= 0.5 &&
                ($captcha['action'] ?? '') === 'creditapp';
        }
    }
    
    if (!$recaptchaVerified) {
        $debug_content .= "reCAPTCHA validation failed\n";
        if ($debug_enabled) {
            file_put_contents($debug_file, $debug_content);
        }
        header('Location: index.php?status=error');
        exit;
    }
    $debug_content .= "reCAPTCHA validation passed\n";
} else {
    $debug_content .= "reCAPTCHA validation disabled - skipping\n";
    // reCAPTCHA disabled - skip validation for local testing
    // In production, set RECAPTCHA_ENABLED to true in email_config.php
}

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Validation functions
function validateBusinessName($name) {
    return !empty($name) && strlen($name) >= 2 && strlen($name) <= 100 && 
           preg_match('/^[a-zA-Z0-9\s\.\,\&\-\']+$/', $name);
}

function validateFederalTaxId($taxId) {
    // Remove all non-digits and hyphens
    $cleaned = preg_replace('/[^0-9\-]/', '', $taxId);
    
    // Federal EIN format: XX-XXXXXXX (9 digits total)
    if (preg_match('/^\d{2}-\d{7}$/', $cleaned)) {
        return true;
    }
    
    // SSN format: XXX-XX-XXXX (9 digits total)
    if (preg_match('/^\d{3}-\d{2}-\d{4}$/', $cleaned)) {
        return true;
    }
    
    // Just 9 digits without hyphens
    if (preg_match('/^\d{9}$/', $cleaned)) {
        return true;
    }
    
    return false;
}

function validateEmail($email) {
    return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 100;
}

function validatePhone($phone) {
    // Remove all non-digits
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    // Must be 10 digits (US phone number)
    return strlen($cleaned) === 10;
}

function validateAddress($address) {
    return !empty($address) && strlen($address) >= 10 && strlen($address) <= 200 &&
           preg_match('/\d/', $address); // Must contain at least one number
}

function validatePersonName($name) {
    return !empty($name) && strlen($name) >= 2 && strlen($name) <= 50 &&
           preg_match('/^[a-zA-Z\s\.\-\']+$/', $name);
}

function validateState($state) {
    return !empty($state) && strlen($state) === 2 && 
           preg_match('/^[A-Z]{2}$/', strtoupper($state));
}

// Form validation
$errors = [];

// Debug flag - set to false to disable debug logging
$debug_enabled = true;

// Create debug directory if it doesn't exist
$debug_dir = __DIR__ . '/debug';
if (!is_dir($debug_dir)) {
    mkdir($debug_dir, 0755, true);
}

// Debug: Log all POST data to file
$debug_file = $debug_dir . '/validation_debug_' . date('Y-m-d_H-i-s') . '.log';
$debug_content = "=== FORM SUBMISSION DEBUG - " . date('Y-m-d H:i:s') . " ===\n";
$debug_content .= "POST data:\n" . print_r($_POST, true) . "\n";

// Validate required fields
$business_name = isset($_POST['firm_name']) ? $_POST['firm_name'] : (isset($_POST['business_name']) ? $_POST['business_name'] : '');
$debug_content .= "Business name field value: '$business_name'\n";
if (empty($business_name) || !validateBusinessName($business_name)) {
    $errors[] = "Business Name is required (2-100 characters, letters, numbers, and basic punctuation only)";
}

if (empty($_POST['business_type'])) {
    $errors[] = "Business Type is required";
} elseif ($_POST['business_type'] === 'Other' && empty($_POST['other_business_type'])) {
    $errors[] = "Please specify the Other Business Type";
}

if (empty($_POST['federal_tax_id']) || !validateFederalTaxId($_POST['federal_tax_id'])) {
    $errors[] = "Federal Tax ID or Primary SSN is required (format: XX-XXXXXXX for EIN or XXX-XX-XXXX for SSN)";
}

if (empty($_POST['phone']) || !validatePhone($_POST['phone'])) {
    $errors[] = "Valid phone number is required (10 digits)";
}

if (empty($_POST['email']) || !validateEmail($_POST['email'])) {
    $errors[] = "Valid email address is required";
}

if (empty($_POST['shipping_address']) || !validateAddress($_POST['shipping_address'])) {
    $errors[] = "Shipping Address is required (must include street number and be 10-200 characters)";
}

if (empty($_POST['billing_address']) || !validateAddress($_POST['billing_address'])) {
    $errors[] = "Billing Address is required (must include street number and be 10-200 characters)";
}

// Validate at least one corporate officer
$officers = ['officer_president', 'officer_vice_president', 'officer_secretary', 'officer_treasurer'];
$debug_content .= "Checking officers: " . print_r($officers, true) . "\n";
$hasOfficer = false;
foreach ($officers as $officer) {
    $value = isset($_POST[$officer]) ? $_POST[$officer] : '';
    $debug_content .= "Officer $officer value: '$value'\n";
    if (!empty($value)) {
        if (!validatePersonName($value)) {
            $errors[] = ucwords(str_replace(['officer_', '_'], ['', ' '], $officer)) . " must be a valid name (2-50 characters, letters only)";
        } else {
            $hasOfficer = true;
        }
    }
}
if (!$hasOfficer) {
    $errors[] = "At least one Corporate Officer is required (President, VP, Secretary, or Treasurer)";
}

// Validate at least one owner/partner
$owners = ['owner1_name', 'owner2_name', 'owner3_name'];
$hasOwner = false;
foreach ($owners as $owner) {
    if (!empty($_POST[$owner])) {
        if (!validatePersonName($_POST[$owner])) {
            $errors[] = ucwords(str_replace('_', ' ', $owner)) . " must be a valid name (2-50 characters, letters only)";
        } else {
            $hasOwner = true;
        }
    }
}
if (!$hasOwner) {
    $errors[] = "At least one Owner/Partner is required";
}

// Validate guarantor names if provided
$guarantors = ['guarantor1_name', 'guarantor2_name'];
foreach ($guarantors as $guarantor) {
    if (!empty($_POST[$guarantor]) && !validatePersonName($_POST[$guarantor])) {
        $errors[] = ucwords(str_replace('_', ' ', $guarantor)) . " must be a valid name (2-50 characters, letters only)";
    }
}

// Validate state if provided
if (!empty($_POST['state']) && !validateState($_POST['state'])) {
    $errors[] = "State must be a valid 2-letter state code (e.g., CA, NY, TX)";
}

// Validate digital authorization
$authorization = isset($_POST['authorization']) ? $_POST['authorization'] : (isset($_POST['digital_authorization']) ? $_POST['digital_authorization'] : '');
$debug_content .= "Authorization field value: '$authorization'\n";
if (empty($authorization)) {
    $errors[] = "Digital Authorization agreement is required";
}

// Add validation results to debug log
if ($debug_enabled) {
    $debug_content .= "\nValidation Errors:\n" . print_r($errors, true) . "\n";
    $debug_content .= "Validation passed: " . (empty($errors) ? 'YES' : 'NO') . "\n";
}

// If there are validation errors, show them
if (!empty($errors)) {
    if ($debug_enabled) {
        $debug_content .= "Redirecting to index.php with validation errors\n";
        $debug_content .= "=== END DEBUG ===\n";
        file_put_contents($debug_file, $debug_content);
    }
    header('Location: index.php?status=validation_error&errors=' . urlencode(json_encode($errors)));
    exit;
}

// Validation passed - continue with form processing
if ($debug_enabled) {
    $debug_content .= "Validation passed - proceeding with form processing\n";
    $debug_content .= "Starting form data collection...\n";
}

// Enhanced form data collection for new fields
$formData = [];
$excludeFields = ['g-recaptcha-response', 'signature1_image', 'signature2_image', 'csrf_token'];

foreach ($_POST as $k => $v) {
    if (!in_array($k, $excludeFields)) {
        // Handle array fields (like business_type options)
        if (is_array($v)) {
            $formData[$k] = implode(', ', array_map('sanitize', $v));
        } else {
            $formData[$k] = sanitize($v);
        }
    }
}

// Handle specific new fields with proper labels
$fieldLabels = [
    'business_type' => 'Business Type',
    'other_business_type' => 'Other Business Type',
    'federal_tax_id' => 'Federal Tax ID / Primary SSN',
    'shipping_address' => 'Shipping Address',
    'billing_address' => 'Billing Address',
    'phone' => 'Phone Number',
    'email' => 'Email Address',
    'president_name' => 'President Name',
    'vp_name' => 'Vice President Name',
    'secretary_name' => 'Secretary Name',
    'treasurer_name' => 'Treasurer Name',
    'owner1_name' => 'Owner/Partner 1 Name',
    'owner2_name' => 'Owner/Partner 2 Name',
    'owner3_name' => 'Owner/Partner 3 Name',
    'guarantor1_name' => 'Guarantor 1 Full Name',
    'guarantor2_name' => 'Guarantor 2 Full Name',
    'digital_authorization' => 'Digital Authorization Agreement'
];

if ($debug_enabled) {
    $debug_content .= "Form data collected successfully\n";
    $debug_content .= "Creating applications directory and saving data...\n";
}

$saveDir = __DIR__ . '/applications';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0775, true);
}
$timestamp = time();
$base = $saveDir . '/creditapp_' . $timestamp;

$txtFile = $base . '.txt';
file_put_contents($txtFile, print_r($_POST, true));

if ($debug_enabled) {
    $debug_content .= "Data saved to: $txtFile\n";
    $debug_content .= "Starting PDF generation...\n";
}

// Load custom PDF class
require_once __DIR__ . '/../crm/includes/mypdf.php';

// Enhanced PDF generation with proper formatting
$pdf = new MYPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 12, 'ANGEL STONES CREDIT APPLICATION', 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Submission Date: ' . date('F j, Y g:i A'), 0, 1, 'C');
$pdf->Ln(8);

// Business Information Section
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'BUSINESS INFORMATION', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$businessFields = ['firm_name', 'business_type', 'other_business_type', 'federal_tax_id', 'subsidiary_of', 'tax_exempt_no', 'tax_exempt_state'];
foreach ($businessFields as $field) {
    if (isset($formData[$field]) && !empty($formData[$field])) {
        $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $formData[$field], 0, 1);
        $pdf->Ln(1);
    }
}

// Contact Information Section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'CONTACT INFORMATION', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$contactFields = ['phone', 'email', 'fax', 'web', 'shipping_address', 'billing_address'];
foreach ($contactFields as $field) {
    if (isset($formData[$field]) && !empty($formData[$field])) {
        $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $formData[$field], 0, 1);
        $pdf->Ln(1);
    }
}

// Corporate Officers Section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'CORPORATE OFFICERS', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$officerFields = ['officer_president', 'officer_vice_president', 'officer_secretary', 'officer_treasurer'];
foreach ($officerFields as $field) {
    if (isset($formData[$field]) && !empty($formData[$field])) {
        $label = ucwords(str_replace(['officer_', '_'], ['', ' '], $field));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $formData[$field], 0, 1);
        $pdf->Ln(1);
    }
}

// Owners/Partners Section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'OWNERS / PARTNERS', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

$ownerFields = ['owner1_name', 'owner1_percent', 'owner1_address', 'owner1_res_phone', 'owner1_cell', 
                'owner2_name', 'owner2_percent', 'owner2_address', 'owner2_res_phone', 'owner2_cell'];
foreach ($ownerFields as $field) {
    if (isset($formData[$field]) && !empty($formData[$field])) {
        $label = ucwords(str_replace('_', ' ', $field));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $formData[$field], 0, 1);
        $pdf->Ln(1);
    }
}

// Guarantors Section
$guarantorFields = ['guarantor1_full_name', 'guarantor1_date', 'guarantor2_full_name', 'guarantor2_date'];
$hasGuarantors = false;
foreach ($guarantorFields as $field) {
    if (isset($formData[$field]) && !empty($formData[$field])) {
        $hasGuarantors = true;
        break;
    }
}

if ($hasGuarantors) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'GUARANTORS', 0, 1, 'L');
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    foreach ($guarantorFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = ucwords(str_replace('_', ' ', $field));
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, $formData[$field], 0, 1);
            $pdf->Ln(1);
        }
    }
}

// Digital Authorization Section
$authField = isset($formData['authorization']) ? $formData['authorization'] : (isset($formData['digital_authorization']) ? $formData['digital_authorization'] : '');
if (!empty($authField)) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(0, 100, 0);
    $pdf->Cell(0, 8, 'DIGITAL AUTHORIZATION CONFIRMED', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, 'The applicant has digitally agreed to the terms and conditions of this credit application on ' . date('F j, Y \a\t g:i A') . '.', 0, 'C');
}
$pdfFile = $base . '.pdf';
$pdf->Output($pdfFile, 'F');

if ($debug_enabled) {
    $debug_content .= "PDF generated successfully: $pdfFile\n";
    $debug_content .= "Starting email processing...\n";
}

// Email with PHPMailer (fallback to mail)
$config_path = __DIR__ . '/../email_config.php';
$debug_content .= "Checking email config at: $config_path\n";
if (file_exists($config_path)) {
    require_once $config_path;
    $debug_content .= "Email config loaded successfully\n";
} else {
    $debug_content .= "Email config file not found\n";
}

$phpmailer = __DIR__ . '/../crm/vendor/phpmailer/PHPMailer.php';
$debug_content .= "Checking PHPMailer at: $phpmailer\n";
$emailSent = false;
if (file_exists($phpmailer)) {
    $debug_content .= "PHPMailer file found\n";
    require_once $phpmailer;
    require_once __DIR__ . '/../crm/vendor/phpmailer/SMTP.php';
    require_once __DIR__ . '/../crm/vendor/phpmailer/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    if (defined('SMTP_HOST')) {
        $debug_content .= "SMTP constants defined, configuring SMTP\n";
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $debug_content .= "SMTP configured: " . SMTP_HOST . ":" . SMTP_PORT . "\n";
    } else {
        $debug_content .= "SMTP constants not defined\n";
    }
    $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@theangelstones.com';
    $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Angel Stones';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress('da@theangelstones.com');
    $mail->Subject = 'New Credit Application';
    $body = '<h2>New Credit Application Submission</h2>';
    $body .= '<p><strong>Submission Date:</strong> ' . date('F j, Y g:i A') . '</p>';

    // Organize fields by sections
    $body .= '<h3>Business Information</h3>';
    $businessFields = ['firm_name', 'business_type', 'other_business_type', 'federal_tax_id', 'subsidiary_of', 'tax_exempt_no', 'tax_exempt_state'];
    foreach ($businessFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($formData[$field]) . '</p>';
        }
    }

    $body .= '<h3>Contact Information</h3>';
    $contactFields = ['phone', 'email', 'shipping_address', 'billing_address'];
    foreach ($contactFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($formData[$field]) . '</p>';
        }
    }

    $body .= '<h3>Corporate Officers</h3>';
    $officerFields = ['officer_president', 'officer_vice_president', 'officer_secretary', 'officer_treasurer'];
    foreach ($officerFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace(['officer_', '_'], ['', ' '], $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($formData[$field]) . '</p>';
        }
    }

    $body .= '<h3>Owners/Partners</h3>';
    $ownerFields = ['owner1_name', 'owner2_name', 'owner3_name'];
    foreach ($ownerFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($formData[$field]) . '</p>';
        }
    }

    $body .= '<h3>Guarantors</h3>';
    $guarantorFields = ['guarantor1_full_name', 'guarantor2_full_name'];
    foreach ($guarantorFields as $field) {
        if (isset($formData[$field]) && !empty($formData[$field])) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($formData[$field]) . '</p>';
        }
    }

    // Add digital authorization status
    $authField = isset($formData['authorization']) ? $formData['authorization'] : (isset($formData['digital_authorization']) ? $formData['digital_authorization'] : '');
    if (!empty($authField)) {
        $body .= '<h3>Authorization</h3>';
        $body .= '<p><strong>Digital Authorization:</strong> Confirmed</p>';
        $body .= '<p><em>The applicant has digitally agreed to the terms and conditions.</em></p>';
    }

    // Add any remaining fields
    $processedFields = array_merge($businessFields, $contactFields, $officerFields, $ownerFields, $guarantorFields, ['digital_authorization']);
    foreach ($formData as $field => $val) {
        if (!in_array($field, $processedFields) && !empty($val)) {
            $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucwords(str_replace('_', ' ', $field));
            $body .= '<p><strong>' . $label . ':</strong> ' . nl2br($val) . '</p>';
        }
    }
    $mail->Body = $body;
    $mail->isHTML(true);
    $mail->addAttachment($pdfFile);
    try {
        $emailSent = $mail->send();
        $debug_content .= "PHPMailer email sent successfully\n";
    } catch (Exception $e) {
        $debug_content .= "PHPMailer error: " . $mail->ErrorInfo . "\n";
        $emailSent = false;
    }
} else {
    $debug_content .= "PHPMailer file not found at: $phpmailer\n";
}

// Write debug log before fallback
if ($debug_enabled) {
    file_put_contents($debug_file, $debug_content);
}

if (!$emailSent) {
    if ($debug_enabled) {
        $debug_content .= "PHPMailer failed, trying fallback mail() function\n";
    }
    // Simple mail() fallback
    $boundary = md5(time());
    $headers = "From: Credit Application <noreply@theangelstones.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";

    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $message .= "A new credit application has been submitted.";

    $attachments = [$pdfFile]; // Only PDF attachment - no signatures
    foreach ($attachments as $file) {
        if (file_exists($file)) {
            $fileContent = chunk_split(base64_encode(file_get_contents($file)));
            $filename = basename($file);
            $message .= "\r\n--$boundary\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= "$fileContent";
        }
    }
    $message .= "\r\n--$boundary--";
    $emailSent = mail('da@theangelstones.com', 'New Credit Application', $message, $headers);
    if ($debug_enabled) {
        $debug_content .= "Fallback mail() result: " . ($emailSent ? 'success' : 'failed') . "\n";
    }
}

// Final debug log update
if ($debug_enabled) {
    $debug_content .= "Final email status: " . ($emailSent ? 'sent' : 'failed') . "\n";
    $debug_content .= "=== END DEBUG ===\n";
    file_put_contents($debug_file, $debug_content);
}

header('Location: index.php?status=' . ($emailSent ? 'success' : 'error'));
exit;
?>
