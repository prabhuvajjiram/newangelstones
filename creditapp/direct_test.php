<?php
// Direct test to see if submit.php can be accessed at all
echo "<h2>Direct Submit.php Access Test</h2>";

// Test 1: Direct file access
echo "<h3>Test 1: File Access</h3>";
$submit_path = __DIR__ . '/submit.php';
if (file_exists($submit_path)) {
    echo "<p style='color: green;'>✓ submit.php exists at: $submit_path</p>";
} else {
    echo "<p style='color: red;'>✗ submit.php NOT found</p>";
}

// Test 2: Try to include submit.php directly
echo "<h3>Test 2: Direct Include Test</h3>";
echo "<p>Attempting to access submit.php directly...</p>";

// Create a simple GET request to submit.php
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/creditapp/submit.php';
echo "<p>Testing URL: <a href='$url' target='_blank'>$url</a></p>";

// Test 3: Simple form that should trigger submit.php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h3>Test 3: Direct Form Submission</h3>";
echo "<p>This form will submit directly to submit.php in the same directory:</p>";
?>

<form method="post" action="submit.php" style="border: 2px solid #007bff; padding: 20px; margin: 20px 0; background: #f8f9fa;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <h4>Minimal Required Fields</h4>
    
    <p><label>Firm Name: <input type="text" name="firm_name" value="Test Company" required></label></p>
    <p><label>Email: <input type="email" name="email" value="test@example.com" required></label></p>
    <p><label>Phone: <input type="tel" name="phone" value="555-1234" required></label></p>
    
    <h5>Officer (Required)</h5>
    <p><label>Name: <input type="text" name="officers[0][name]" value="Test Officer" required></label></p>
    <p><label>Title: <input type="text" name="officers[0][title]" value="CEO" required></label></p>
    
    <h5>Owner (Required)</h5>
    <p><label>Name: <input type="text" name="owners[0][name]" value="Test Owner" required></label></p>
    <p><label>Percentage: <input type="number" name="owners[0][percentage]" value="100" min="1" max="100" required></label></p>
    
    <p><label><input type="checkbox" name="digital_authorization" value="1" checked required> Digital Authorization</label></p>
    
    <button type="submit" style="background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px;">
        🚀 Submit Test Form
    </button>
</form>

<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0;">
    <h4>What Should Happen:</h4>
    <ol>
        <li>Click the submit button above</li>
        <li>A debug file should be created in the debug/ folder immediately</li>
        <li>You should be redirected to index.php with success or error status</li>
        <li>Check the debug folder after submission</li>
    </ol>
</div>

<?php
// Test 4: Check current directory and permissions
echo "<h3>Test 4: Environment Check</h3>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

$debug_dir = __DIR__ . '/debug';
echo "<p>Debug directory: $debug_dir</p>";
echo "<p>Debug dir exists: " . (is_dir($debug_dir) ? 'YES' : 'NO') . "</p>";
echo "<p>Debug dir writable: " . (is_writable($debug_dir) ? 'YES' : 'NO') . "</p>";

// List current debug files
$files = glob($debug_dir . '/*');
echo "<p>Current debug files: " . count($files) . "</p>";
if (!empty($files)) {
    echo "<ul>";
    foreach ($files as $file) {
        echo "<li>" . basename($file) . " (" . date('Y-m-d H:i:s', filemtime($file)) . ")</li>";
    }
    echo "</ul>";
}
?>
