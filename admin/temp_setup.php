<?php
$conn = new mysqli('localhost', 'root', '', 'angelstones_quotes_new');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate tables
$tables = [
    "quote_status_history",
    "follow_ups",
    "quote_items",
    "quotes",
    "customers"
];

// Drop tables if they exist
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}

// Create tables
$sql = "
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    requested_by VARCHAR(100),
    project_name VARCHAR(100),
    total_amount DECIMAL(10, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) NOT NULL,
    commission_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    product_type VARCHAR(50) NOT NULL,
    size VARCHAR(20) NOT NULL,
    model VARCHAR(20) NOT NULL,
    color_id INT NOT NULL,
    length DECIMAL(10, 2) NOT NULL,
    breadth DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    price_increase DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (color_id) REFERENCES stone_color_rates(id)
);

CREATE TABLE follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    quote_id INT,
    status ENUM('pending', 'contacted', 'interested', 'not_interested', 'converted', 'cancelled') NOT NULL DEFAULT 'pending',
    follow_up_date DATE NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE quote_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    status ENUM('created', 'sent', 'viewed', 'accepted', 'rejected', 'expired') NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);";

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Tables created successfully!";
} else {
    echo "Error creating tables: " . $conn->error;
}

$conn->close();
