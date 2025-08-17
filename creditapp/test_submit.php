<?php
// Simple test to check if form submission works
session_start();

// Generate proper CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h2>Form Submission Test</h2>";
echo "<p>Testing form submission to see if debug logs are created...</p>";
echo "<p><strong>Session CSRF Token:</strong> " . $_SESSION['csrf_token'] . "</p>";

// Fill out a simple form and submit it
?>
<form action="/creditapp/submit.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="text" name="firm_name" value="Test Company" required>
    <select name="business_type" required>
        <option value="Corporation">Corporation</option>
    </select>
    <input type="text" name="federal_tax_id" value="12-3456789" required>
    <input type="text" name="phone" value="(555) 123-4567" required>
    <input type="email" name="email" value="test@example.com" required>
    <textarea name="shipping_address" required>123 Test Street, Test City, TS 12345</textarea>
    <textarea name="billing_address" required>123 Test Street, Test City, TS 12345</textarea>
    <input type="text" name="officer_president" value="John Doe" required>
    <input type="text" name="owner1_name" value="John Doe" required>
    <input type="checkbox" name="authorization" value="1" checked required>
    <button type="submit">Submit Test Form</button>
</form>

<hr>
<h3>Check Debug Logs</h3>
<p><a href="/creditapp/debug_viewer.php">View Debug Logs</a></p>
