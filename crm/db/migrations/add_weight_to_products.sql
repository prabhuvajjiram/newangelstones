-- Add weight column if it doesn't exist
SET @dbname = 'angelstones_quotes_new';
SET @tablename = 'products';
SET @columnname = 'weight';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL AFTER height"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add stock and location columns if they don't exist
SET @columnname = 'current_stock';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN current_stock INT DEFAULT 0 AFTER weight, ADD COLUMN location_id INT DEFAULT NULL AFTER current_stock, ADD COLUMN location_details VARCHAR(255) DEFAULT NULL AFTER location_id"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;