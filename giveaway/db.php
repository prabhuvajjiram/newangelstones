<?php
// giveaway/db.php
function giveaway_config(): array {
  static $cfg = null;
  if ($cfg === null) $cfg = require __DIR__ . '/config.php';
  return $cfg;
}

function giveaway_db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = giveaway_config();
  $db = $cfg['db'];
  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function giveaway_now(): DateTimeImmutable {
  $tz = new DateTimeZone(giveaway_config()['timezone'] ?? 'America/New_York');
  return new DateTimeImmutable('now', $tz);
}
