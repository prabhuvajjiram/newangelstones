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

    if ($full_name === '' || $company === '' || $email === '') {
      $errors[] = 'Please fill name, company, email and phone.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Please enter a valid email.';
    }

    if (!$errors) {
      $secret = $cfg['app_secret'];
      $email_hash = hash('sha256', $email . '|' . $secret);
      $phone_hash = $phone !== '' ? hash('sha256', $phone . '|' . $secret) : hash('sha256', 'blank|' . $email . '|' . $secret);
      $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $ip_hash = hash('sha256', $ip . '|' . $secret);

      $chk = giveaway_db()->prepare("SELECT id FROM giveaway_entries WHERE event_id=? AND (email_hash=? OR (phone_hash=? AND phone<>'')) LIMIT 1");
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
          <li>Winner announced at show close.</li>
          <li>Prize: 1 Ledger (standard options). Freight not included.</li>
        </ul>

        <?php if (!$open): ?>
          <div class="giveaway-alert">This giveaway is closed.</div>
        <?php endif; ?>
      </div>
    </div>

    <hr style="opacity:.15; margin: 18px 0;">

    <?php if ($success): ?>
      <div class="giveaway-alert ok">
        <?= $already ? "You're already entered ✅" : "You're entered ✅" ?>
        <div class="giveaway-small">Thank you for visiting Angel Stones. Keep this page bookmarked.</div>
      </div>
      <a class="giveaway-btn" href="<?= h(asset_url('giveaway/thankyou.php?event=' . urlencode($eventSlug))) ?>">Continue</a>
    <?php else: ?>

      <?php if ($errors): ?>
        <div class="giveaway-alert err">
          <?php foreach ($errors as $e): ?>
            <div><?= h($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="giveaway-form" novalidate>
  <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

  <?php if (!empty($errors)): ?>
    <div class="giveaway-error" role="alert" aria-live="polite">
      <strong>Please fix the following:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="giveaway-grid" style="margin-top: 10px;">
    <div>
      <label class="giveaway-label" for="full_name">Full Name <span aria-hidden="true">*</span></label>
      <input class="giveaway-input" id="full_name" name="full_name" required autocomplete="name" inputmode="text">
    </div>

    <div>
      <label class="giveaway-label" for="company">Company <span aria-hidden="true">*</span></label>
      <input class="giveaway-input" id="company" name="company" required autocomplete="organization" inputmode="text">
    </div>
  </div>

  <div class="giveaway-grid">
    <div>
      <label class="giveaway-label" for="email">Email <span aria-hidden="true">*</span></label>
      <input class="giveaway-input" id="email" name="email" type="email" required autocomplete="email" inputmode="email" pattern="^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$" title="Please enter a valid email address.">
    </div>

    <div>
      <label class="giveaway-label" for="phone">Phone (optional)</label>
      <input class="giveaway-input" id="phone" name="phone" autocomplete="tel" inputmode="tel" placeholder="Optional">
    </div>
  </div>

  <div class="giveaway-grid">
    <div>
      <label class="giveaway-label" for="state">State (optional)</label>
      <input class="giveaway-input" id="state" name="state" autocomplete="address-level1" inputmode="text" placeholder="Optional">
    </div>

    <div>
      <label class="giveaway-label" for="is_dealer">Are you a monument dealer? <span aria-hidden="true">*</span></label>
      <select class="giveaway-select" id="is_dealer" name="is_dealer" required>
        <option value="" selected disabled>Select…</option>
        <option value="yes">Yes</option>
        <option value="no">No</option>
      </select>
      <p class="giveaway-small" id="dealer_note" style="margin-top:6px;">Dealer entries are eligible for the drawing.</p>
    </div>
  </div>

  <fieldset style="border:0; padding:0; margin-top: 14px;">
    <legend class="giveaway-label" style="margin-top:0;">Interested in (optional)</legend>
    <div class="giveaway-checks" style="display:flex; flex-wrap:wrap; gap: 14px;">
      <label class="giveaway-small"><input class="giveaway-checkbox" type="checkbox" name="interests[]" value="Ledgers"> Ledgers</label>
      <label class="giveaway-small"><input class="giveaway-checkbox" type="checkbox" name="interests[]" value="Slants"> Slants</label>
      <label class="giveaway-small"><input class="giveaway-checkbox" type="checkbox" name="interests[]" value="Uprights"> Uprights</label>
      <label class="giveaway-small"><input class="giveaway-checkbox" type="checkbox" name="interests[]" value="Benches"> Benches</label>
    </div>
  </fieldset>

  <div style="margin-top:14px;">
    <label class="giveaway-small">
      <input class="giveaway-checkbox" type="checkbox" name="consent_marketing" value="1">
      Yes, send me dealer catalog & inventory updates
    </label>
  </div>

  <button class="giveaway-btn" style="margin-top:16px;" <?= $open ? '' : 'disabled aria-disabled="true"' ?>>
    Enter Giveaway
  </button>

  <p class="giveaway-small" style="margin-top:10px;">
    If the QR fails: <strong>theangelstones.com/giveaway</strong>
  </p>
</form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
