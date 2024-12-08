-- Migration: Add supplier price and markup percentage columns to product tables
-- Date: 2024-12-07

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Update SERTOP products table
ALTER TABLE sertop_products
ADD COLUMN IF NOT EXISTS supplier_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS markup_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00;

-- Initialize with current base_price as supplier_price
UPDATE sertop_products 
SET supplier_price = base_price,
    markup_percentage = 0.00;

-- 2. Update BASE products table
ALTER TABLE base_products
ADD COLUMN IF NOT EXISTS supplier_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS markup_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00;

-- Initialize with current base_price as supplier_price
UPDATE base_products 
SET supplier_price = base_price,
    markup_percentage = 0.00;

-- 3. Update MARKER products table
ALTER TABLE marker_products
ADD COLUMN IF NOT EXISTS supplier_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS markup_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00;

-- Initialize with current base_price as supplier_price
UPDATE marker_products 
SET supplier_price = base_price,
    markup_percentage = 0.00;

-- 4. Update SLANT products table
ALTER TABLE slant_products
ADD COLUMN IF NOT EXISTS supplier_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS markup_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00;

-- Initialize with current base_price as supplier_price
UPDATE slant_products 
SET supplier_price = base_price,
    markup_percentage = 0.00;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Example updates for manual price adjustments:
/*
-- Update SERTOP products
UPDATE sertop_products
SET supplier_price = 35.00,
    markup_percentage = 11.00,
    base_price = supplier_price * (1 + markup_percentage/100)
WHERE model = 'P1' AND size_inches = 6.00;

-- Update BASE products
UPDATE base_products
SET supplier_price = 40.00,
    markup_percentage = 11.00,
    base_price = supplier_price * (1 + markup_percentage/100)
WHERE model = 'P1' AND size_inches = 6;

-- Update MARKER products
UPDATE marker_products
SET supplier_price = 24.00,
    markup_percentage = 11.00,
    base_price = supplier_price * (1 + markup_percentage/100)
WHERE model = 'P1' AND square_feet = 3.0;

-- Update SLANT products
UPDATE slant_products
SET supplier_price = 66.00,
    markup_percentage = 11.00,
    base_price = supplier_price * (1 + markup_percentage/100)
WHERE model = 'P2' AND size_inches = 16;
*/

-- Note: After running this migration, use the products.php page to:
-- 1. Enter the correct supplier prices for each product
-- 2. Set the desired markup percentage
-- 3. The base_price will be automatically calculated and updated
