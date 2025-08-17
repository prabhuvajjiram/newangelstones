<?php
// Test direct access to submit.php to verify it's working
session_start();

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<h2>Testing Direct Submit.php Access</h2>";
echo "<p>This will test if submit.php is accessible and working.</p>";

// Test 1: Check if submit.php file exists
$submit_file = __DIR__ . '/submit.php';
echo "<h3>Test 1: File Existence</h3>";
if (file_exists($submit_file)) {
    echo "<p style='color: green;'>✓ submit.php exists at: $submit_file</p>";
} else {
    echo "<p style='color: red;'>✗ submit.php NOT FOUND at: $submit_file</p>";
}

// Test 2: Check if debug directory exists and is writable
$debug_dir = __DIR__ . '/debug';
echo "<h3>Test 2: Debug Directory</h3>";
if (!is_dir($debug_dir)) {
    mkdir($debug_dir, 0755, true);
    echo "<p style='color: blue;'>Created debug directory</p>";
}
if (is_writable($debug_dir)) {
    echo "<p style='color: green;'>✓ Debug directory is writable: $debug_dir</p>";
} else {
    echo "<p style='color: red;'>✗ Debug directory is NOT writable: $debug_dir</p>";
}

// Test 3: Create a simple test form that submits to submit.php
echo "<h3>Test 3: Simple Form Submission</h3>";
?>

<form action="submit.php" method="post" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="g-recaptcha-response" value="test">
    
    <h4>Minimal Test Form</h4>
    <p><label>Business Name: <input type="text" name="firm_name" value="Test Business" required></label></p>
    <p><label>Contact Name: <input type="text" name="contact_name" value="Test Contact" required></label></p>
    <p><label>Email: <input type="email" name="email" value="test@example.com" required></label></p>
    <p><label>Phone: <input type="tel" name="phone" value="555-1234" required></label></p>
    
    <h5>Officer Information</h5>
    <p><label>Officer Name: <input type="text" name="officers[0][name]" value="Test Officer" required></label></p>
    <p><label>Officer Title: <input type="text" name="officers[0][title]" value="CEO" required></label></p>
    
    <h5>Owner Information</h5>
    <p><label>Owner Name: <input type="text" name="owners[0][name]" value="Test Owner" required></label></p>
    <p><label>Owner Percentage: <input type="number" name="owners[0][percentage]" value="100" required></label></p>
    
    <p><label><input type="checkbox" name="digital_authorization" value="1" checked> I authorize this application</label></p>
    
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
        Submit Test Form
    </button>
</form>

<h3>Instructions:</h3>
<ol>
    <li>Click the "Submit Test Form" button above</li>
    <li>Check if you get redirected to index.php with success or error status</li>
    <li>Check the debug folder for new log files</li>
    <li>If no logs appear, there's a server configuration issue</li>
</ol>

<?php
// Test 4: Check server configuration
echo "<h3>Test 4: Server Information</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";

// Test 5: Check if we can write a test debug file
echo "<h3>Test 5: Debug File Write Test</h3>";
$test_debug_file = $debug_dir . '/test_write_' . date('Y-m-d_H-i-s') . '.txt';
$test_content = "Test debug write at " . date('Y-m-d H:i:s') . "\n";
$test_content .= "PHP can write to debug directory\n";

if (file_put_contents($test_debug_file, $test_content)) {
    echo "<p style='color: green;'>✓ Successfully wrote test debug file: " . basename($test_debug_file) . "</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to write test debug file</p>";
}
?>
