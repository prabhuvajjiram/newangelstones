USE angelstones_quotes_new;

-- Add price column to quotes table
ALTER TABLE quotes
ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) NOT NULL AFTER total_amount;
