<?php
// giveaway/rate_limit.php
require_once __DIR__ . '/db.php';

function giveaway_hash_ip(string $secret): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return hash('sha256', $ip . '|' . $secret);
}

function giveaway_rate_limit_or_die(array $cfg): void {
  $secret = $cfg['app_secret'];
  $ipHash = giveaway_hash_ip($secret);

  $dir = sys_get_temp_dir() . '/giveaway_rl';
  if (!is_dir($dir)) @mkdir($dir, 0700, true);

  $file = $dir . '/' . $ipHash . '.json';
  $now = time();
  $data = ['m'=>[], 'h'=>[]];

  if (file_exists($file)) {
    $raw = file_get_contents($file);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $data = $decoded;
  }

  $data['m'] = array_values(array_filter($data['m'], fn($t)=> $t > $now-60));
  $data['h'] = array_values(array_filter($data['h'], fn($t)=> $t > $now-3600));

  if (count($data['m']) >= ($cfg['rate_limit']['max_per_minute'] ?? 8) ||
      count($data['h']) >= ($cfg['rate_limit']['max_per_hour'] ?? 60)) {
    http_response_code(429);
    exit('Too many attempts. Please try again in a few minutes.');
  }

  $data['m'][] = $now;
  $data['h'][] = $now;

  file_put_contents($file, json_encode($data));
}
