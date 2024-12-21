-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Drop tables if they exist (in reverse dependency order)
DROP TABLE IF EXISTS price_change_audit;
DROP TABLE IF EXISTS supplier_product_prices;
DROP TABLE IF EXISTS landed_cost_calculations;
DROP TABLE IF EXISTS supplier_products;
DROP TABLE IF EXISTS product_unit_conversions;
DROP TABLE IF EXISTS products;

-- Create products table first (main table that others will reference)
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    color_id INT,
    length DECIMAL(10,2),
    width DECIMAL(10,2),
    height DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sku (sku),
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (color_id) REFERENCES stone_color_rates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create product_unit_conversions table
CREATE TABLE IF NOT EXISTS product_unit_conversions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    unit_type VARCHAR(50) NOT NULL,
    conversion_factor DECIMAL(10,4) NOT NULL,
    base_unit BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_products table
CREATE TABLE IF NOT EXISTS supplier_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    product_id INT NOT NULL,
    supplier_sku VARCHAR(100),
    supplier_product_name VARCHAR(255),
    moq INT DEFAULT 1,
    lead_time_days INT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_supplier_product (supplier_id, product_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create landed_cost_calculations table
CREATE TABLE IF NOT EXISTS landed_cost_calculations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_product_id INT NOT NULL,
    shipping_cost DECIMAL(10,2),
    customs_duty DECIMAL(10,2),
    insurance_cost DECIMAL(10,2),
    other_costs DECIMAL(10,2),
    total_landed_cost DECIMAL(10,2),
    effective_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_product_id) REFERENCES supplier_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create supplier_product_prices table
CREATE TABLE IF NOT EXISTS supplier_product_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_product_id INT NOT NULL,
    currency VARCHAR(3) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    moq_price DECIMAL(10,2),
    effective_date DATE NOT NULL,
    end_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_product_id) REFERENCES supplier_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create price_change_audit table
CREATE TABLE IF NOT EXISTS price_change_audit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_product_id INT NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    currency VARCHAR(3),
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_reason TEXT,
    changed_by VARCHAR(100),
    FOREIGN KEY (supplier_product_id) REFERENCES supplier_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_supplier_products_lookup ON supplier_products(supplier_id, product_id);
CREATE INDEX idx_product_units_lookup ON product_unit_conversions(product_id, unit_type);
CREATE INDEX idx_price_history ON supplier_product_prices(supplier_product_id, effective_date);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
