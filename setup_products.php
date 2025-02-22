<?php
require_once('includes/db_config.php');

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Read and execute SQL file
    $sql = file_get_contents('sql/create_products_table.sql');
    
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Prepare next result set
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->error) {
        throw new Exception("Error executing SQL: " . $conn->error);
    }

    echo "Products table created and populated successfully!";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$conn->close();
?>
