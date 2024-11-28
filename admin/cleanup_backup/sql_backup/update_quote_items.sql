USE angelstones_quotes_new;

-- Add price column to quote_items table if it doesn't exist
ALTER TABLE quote_items 
ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) NULL AFTER quantity;
