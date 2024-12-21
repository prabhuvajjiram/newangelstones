-- Create supplier_invoices table
CREATE TABLE IF NOT EXISTS supplier_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('pdf','excel') NOT NULL,
    status ENUM('pending','processed','error') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    UNIQUE KEY unique_invoice (supplier_id, invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_invoice_items table
CREATE TABLE IF NOT EXISTS supplier_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_code VARCHAR(50) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    unit VARCHAR(10) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    fob_price DECIMAL(15,2),
    cbm DECIMAL(10,3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES supplier_invoices(id) ON DELETE CASCADE,
    INDEX idx_product_code (product_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_invoice_templates table
CREATE TABLE IF NOT EXISTS supplier_invoice_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    supplier_id INT NOT NULL,
    file_type ENUM('pdf','excel') NOT NULL,
    mapping_rules JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    UNIQUE KEY unique_template (supplier_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE suppliers ADD COLUMN notes TEXT AFTER address;