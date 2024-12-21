-- First drop the existing foreign key constraint
ALTER TABLE products 
DROP FOREIGN KEY products_ibfk_2;

-- Then modify the color_id column to reference stone_color_rates table
ALTER TABLE products
ADD CONSTRAINT products_ibfk_2 
FOREIGN KEY (color_id) REFERENCES stone_color_rates(id) ON DELETE SET NULL;
