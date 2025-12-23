-- Migration: Add archived columns to existing promotions table
-- Run this on your production database if you already have the promotions table

-- Add archived column if it doesn't exist
ALTER TABLE promotions 
ADD COLUMN IF NOT EXISTS archived TINYINT(1) DEFAULT 0 AFTER created_by;

-- Add archived_at column if it doesn't exist
ALTER TABLE promotions 
ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL AFTER archived;

-- Add index for archived column if it doesn't exist
ALTER TABLE promotions 
ADD INDEX IF NOT EXISTS idx_archived (archived);

-- Set all existing promotions as not archived
UPDATE promotions SET archived = 0 WHERE archived IS NULL;

-- Verify the changes
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'promotions' 
AND COLUMN_NAME IN ('archived', 'archived_at')
ORDER BY ORDINAL_POSITION;
