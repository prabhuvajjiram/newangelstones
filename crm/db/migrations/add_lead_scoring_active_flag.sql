-- Add is_active column to lead_scoring_rules
ALTER TABLE lead_scoring_rules
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Update existing rules to be active
UPDATE lead_scoring_rules SET is_active = TRUE WHERE is_active IS NULL;
