-- Finished Products Categories Table
CREATE TABLE IF NOT EXISTS product_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial product categories
INSERT INTO product_categories (name, description) VALUES
('Sertop', 'sertop products'),
('Slabs', 'Large stone slabs'),
('marker', 'Marker for headstones'),
('base', 'Base for headstones'),
('Slant', 'Slant for headstones');

-- Finished Products Table
CREATE TABLE IF NOT EXISTS finished_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    color_id INT NOT NULL,
    length DECIMAL(10,2) NOT NULL,
    width DECIMAL(10,2) NOT NULL,
    height DECIMAL(10,2) NOT NULL,
    weight DECIMAL(10,2),
    unit_price DECIMAL(10,2) NOT NULL,
    final_price DECIMAL(10,2) DEFAULT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id),
    FOREIGN KEY (color_id) REFERENCES stone_color_rates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finished Products Inventory Table
CREATE TABLE IF NOT EXISTS finished_products_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    location_details VARCHAR(255),
    min_stock_level INT NOT NULL DEFAULT 1,
    status ENUM('in_stock', 'low_stock', 'out_of_stock') NOT NULL DEFAULT 'in_stock',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES finished_products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product Movements Table (Combined for both Finished Products and Raw Materials)
CREATE TABLE IF NOT EXISTS product_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_type ENUM('finished_product', 'raw_material') NOT NULL,
    item_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'transfer', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    source_warehouse_id INT,
    destination_warehouse_id INT,
    reference_type VARCHAR(50), -- e.g., 'production', 'sales_order', 'purchase', 'adjustment'
    reference_id VARCHAR(50),
    notes TEXT,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finished Products Indexes
CREATE INDEX idx_finished_products_sku ON finished_products(sku);
CREATE INDEX idx_finished_products_category ON finished_products(category_id);
CREATE INDEX idx_finished_products_color ON finished_products(color_id);
CREATE INDEX idx_finished_products_status ON finished_products(status);

-- Finished Products Inventory Indexes
CREATE INDEX idx_finished_products_inventory_product ON finished_products_inventory(product_id);
CREATE INDEX idx_finished_products_inventory_warehouse ON finished_products_inventory(warehouse_id);
CREATE INDEX idx_finished_products_inventory_status ON finished_products_inventory(status);

-- Product Movements Indexes
CREATE INDEX idx_product_movements_item ON product_movements(item_type, item_id);
CREATE INDEX idx_product_movements_type ON product_movements(movement_type);
CREATE INDEX idx_product_movements_source ON product_movements(source_warehouse_id);
CREATE INDEX idx_product_movements_dest ON product_movements(destination_warehouse_id);
CREATE INDEX idx_product_movements_created ON product_movements(created_at);
