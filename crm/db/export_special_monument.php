<?php
$db_host = '127.0.0.1';
$db_name = 'angelstones_quotes_new';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get table structure
    $stmt = $pdo->query("SHOW CREATE TABLE special_monument");
    $tableStructure = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "-- Special Monument Table Structure\n";
    echo $tableStructure['Create Table'] . ";\n\n";

    // Get table data
    $stmt = $pdo->query("SELECT * FROM special_monument");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($data) > 0) {
        echo "-- Special Monument Data\n";
        echo "INSERT INTO `special_monument` (`id`, `sp_name`, `sp_value`, `created_at`, `updated_at`) VALUES\n";
        
        $values = [];
        foreach ($data as $row) {
            $values[] = sprintf("(%d, %s, %s, %s, %s)",
                $row['id'],
                $pdo->quote($row['sp_name']),
                $pdo->quote($row['sp_value']),
                $pdo->quote($row['created_at']),
                $pdo->quote($row['updated_at'])
            );
        }
        echo implode(",\n", $values) . ";\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
