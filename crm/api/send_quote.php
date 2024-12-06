<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to output debug info
function debug($message) {
    // Output to browser
    echo $message . "<br>";
    flush();
    ob_flush();
    
    // Also write to a log file
    $log_file = __DIR__ . '/../logs/email_debug.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    try {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    } catch (Exception $e) {
        error_log("Failed to write to debug log: " . $e->getMessage());
    }
}

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/gmail_config.php';
require_once '../includes/gmail_functions.php';

try {
    debug("Starting quote email process...");
    debug("Checking Gmail configuration...");
    
    // Verify Gmail configuration
    if (!defined('GMAIL_CLIENT_ID') || !defined('GMAIL_CLIENT_SECRET')) {
        throw new Exception('Gmail API configuration is missing');
    }
    
    debug("Gmail configuration verified");
    
    if (!isset($_POST['quote_id'])) {
        throw new Exception('Quote ID is required');
    }
    
    $quote_id = intval($_POST['quote_id']);
    debug("Quote ID: " . $quote_id);
    
    // Create PDF directory if it doesn't exist
    $pdf_dir = __DIR__ . '/../pdf_quotes';
    if (!file_exists($pdf_dir)) {
        mkdir($pdf_dir, 0777, true);
    }
    
    // Generate unique filename for this quote
    $pdf_path = $pdf_dir . '/quote_' . $quote_id . '_' . date('Y-m-d_His') . '.pdf';
    
    // Include necessary files but suppress output
    ob_start();
    require_once '../includes/config.php';
    require_once '../tcpdf/tcpdf.php';
    ob_end_clean();
    
    // Call generate_pdf.php with a specific path to save the PDF
    $_GET['id'] = $quote_id;
    $_GET['save_path'] = $pdf_path;  // We'll modify generate_pdf.php to use this
    require_once '../generate_pdf.php';
    
    if (!file_exists($pdf_path)) {
        throw new Exception('Failed to generate PDF file');
    }
    
    debug("PDF generated successfully at: " . $pdf_path);
    
    // Get customer email from database
    $stmt = $pdo->prepare("SELECT c.email, c.name FROM quotes q JOIN customers c ON q.customer_id = c.id WHERE q.id = ?");
    $stmt->execute([$quote_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found for quote');
    }
    
    $customer_email = $customer['email'];
    $customer_name = $customer['name'];
    debug("Found quote for customer: " . $customer_name);
    debug("Customer email: " . $customer_email);
    
    // Load email template
    $template_path = __DIR__ . '/../email_templates/quote.html';
    debug("Loading template from: " . $template_path);
    
    if (!file_exists($template_path)) {
        throw new Exception('Email template not found');
    }
    
    $template = file_get_contents($template_path);
    if ($template === false) {
        throw new Exception('Failed to read email template');
    }
    debug("Template loaded successfully");
    
    // Replace placeholders in template
    $template = str_replace('{CUSTOMER_NAME}', $customer_name, $template);
    $template = str_replace('{QUOTE_ID}', $quote_id, $template);
    debug("Template placeholders replaced");
    
    debug("Initializing GmailMailer...");
    try {
        $mailer = new GmailMailer($pdo);
        debug("Mailer instance created successfully");
    } catch (Exception $e) {
        debug("Failed to initialize mailer: " . $e->getMessage());
        throw new Exception("Email system not properly configured: " . $e->getMessage());
    }
    
    // Send email with PDF attachment
    try {
        debug("Attempting to send email...");
        $result = $mailer->sendEmail(
            $customer_email,
            "Your Quote #" . $quote_id . " from Angel Stones",
            $template,
            $pdf_path
        );
        debug("Email sent successfully");
        echo json_encode(['success' => true, 'message' => 'Quote sent successfully']);
    } catch (Exception $e) {
        debug("Failed to send email: " . $e->getMessage());
        throw new Exception("Failed to send email: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    debug("ERROR: " . $e->getMessage());
    debug("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}
