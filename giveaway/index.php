<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/rate_limit.php';

session_start();
$cfg = giveaway_config();
date_default_timezone_set($cfg['timezone'] ?? 'America/New_York');

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$eventSlug = $_GET['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }

$page_title = 'Convention Giveaway - Angel Stones';

$errors = [];
$success = false;
$already = false;

$open = is_event_open($event);
$endDt = event_end_dt($event);
$endText = $endDt->format('M j, Y \a\t g:i A T');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  giveaway_rate_limit_or_die($cfg);

  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $errors[] = 'Invalid session. Refresh and try again.';
  } elseif (!$open) {
    $errors[] = 'This giveaway is closed.';
  } else {
    $full_name = trim($_POST['full_name'] ?? '');
    $company   = trim($_POST['company'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = preg_replace('/[^0-9+]/', '', trim($_POST['phone'] ?? ''));
    $state     = trim($_POST['state'] ?? '');
    $is_dealer = (($_POST['is_dealer'] ?? '') === 'yes') ? 1 : 0;
    $consent   = isset($_POST['consent_marketing']) ? 1 : 0;

    $interests = $_POST['interests'] ?? [];
    $interestsJson = json_encode(array_values(array_filter($interests)), JSON_UNESCAPED_SLASHES);

    if ($full_name === '' || $company === '' || $email === '' || $phone === '') {
      $errors[] = 'Please fill name, company, email and phone.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Please enter a valid email.';
    }

    if (!$errors) {
      $secret = $cfg['app_secret'];
      $email_hash = hash('sha256', $email . '|' . $secret);
      $phone_hash = hash('sha256', $phone . '|' . $secret);
      $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $ip_hash = hash('sha256', $ip . '|' . $secret);

      $chk = giveaway_db()->prepare("SELECT id FROM giveaway_entries WHERE event_id=? AND (email_hash=? OR phone_hash=?) LIMIT 1");
      $chk->execute([(int)$event['id'], $email_hash, $phone_hash]);
      $existing = $chk->fetch();

      if ($existing) {
        $already = true;
        $success = true;
      } else {
        $ins = giveaway_db()->prepare("
          INSERT INTO giveaway_entries
          (event_id, full_name, company, email, phone, state, is_dealer, interests, consent_marketing, source, email_hash, phone_hash, ip_hash)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'qr', ?, ?, ?)
        ");
        $ins->execute([
          (int)$event['id'],
          $full_name,
          $company,
          $email,
          $phone,
          $state ?: null,
          $is_dealer,
          $interestsJson ?: null,
          $consent,
          $email_hash,
          $phone_hash,
          $ip_hash,
        ]);
        $success = true;
      }
    }
  }
}

include __DIR__ . '/partials/header.php';
?>

<div class="giveaway-wrap">
  <div class="giveaway-card">
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div class="giveaway-badge">Dealer Giveaway</div>
        <h1 style="margin-top:10px;">Win a Premium Ledger</h1>
        <p class="giveaway-small">Event: <strong><?= h($event['name']) ?></strong></p>
        <p class="giveaway-small">Entry closes: <strong><?= h($endText) ?></strong></p>
      </div>
      <div class="giveaway-small" style="text-align:right;">
        <div><strong>ANGEL STONES</strong></div>
        <div>Elberton, GA | Barre, VT</div>
      </div>
    </div>

    <div class="giveaway-hero" style="margin-top:18px;">
      <div class="giveaway-img">
        <img src="<?= h(asset_url('giveaway/assets/ledger-placeholder.jpg')) ?>" alt="Ledger (placeholder)">
      </div>
      <div style="flex:1; min-width:260px;">
        <h3>How it works</h3>
        <ul class="giveaway-small">
          <li>Scan and enter in under 20 seconds.</li>
          <li>Dealer entries only.</li>
          <li>Winner announced at show close.</li>
          <li>Prize: 1 Ledger (standard options). Freight not included.</li>
        </ul>

        <?php if (!$open): ?>
          <div class="alert alert-warning">This giveaway is closed.</div>
        <?php endif; ?>
      </div>
    </div>

    <hr style="opacity:.15; margin: 18px 0;">

    <?php if ($success): ?>
      <div class="alert alert-success">
        <?= $already ? "You're already entered ✅" : "You're entered ✅" ?>
        <div class="giveaway-small">Thank you for visiting Angel Stones. Keep this page bookmarked.</div>
      </div>
      <a class="btn btn-primary" href="<?= h(asset_url('giveaway/thankyou.php?event=' . urlencode($eventSlug))) ?>">Continue</a>
    <?php else: ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e): ?>
            <div><?= h($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="giveaway-form" style="max-width:680px;">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <label class="form-label">Full Name *</label>
        <input class="form-control" name="full_name" required>

        <label class="form-label">Company *</label>
        <input class="form-control" name="company" required>

        <label class="form-label">Email *</label>
        <input class="form-control" name="email" type="email" required>

        <label class="form-label">Phone *</label>
        <input class="form-control" name="phone" required>

        <div class="row">
          <div class="col-md-4">
            <label class="form-label">State</label>
            <input class="form-control" name="state" placeholder="Optional">
          </div>
          <div class="col-md-8">
            <label class="form-label">Are you a monument dealer? *</label>
            <select class="form-control" name="is_dealer" required>
              <option value="">Select…</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>
        </div>

        <label class="form-label" style="margin-top:12px;">Interested in (optional)</label>
        <div class="giveaway-small">
          <label style="margin-right:12px;"><input type="checkbox" name="interests[]" value="ledgers"> Ledgers</label>
          <label style="margin-right:12px;"><input type="checkbox" name="interests[]" value="slants"> Slants</label>
          <label style="margin-right:12px;"><input type="checkbox" name="interests[]" value="uprights"> Uprights</label>
          <label style="margin-right:12px;"><input type="checkbox" name="interests[]" value="benches"> Benches</label>
        </div>

        <div style="margin-top:12px;">
          <label class="giveaway-small">
            <input type="checkbox" name="consent_marketing" value="1">
            Yes, send me dealer catalog & inventory updates
          </label>
        </div>

        <button class="btn btn-primary" style="margin-top:14px;" <?= $open ? '' : 'disabled' ?>>Enter Giveaway</button>

        <p class="giveaway-small" style="margin-top:10px;">
          If the QR fails: <strong>theangelstones.com/giveaway</strong>
        </p>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
