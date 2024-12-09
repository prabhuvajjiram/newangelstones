-- Warehouses Table
CREATE TABLE IF NOT EXISTS warehouses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    notes TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add warehouse_id column to raw_materials table
ALTER TABLE raw_materials
    ADD COLUMN warehouse_id INT,
    ADD FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    DROP COLUMN location;
