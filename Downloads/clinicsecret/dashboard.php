<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /clinicsecret/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM referral_users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: /clinicsecret/login.php');
    exit;
}

require_once __DIR__ . '/header.php';
?>

<h3 class="mb-3">Welcome, <?php echo htmlspecialchars($user['first_name']); ?></h3>

<div class="mb-3">
  <label class="form-label fw-bold">Your Referral Link</label>
  <div class="input-group">
    <input type="text" class="form-control" id="refLink" value="<?php echo htmlspecialchars($user['referral_link']); ?>" readonly>
    <button class="btn btn-outline-secondary" type="button" onclick="copyRefLink()">Copy</button>
  </div>
  <small class="text-muted">Share this link. When your referrals buy, you earn rewards.</small>
</div>

<div class="row mb-4" id="statsRow">
  <div class="col-md-2 col-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Clicks</div>
        <div class="fs-4 fw-bold" id="stat-clicks">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Referrals</div>
        <div class="fs-4 fw-bold" id="stat-referrals">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2 col-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Active Subs</div>
        <div class="fs-4 fw-bold" id="stat-active">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">This Month (Pending)</div>
        <div class="fs-4 fw-bold" id="stat-month">$0.00</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-12 mb-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Lifetime Earnings</div>
        <div class="fs-4 fw-bold" id="stat-lifetime">$0.00</div>
      </div>
    </div>
  </div>
</div>

<h4>Your Referral Orders</h4>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle">
  <thead class="table-light">
    <tr>
      <th>Referred Email</th>
      <th>Product</th>
      <th>Order Date</th>
      <th>Active</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $stmt = $pdo->prepare('SELECT * FROM referral_orders WHERE referral_user_id = ? ORDER BY order_date DESC LIMIT 100');
  $stmt->execute([$userId]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['referred_email']); ?></td>
      <td><?php echo htmlspecialchars($row['product_type']); ?></td>
      <td><?php echo htmlspecialchars($row['order_date']); ?></td>
      <td><?php echo $row['subscription_active'] ? 'Yes' : 'No'; ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>

<script>
function copyRefLink() {
  const input = document.getElementById('refLink');
  input.select();
  input.setSelectionRange(0, 99999);
  document.execCommand('copy');
}

fetch('/clinicsecret/api/stats.php')
  .then(r => r.json())
  .then(data => {
    if (data.error) return;
    document.getElementById('stat-clicks').textContent = data.total_clicks || 0;
    document.getElementById('stat-referrals').textContent = data.total_referrals || 0;
    document.getElementById('stat-active').textContent = data.active_subscriptions || 0;
    document.getElementById('stat-month').textContent = '$' + (data.current_month_payout || 0).toFixed(2);
    document.getElementById('stat-lifetime').textContent = '$' + (data.lifetime_payout || 0).toFixed(2);
  })
  .catch(console.error);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
