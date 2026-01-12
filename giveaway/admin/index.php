<?php
require_once __DIR__ . '/auth.php';

$cfg = giveaway_config();
$eventSlug = $_GET['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='login') {
  $u = trim($_POST['username'] ?? '');
  $p = trim($_POST['password'] ?? '');
  if (admin_login_ok($u, $p)) {
    $_SESSION['giveaway_admin'] = ['user'=>$u, 'at'=>time()];
    header('Location: dashboard.php?event=' . urlencode($eventSlug));
    exit;
  } else {
    $err = 'Invalid login (or password hash not set in config.php).';
  }
}

$page_title = 'Giveaway Admin - Login';
include __DIR__ . '/../partials/header.php';
?>
<div class="giveaway-wrap">
  <div class="giveaway-card" style="max-width:420px; margin:auto;">
    <h1 style="margin-bottom: 8px;">Admin Login</h1>
    <p class="giveaway-small" style="margin-bottom: 18px;">Event: <strong><?= h($event['name']) ?></strong></p>
    <?php if ($err): ?><div class="giveaway-alert err" style="margin-bottom:18px;"><?= h($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="login">
      <label class="giveaway-label" for="admin-username">Username</label>
      <input class="giveaway-input" id="admin-username" name="username" required autofocus>
      <label class="giveaway-label" for="admin-password" style="margin-top:10px;">Password</label>
      <input class="giveaway-input" id="admin-password" name="password" type="password" required>
      <button class="giveaway-btn" style="margin-top:18px; width:100%; font-size:17px;">Login</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
