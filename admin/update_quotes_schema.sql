USE angelstones_quotes_new;

-- Update quotes table with all required columns
ALTER TABLE quotes
ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) AFTER total_amount,
ADD COLUMN IF NOT EXISTS project_name VARCHAR(255) AFTER customer_phone,
ADD COLUMN IF NOT EXISTS length DECIMAL(10, 2) AFTER project_name,
ADD COLUMN IF NOT EXISTS breadth DECIMAL(10, 2) AFTER length,
ADD COLUMN IF NOT EXISTS width_polish DECIMAL(10, 2) AFTER breadth,
ADD COLUMN IF NOT EXISTS color VARCHAR(100) AFTER width_polish,
ADD COLUMN IF NOT EXISTS quantity INT AFTER color,
ADD COLUMN IF NOT EXISTS sertop_type ENUM('base', 'slant') AFTER quantity,
ADD COLUMN IF NOT EXISTS sertop_price DECIMAL(10, 2) AFTER sertop_type,
ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5, 2) AFTER sertop_price,
ADD COLUMN IF NOT EXISTS total_area DECIMAL(10, 2) AFTER commission_rate,
ADD COLUMN IF NOT EXISTS price_per_sqft DECIMAL(10, 2) AFTER total_area,
ADD COLUMN IF NOT EXISTS width_polish_cost DECIMAL(10, 2) AFTER price_per_sqft,
ADD COLUMN IF NOT EXISTS sertop_total DECIMAL(10, 2) AFTER width_polish_cost,
ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10, 2) AFTER sertop_total,
ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10, 2) AFTER subtotal;
