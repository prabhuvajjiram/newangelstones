-- Drop existing foreign key constraint
ALTER TABLE quote_items
DROP FOREIGN KEY quote_items_ibfk_2;

-- Add new foreign key constraint with CASCADE
ALTER TABLE quote_items
ADD CONSTRAINT quote_items_ibfk_2
FOREIGN KEY (color_id) REFERENCES stone_color_rates(id)
ON DELETE CASCADE
ON UPDATE CASCADE;
