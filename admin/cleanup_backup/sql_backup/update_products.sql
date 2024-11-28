-- First clear existing data
TRUNCATE TABLE sertop_products;
TRUNCATE TABLE marker_products;
TRUNCATE TABLE base_products;
TRUNCATE TABLE slant_products;

-- Insert SERTOP products (rows 61-72)
INSERT INTO sertop_products (product_code, model, size_inches, length_inches, breadth_inches, base_price) VALUES
('SER-8-P1', 'P1', 8, 0, 0, 57.72),
('SER-8-P2', 'P2', 8, 0, 0, 58.55),
('SER-8-P3', 'P3', 8, 0, 0, 59.38),
('SER-8-P4', 'P4', 8, 0, 0, 60.22),
('SER-8-P5', 'P5', 8, 0, 0, 61.05),
('SER-6-P1', 'P1', 6, 0, 0, 43.29),
('SER-6-P2', 'P2', 6, 0, 0, 43.91),
('SER-6-P3', 'P3', 6, 0, 0, 44.54),
('SER-6-P4', 'P4', 6, 0, 0, 45.17),
('SER-6-P5', 'P5', 6, 0, 0, 45.79);

-- Insert MARKER products (rows 74-76, only P1 model)
INSERT INTO marker_products (product_code, model, square_feet, length_inches, breadth_inches, base_price) VALUES
('MAR-3-P1', 'P1', 3, 0, 0, 28.86),
('MAR-4-P1', 'P1', 4, 0, 0, 38.48);

-- Insert BASE products (rows 78-84)
INSERT INTO base_products (product_code, model, size_inches, length_inches, breadth_inches, base_price) VALUES
('BASE-8-P1', 'P1', 8, 0, 0, 72.15),
('BASE-8-PM', 'P/M', 8, 0, 0, 73.19),
('BASE-6-P1', 'P1', 6, 0, 0, 54.11),
('BASE-6-PM', 'P/M', 6, 0, 0, 54.89);

-- Insert SLANT products (rows 87-91)
INSERT INTO slant_products (product_code, model, base_price) VALUES
('SLANT-P2', 'P2', 86.58),
('SLANT-P3', 'P3', 87.83),
('SLANT-P4', 'P4', 89.08),
('SLANT-P5', 'P5', 90.33);

-- Add pdf_file column to quotes table if it doesn't exist
ALTER TABLE quotes ADD COLUMN IF NOT EXISTS pdf_file VARCHAR(255) NULL AFTER total_amount;

-- Add new columns to quote_items if they don't exist
ALTER TABLE quote_items 
ADD COLUMN IF NOT EXISTS product_id INT NULL AFTER quote_id,
ADD COLUMN IF NOT EXISTS color_id INT NULL AFTER size,
ADD COLUMN IF NOT EXISTS length DECIMAL(10,2) NULL AFTER color_id,
ADD COLUMN IF NOT EXISTS breadth DECIMAL(10,2) NULL AFTER length,
ADD COLUMN IF NOT EXISTS sqft DECIMAL(10,2) NULL AFTER breadth,
ADD COLUMN IF NOT EXISTS cubic_feet DECIMAL(10,2) NULL AFTER sqft;
