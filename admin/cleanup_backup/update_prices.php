<?php
require_once 'includes/config.php';
requireLogin();

// Read and execute the SQL file
$sql = file_get_contents('update_prices.sql');
$queries = explode(';', $sql);

$success = true;
$errors = [];

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if (!$conn->query($query)) {
        $success = false;
        $errors[] = $conn->error;
    }
}

if ($success) {
    echo "All prices have been updated successfully!";
} else {
    echo "Errors occurred while updating prices:<br>";
    foreach ($errors as $error) {
        echo "- " . htmlspecialchars($error) . "<br>";
    }
}

// Redirect back to products page after 3 seconds
header("refresh:3;url=products.php");
?>
