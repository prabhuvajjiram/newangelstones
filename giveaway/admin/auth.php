<?php
// giveaway/admin/auth.php
require_once __DIR__ . '/../helpers.php';
session_start();

function require_admin(): void {
  if (empty($_SESSION['giveaway_admin'])) {
    header('Location: index.php');
    exit;
  }
}

function admin_login_ok(string $user, string $pass): bool {
  $cfg = giveaway_config();
  if ($user !== ($cfg['admin']['username'] ?? 'admin')) return false;
  $hash = $cfg['admin']['password_hash'] ?? '';
  if (!$hash || str_starts_with($hash, 'CHANGE_')) return false;
  return password_verify($pass, $hash);
}
