<?php
require_once __DIR__ . '/auth.php';
require_admin();

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
?>
<div class="giveaway-wrap">
  <div class="giveaway-card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
      <div>
        <h1>Admin Dashboard</h1>
        <p class="giveaway-small">Event: <strong><?= h($event['name']) ?></strong></p>
      </div>
      <div style="text-align:right;">
        <a class="btn btn-outline-light btn-sm" href="export.php?event=<?= h(urlencode($eventSlug)) ?>">Export CSV</a>
        <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col-md-4"><div class="giveaway-badge">Total Entries: <?= h((string)$total) ?></div></div>
      <div class="col-md-4"><div class="giveaway-badge">Dealer Entries: <?= h((string)$totalDealer) ?></div></div>
      <div class="col-md-4"></div>
    </div>

    <hr style="opacity:.15;">

    <h3>Winner</h3>
    <?php if ($winner): ?>
      <div class="alert alert-success">
        <strong><?= h($winner['full_name']) ?></strong> — <?= h($winner['company']) ?><br>
        <?= h($winner['email']) ?> • <?= h($winner['phone']) ?><br>
        <span class="giveaway-small">Selected at: <?= h($winner['selected_at']) ?></span>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">No winner selected yet.</div>
      <form method="post" action="draw.php" onsubmit="return confirm('Draw winner now?');">
        <input type="hidden" name="event" value="<?= h($eventSlug) ?>">
        <button class="btn btn-primary">Draw Winner (Dealer Only)</button>
      </form>
    <?php endif; ?>

    <hr style="opacity:.15;">
    <h3>Recent Entries (latest 200)</h3>

    <div class="table-responsive">
      <table class="table table-dark table-striped table-sm">
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
