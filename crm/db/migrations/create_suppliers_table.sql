-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Drop table if exists
DROP TABLE IF EXISTS suppliers;

-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    notes TEXT,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_supplier_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone, status) VALUES
('Amman Granites', 'Ramasamy', 'md@ammangranites.com', '+91-9003289999', 'active'),
('AG Granites', 'Ashwin', 'Ashwin@ammangranites.com', '+91-9940674044', 'active');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
