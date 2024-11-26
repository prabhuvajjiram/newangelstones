USE angelstones_quotes_new;

-- Add new columns to quotes table
ALTER TABLE quotes
ADD COLUMN requested_by VARCHAR(100) AFTER customer_phone,
ADD COLUMN project_name VARCHAR(100) AFTER requested_by;
