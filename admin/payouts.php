<?php require_once __DIR__ . '/header.php'; ?>

<h3 class="mb-3">Payouts</h3>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payId = (int)($_POST['action_pay'] ?? 0);
    $stopId = (int)($_POST['action_stop'] ?? 0);

    if ($payId) {
        $stmt = $pdo->prepare('UPDATE referral_payouts SET status="paid", paid_at = NOW() WHERE id = ?');
        $stmt->execute([$payId]);
    }

    if ($stopId) {
        $stmt = $pdo->prepare('UPDATE referral_payouts SET status="stopped" WHERE id = ?');
        $stmt->execute([$stopId]);
    }
}
?>

<h5>Pending Payouts</h5>
<form method="post">
  <table class="table table-sm table-bordered">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>User ID</th>
        <th>Email</th>
        <th>Product</th>
        <th>Amount</th>
        <th>Period</th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php
    $stmt = $pdo->query('SELECT * FROM referral_payouts WHERE status="pending" ORDER BY created_at ASC LIMIT 200');
    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?php echo (int)$p['id']; ?></td>
        <td><?php echo (int)$p['referral_user_id']; ?></td>
        <td><?php echo htmlspecialchars($p['referred_email']); ?></td>
        <td><?php echo htmlspecialchars($p['product_type']); ?></td>
        <td><?php echo '$' . number_format((float)$p['payout_amount'],2); ?></td>
        <td><?php echo htmlspecialchars($p['period_month']); ?></td>
        <td>
          <button type="submit" name="action_pay" value="<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-success">Mark Paid</button>
        </td>
        <td>
          <button type="submit" name="action_stop" value="<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-danger">Stop Payment</button>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</form>

<h5 class="mt-4">Stopped Payouts</h5>
<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>User ID</th>
      <th>Email</th>
      <th>Product</th>
      <th>Amount</th>
      <th>Period</th>
      <th>Updated</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $stmt = $pdo->query('SELECT * FROM referral_payouts WHERE status="stopped" ORDER BY created_at DESC LIMIT 200');
  while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
      <td><?php echo (int)$p['id']; ?></td>
      <td><?php echo (int)$p['referral_user_id']; ?></td>
      <td><?php echo htmlspecialchars($p['referred_email']); ?></td>
      <td><?php echo htmlspecialchars($p['product_type']); ?></td>
      <td><?php echo '$' . number_format((float)$p['payout_amount'],2); ?></td>
      <td><?php echo htmlspecialchars($p['period_month']); ?></td>
      <td><?php echo htmlspecialchars($p['created_at'] ?? ''); ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<h5 class="mt-4">Recent Paid Payouts</h5>
<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>User ID</th>
      <th>Email</th>
      <th>Product</th>
      <th>Amount</th>
      <th>Period</th>
      <th>Paid At</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $stmt = $pdo->query('SELECT * FROM referral_payouts WHERE status="paid" ORDER BY paid_at DESC LIMIT 200');
  while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
      <td><?php echo (int)$p['id']; ?></td>
      <td><?php echo (int)$p['referral_user_id']; ?></td>
      <td><?php echo htmlspecialchars($p['referred_email']); ?></td>
      <td><?php echo htmlspecialchars($p['product_type']); ?></td>
      <td><?php echo '$' . number_format((float)$p['payout_amount'],2); ?></td>
      <td><?php echo htmlspecialchars($p['period_month']); ?></td>
      <td><?php echo htmlspecialchars($p['paid_at']); ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
