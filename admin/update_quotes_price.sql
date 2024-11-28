USE angelstones_quotes_new;

-- Add price column if it doesn't exist
ALTER TABLE quotes 
ADD COLUMN IF NOT EXISTS price DECIMAL(10, 2) NULL AFTER total_amount;
