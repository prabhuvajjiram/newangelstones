-- Add stock_quantity to products table
ALTER TABLE products
ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0,
ADD COLUMN min_stock_level INT NOT NULL DEFAULT 1,
ADD COLUMN status ENUM('in_stock', 'low_stock', 'out_of_stock') NOT NULL DEFAULT 'in_stock';
