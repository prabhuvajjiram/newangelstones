<?php
require_once __DIR__ . '/../includes/config.php';

try {
    echo "Checking special_monument table:\n";
    echo "==============================\n";
    $stmt = $pdo->query("SHOW CREATE TABLE special_monument");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";

    echo "Checking quote_items table:\n";
    echo "=========================\n";
    $stmt = $pdo->query("SHOW CREATE TABLE quote_items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
