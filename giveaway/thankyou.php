<?php
require_once __DIR__ . '/helpers.php';
$cfg = giveaway_config();
$eventSlug = $_GET['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }
$page_title = 'Thank You - Angel Stones Giveaway';
include __DIR__ . '/partials/header.php';
$endText = event_end_dt($event)->format('M j, Y \a\t g:i A T');
?>
<div class="giveaway-wrap">
  <div class="giveaway-card">
    <h1>Thanks for entering!</h1>
    <p class="giveaway-small">Winner announced at show close: <strong><?= h($endText) ?></strong></p>

    <div style="margin-top:14px;">
      <!-- Removed 'View Products' button as requested -->
      <a class="giveaway-btn" style="margin-left:8px;" href="<?= h(asset_url('giveaway/index.php?event=' . urlencode($eventSlug))) ?>">Back to Giveaway</a>
    </div>

    <hr style="opacity:.15; margin: 18px 0;">
    <p class="giveaway-small">
      Need dealer pricing or container availability? Call us toll-free (Barre / Elberton).
    </p>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
