<?php
require_once __DIR__ . '/auth.php';
require_admin();

$cfg = giveaway_config();
$eventSlug = $_POST['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }

$check = giveaway_db()->prepare("SELECT id FROM giveaway_winners WHERE event_id=? LIMIT 1");
$check->execute([(int)$event['id']]);
if ($check->fetch()) {
  header('Location: dashboard.php?event=' . urlencode($eventSlug));
  exit;
}

// Pick random dealer entry
$stmt = giveaway_db()->prepare("SELECT id FROM giveaway_entries WHERE event_id=? AND is_dealer=1 AND status='valid'");
$stmt->execute([(int)$event['id']]);
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$ids) {
  exit('No dealer entries found.');
}

$winnerId = $ids[random_int(0, count($ids)-1)];
$user = $_SESSION['giveaway_admin']['user'] ?? 'admin';

$ins = giveaway_db()->prepare("INSERT INTO giveaway_winners (event_id, entry_id, selected_by) VALUES (?,?,?)");
$ins->execute([(int)$event['id'], (int)$winnerId, $user]);

$upd = giveaway_db()->prepare("UPDATE giveaway_entries SET status='winner' WHERE id=?");
$upd->execute([(int)$winnerId]);

header('Location: dashboard.php?event=' . urlencode($eventSlug));
