-- Update event for MBNA 2026 giveaway
-- Event runs: February 26-28, 2026
-- Winner announced: Feb 28 at 3PM
-- Pickup deadline: Feb 28 at 6PM

-- Update existing Mid-Atlantic event to MBNA 2026
UPDATE giveaway_events 
SET 
  slug = 'mbna-2026',
  name = 'MBNA 2026',
  end_at = '2026-02-28 18:00:00'
WHERE id = 1;

-- Verify the update
SELECT id, slug, name, end_at, is_active, created_at FROM giveaway_events;
