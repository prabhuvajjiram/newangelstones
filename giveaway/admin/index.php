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
  <div class="giveaway-card" style="max-width:520px;">
    <h1>Admin Login</h1>
    <p class="giveaway-small">Event: <strong><?= h($event['name']) ?></strong></p>
    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="action" value="login">
      <label class="form-label">Username</label>
      <input class="form-control" name="username" required>
      <label class="form-label" style="margin-top:10px;">Password</label>
      <input class="form-control" name="password" type="password" required>
      <button class="btn btn-primary" style="margin-top:14px;">Login</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
