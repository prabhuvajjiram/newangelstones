<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once('../includes/db_config.php');

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT 
                p.id,
                p.name,
                p.description,
                p.type,
                p.color,
                p.material,
                p.price,
                CONCAT('images/products/', p.image) as image
            FROM products p
            WHERE p.active = 1
            ORDER BY p.name ASC";

    $result = $conn->query($sql);
    
    $products = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'type' => $row['type'],
                'color' => $row['color'],
                'material' => $row['material'],
                'price' => floatval($row['price']),
                'image' => $row['image']
            );
        }
    }
    
    echo json_encode(array(
        'status' => 'success',
        'data' => $products
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}

$conn->close();
?>
