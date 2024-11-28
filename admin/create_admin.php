<?php
require_once 'includes/config.php';

echo "<pre>";
echo "Starting admin user creation process...\n\n";

// Check database connection
echo "Database connection status: " . ($conn->connect_errno ? "Failed" : "Success") . "\n";
if ($conn->connect_errno) {
    echo "Connection error: " . $conn->connect_error . "\n";
    exit();
}

// Show current users
echo "\nCurrent users in database:\n";
$result = $conn->query("SELECT id, username FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Username: {$row['username']}\n";
    }
} else {
    echo "Error querying users: " . $conn->error . "\n";
}

// Delete existing admin user
echo "\nDeleting existing admin user...\n";
$delete_query = "DELETE FROM users WHERE username = 'admin'";
if ($conn->query($delete_query)) {
    echo "✓ Deleted existing admin user\n";
} else {
    echo "✗ Error deleting existing admin: " . $conn->error . "\n";
}

// Create new password hash
$password = 'P@ssword1';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "\nGenerated new password hash: " . $hash . "\n";

// Insert new admin user
echo "\nInserting new admin user...\n";
$insert_query = "INSERT INTO users (username, password) VALUES ('admin', ?)";
$stmt = $conn->prepare($insert_query);
if ($stmt) {
    $stmt->bind_param("s", $hash);
    if ($stmt->execute()) {
        echo "✓ Successfully created admin user\n";
        echo "  Username: admin\n";
        echo "  Password: P@ssword1\n";
    } else {
        echo "✗ Error creating admin: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "✗ Error preparing statement: " . $conn->error . "\n";
}

// Verify the user was created
echo "\nVerifying admin user creation...\n";
$verify_query = "SELECT id, username, password FROM users WHERE username = 'admin'";
$result = $conn->query($verify_query);
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✓ Verification successful\n";
    echo "  Admin user exists with ID: " . $user['id'] . "\n";
    echo "  Stored password hash: " . $user['password'] . "\n";
    
    // Test password verification
    echo "\nTesting password verification...\n";
    if (password_verify('P@ssword1', $user['password'])) {
        echo "✓ Password verification successful\n";
    } else {
        echo "✗ Password verification failed\n";
    }
} else {
    echo "✗ Verification failed - Could not find admin user\n";
}

$conn->close();
echo "</pre>";
?>
