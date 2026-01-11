<?php
// giveaway/helpers.php
require_once __DIR__ . '/db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function asset_url(string $path): string {
  $base = giveaway_config()['site']['asset_base'] ?? '/';
  if (!str_ends_with($base, '/')) $base .= '/';
  $path = ltrim($path, '/');
  return $base . $path;
}

function load_event(string $slug): ?array {
  $stmt = giveaway_db()->prepare("SELECT * FROM giveaway_events WHERE slug=? LIMIT 1");
  $stmt->execute([$slug]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function event_end_dt(array $event): DateTimeImmutable {
  $tz = new DateTimeZone(giveaway_config()['timezone'] ?? 'America/New_York');
  return new DateTimeImmutable($event['end_at'], $tz);
}

function is_event_open(array $event): bool {
  if ((int)$event['is_active'] !== 1) return false;
  $now = giveaway_now();
  return $now <= event_end_dt($event);
}
