-- Price Change History Table
CREATE TABLE IF NOT EXISTS price_change_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_type ENUM('finished_product', 'raw_material') NOT NULL,
    item_id INT NOT NULL,
    old_unit_price DECIMAL(10,2),
    new_unit_price DECIMAL(10,2),
    old_final_price DECIMAL(10,2),
    new_final_price DECIMAL(10,2),
    change_type ENUM('individual', 'global_markup') NOT NULL,
    markup_percentage DECIMAL(5,2),
    changed_by VARCHAR(100) NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory Audit Trail Table
CREATE TABLE IF NOT EXISTS inventory_audit_trail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_type ENUM('finished_product', 'raw_material') NOT NULL,
    item_id INT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'movement', 'adjustment') NOT NULL,
    old_quantity INT,
    new_quantity INT,
    old_warehouse_id INT,
    new_warehouse_id INT,
    movement_reference VARCHAR(50),
    reason TEXT,
    changed_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Batch Operations Table
CREATE TABLE IF NOT EXISTS batch_operations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    operation_type ENUM('movement', 'price_update', 'quantity_adjustment') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_by VARCHAR(100) NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Batch Operation Items Table
CREATE TABLE IF NOT EXISTS batch_operation_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    item_type ENUM('finished_product', 'raw_material') NOT NULL,
    item_id INT NOT NULL,
    source_warehouse_id INT,
    destination_warehouse_id INT,
    quantity INT,
    unit_price DECIMAL(10,2),
    final_price DECIMAL(10,2),
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    error_message TEXT,
    FOREIGN KEY (batch_id) REFERENCES batch_operations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for better performance
CREATE INDEX idx_price_history_item ON price_change_history(item_type, item_id);
CREATE INDEX idx_price_history_date ON price_change_history(created_at);
CREATE INDEX idx_audit_trail_item ON inventory_audit_trail(item_type, item_id);
CREATE INDEX idx_audit_trail_date ON inventory_audit_trail(created_at);
CREATE INDEX idx_batch_operations_status ON batch_operations(status);
CREATE INDEX idx_batch_items_batch ON batch_operation_items(batch_id);
CREATE INDEX idx_batch_items_item ON batch_operation_items(item_type, item_id);
