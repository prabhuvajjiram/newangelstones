<?php
require_once __DIR__ . '/auth.php';
require_admin(); // AUTH BYPASSED FOR DEBUGGING

$cfg = giveaway_config();
$eventSlug = $_GET['event'] ?? $cfg['default_event_slug'] ?? 'midatlantic-2026';
$event = load_event($eventSlug);
if (!$event) { http_response_code(404); exit('Event not found.'); }

$page_title = 'Giveaway Admin - Dashboard';

$cnt = giveaway_db()->prepare("SELECT COUNT(*) c FROM giveaway_entries WHERE event_id=?");
$cnt->execute([(int)$event['id']]);
$total = (int)$cnt->fetch()['c'];

$cntDealer = giveaway_db()->prepare("SELECT COUNT(*) c FROM giveaway_entries WHERE event_id=? AND is_dealer=1");
$cntDealer->execute([(int)$event['id']]);
$totalDealer = (int)$cntDealer->fetch()['c'];

$winnerStmt = giveaway_db()->prepare("SELECT w.selected_at, e.full_name, e.company, e.email, e.phone
  FROM giveaway_winners w JOIN giveaway_entries e ON e.id=w.entry_id WHERE w.event_id=? LIMIT 1");
$winnerStmt->execute([(int)$event['id']]);
$winner = $winnerStmt->fetch();

$entries = giveaway_db()->prepare("SELECT id, full_name, company, email, phone, is_dealer, created_at
  FROM giveaway_entries WHERE event_id=? ORDER BY created_at DESC LIMIT 200");
$entries->execute([(int)$event['id']]);
$rows = $entries->fetchAll();

include __DIR__ . '/../partials/header.php';

// DEBUG: Show session state for troubleshooting
if (isset($_GET['debug_session'])) {
  echo '<pre style="background:#222;color:#fff;padding:12px;border-radius:8px;">';
  echo "<b>SESSION DEBUG</b>\n";
  var_dump($_SESSION);
  echo '</pre>';
}
?>
<div class="giveaway-wrap">
  <div class="giveaway-card" style="max-width: 900px; margin: auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 18px; margin-bottom: 18px;">
      <div>
        <h1 style="margin-bottom: 8px;">Admin Dashboard</h1>
        <p class="giveaway-small" style="margin-bottom: 18px;">Event: <strong><?= h($event['name']) ?></strong></p>
        <div style="display: flex; gap: 12px; margin-bottom: 18px;">
          <div class="giveaway-badge">Total Entries: <?= h((string)$total) ?></div>
          <div class="giveaway-badge">Dealer Entries: <?= h((string)$totalDealer) ?></div>
        </div>
      </div>
      <div style="text-align: right; min-width: 180px;">
        <a class="giveaway-btn" style="margin-bottom: 8px; width: 110px;" href="export.php?event=<?= h(urlencode($eventSlug)) ?>">Export CSV</a>
        <a class="giveaway-btn" style="width: 90px;" href="logout.php">Logout</a>
      </div>
    </div>

    <hr style="opacity:.15; margin: 18px 0;">

    <h3 style="margin-bottom: 10px;">Winner</h3>
    <?php if ($winner): ?>
      <div class="giveaway-alert ok" style="margin-bottom: 18px;">
        <strong><?= h($winner['full_name']) ?></strong> — <?= h($winner['company']) ?><br>
        <?= h($winner['email']) ?> • <?= h($winner['phone']) ?><br>
        <span class="giveaway-small">Selected at: <?= h($winner['selected_at']) ?></span>
      </div>
    <?php else: ?>
      <div class="giveaway-alert" style="background:#333; color:#fff; margin-bottom: 12px;">No winner selected yet.</div>
      <form method="post" action="draw.php" onsubmit="return confirm('Draw winner now?');" style="margin-bottom: 18px;">
        <input type="hidden" name="event" value="<?= h($eventSlug) ?>">
        <button class="giveaway-btn">Draw Winner (Dealer Only)</button>
      </form>
    <?php endif; ?>

    <hr style="opacity:.15; margin: 18px 0;">
    <h3 style="margin-bottom: 10px;">Recent Entries (latest 200)</h3>

    <div style="overflow-x:auto;">
      <table class="table table-dark table-striped table-sm" style="min-width: 600px; width: 100%;">
        <thead>
          <tr>
            <th>Date</th><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Dealer?</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= h($r['created_at']) ?></td>
              <td><?= h($r['full_name']) ?></td>
              <td><?= h($r['company']) ?></td>
              <td><?= h($r['email']) ?></td>
              <td><?= h($r['phone']) ?></td>
              <td><?= $r['is_dealer'] ? 'Yes' : 'No' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
