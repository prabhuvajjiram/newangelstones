-- Stone Tables Setup Script
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: base_products
CREATE TABLE IF NOT EXISTS base_products (
  id int(11) NOT NULL AUTO_INCREMENT,
  product_code varchar(10) NOT NULL,
  model varchar(10) NOT NULL,
  size_inches int(11) NOT NULL,
  length_inches decimal(10,2) NOT NULL,
  breadth_inches decimal(10,2) NOT NULL,
  is_premium tinyint(1) DEFAULT 0,
  description varchar(100) DEFAULT NULL,
  base_price decimal(10,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert base product data
INSERT INTO base_products (product_code, model, size_inches, length_inches, breadth_inches, is_premium, description, base_price) VALUES 
('BASE-8-P1', 'P1', 8, 0.00, 0.00, 0, NULL, 57.72),
('BASE-8-PM', 'P/M', 8, 0.00, 0.00, 0, NULL, 64.38),
('BASE-6-P1', 'P1', 6, 0.00, 0.00, 0, NULL, 44.44),
('BASE-6-PM', 'P/M', 6, 0.00, 0.00, 0, NULL, 48.84),
('BASE-10-P1', 'P1', 10, 0.00, 0.00, 0, NULL, 68.82),
('BASE-10-PM', 'P/M', 10, 0.00, 0.00, 0, NULL, 82.14);

-- Table: slant_products
CREATE TABLE IF NOT EXISTS slant_products (
  id int(11) NOT NULL AUTO_INCREMENT,
  product_code varchar(10) NOT NULL,
  model varchar(10) NOT NULL,
  length_inches decimal(10,2) NOT NULL,
  breadth_inches decimal(10,2) NOT NULL,
  is_premium tinyint(1) DEFAULT 0,
  description varchar(100) DEFAULT NULL,
  base_price decimal(10,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: price_components
CREATE TABLE IF NOT EXISTS price_components (
  id int(11) NOT NULL AUTO_INCREMENT,
  component_name varchar(50) NOT NULL,
  base_rate decimal(10,2) NOT NULL,
  description text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert price components
INSERT INTO price_components (component_name, base_rate, description) VALUES 
('Width Polish', 10.00, 'Base rate for width polishing'),
('Edge Polish', 15.00, 'Base rate for edge polishing'),
('Beveling', 20.00, 'Base rate for beveling'),
('Lamination', 25.00, 'Base rate for lamination');

-- Table: commission_rates
CREATE TABLE IF NOT EXISTS commission_rates (
  id int(11) NOT NULL AUTO_INCREMENT,
  rate_name varchar(100) NOT NULL,
  percentage decimal(5,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: customer_preferences
CREATE TABLE IF NOT EXISTS customer_preferences (
  id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  preference_key varchar(50) NOT NULL,
  preference_value text NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: quote_items
CREATE TABLE IF NOT EXISTS quote_items (
  id int(11) NOT NULL AUTO_INCREMENT,
  quote_id int(11) NOT NULL,
  product_type enum('base','slant') NOT NULL,
  product_id int(11) NOT NULL,
  quantity int(11) NOT NULL DEFAULT 1,
  width_polish tinyint(1) DEFAULT 0,
  edge_polish tinyint(1) DEFAULT 0,
  beveling tinyint(1) DEFAULT 0,
  lamination tinyint(1) DEFAULT 0,
  unit_price decimal(10,2) NOT NULL,
  total_price decimal(10,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: quote_notes
CREATE TABLE IF NOT EXISTS quote_notes (
  id int(11) NOT NULL AUTO_INCREMENT,
  quote_id int(11) NOT NULL,
  note_text text NOT NULL,
  created_by int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: quote_history
CREATE TABLE IF NOT EXISTS quote_history (
  id int(11) NOT NULL AUTO_INCREMENT,
  quote_id int(11) NOT NULL,
  action varchar(50) NOT NULL,
  description text,
  performed_by int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
