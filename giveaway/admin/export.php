<?php
require_once __DIR__ . '/auth.php';
require_admin();

$cfg = giveaway_config();
$eventSlug = $_GET['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="giveaway-' . $eventSlug . '-entries.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['created_at','full_name','company','email','phone','state','is_dealer','consent_marketing','source']);

$stmt = giveaway_db()->prepare("SELECT created_at, full_name, company, email, phone, state, is_dealer, consent_marketing, source
  FROM giveaway_entries WHERE event_id=? ORDER BY created_at ASC");
$stmt->execute([(int)$event['id']]);
while ($row = $stmt->fetch()) {
  fputcsv($out, $row);
}
fclose($out);
