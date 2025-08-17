<?php
// Test if we can write debug files at all
echo "<h2>Debug Write Test</h2>";

$debug_dir = __DIR__ . '/debug';
echo "<p>Debug directory: $debug_dir</p>";

// Check if directory exists
if (!is_dir($debug_dir)) {
    echo "<p style='color: orange;'>Creating debug directory...</p>";
    mkdir($debug_dir, 0755, true);
}

// Check if directory is writable
if (is_writable($debug_dir)) {
    echo "<p style='color: green;'>✓ Debug directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Debug directory is NOT writable</p>";
    echo "<p>Directory permissions: " . substr(sprintf('%o', fileperms($debug_dir)), -4) . "</p>";
}

// Try to write a test file
$test_file = $debug_dir . '/test_write_' . date('Y-m-d_H-i-s') . '.log';
$test_content = "Test write at " . date('Y-m-d H:i:s') . "\n";
$test_content .= "This is a test debug file\n";

echo "<p>Attempting to write to: " . basename($test_file) . "</p>";

if (file_put_contents($test_file, $test_content)) {
    echo "<p style='color: green;'>✓ Successfully wrote test file</p>";
    echo "<p>File size: " . filesize($test_file) . " bytes</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to write test file</p>";
    $error = error_get_last();
    if ($error) {
        echo "<p>Error: " . $error['message'] . "</p>";
    }
}

// List all files in debug directory
echo "<h3>Files in debug directory:</h3>";
$files = glob($debug_dir . '/*');
if (empty($files)) {
    echo "<p>No files found</p>";
} else {
    echo "<ul>";
    foreach ($files as $file) {
        echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
    }
    echo "</ul>";
}

// Test direct submit.php access
echo "<h3>Test Direct Submit Access</h3>";
echo "<p>Click the button below to test if submit.php creates debug files:</p>";

session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form action="submit.php" method="post" style="border: 1px solid #ccc; padding: 15px; margin: 10px 0;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="g-recaptcha-response" value="test">
    
    <h4>Minimal Test Form</h4>
    <input type="text" name="firm_name" value="Test Company" required>
    <input type="email" name="email" value="test@example.com" required>
    <input type="text" name="phone" value="555-1234" required>
    
    <div>
        <label>Officers:</label>
        <input type="text" name="officers[0][name]" value="Test Officer" required>
        <input type="text" name="officers[0][title]" value="CEO" required>
    </div>
    
    <div>
        <label>Owners:</label>
        <input type="text" name="owners[0][name]" value="Test Owner" required>
        <input type="number" name="owners[0][percentage]" value="100" required>
    </div>
    
    <label><input type="checkbox" name="digital_authorization" value="1" checked> I authorize</label>
    <br><br>
    <button type="submit" style="background: #007bff; color: white; padding: 8px 16px; border: none;">
        Submit Test Form
    </button>
</form>

<p><strong>After clicking submit:</strong></p>
<ol>
    <li>Refresh this page to see if new debug files appear</li>
    <li>Check if you get redirected to index.php</li>
    <li>Look for any error messages</li>
</ol>
