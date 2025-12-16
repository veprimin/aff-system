<?php require_once __DIR__ . '/header.php'; ?>

<?php
$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['user_email'] ?? '');
    $refMail = trim($_POST['referred_email'] ?? '');
    $ptype   = trim($_POST['product_type'] ?? '');
    $amount  = (float)($_POST['payout_amount'] ?? 0);
    $ptypePay = trim($_POST['payout_type'] ?? '');
    $period  = trim($_POST['period_month'] ?? '');

    if (!$email || !$refMail || !$ptype || !$amount || !$ptypePay || !$period) {
        $err = 'All fields are required.';
    } else {
        // Find referral user by email
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE slug = ? LIMIT 1');
        $stmt->execute([CLIENT_SLUG]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT * FROM referral_users WHERE client_id = ? AND email = ? LIMIT 1');
        $stmt->execute([$client['id'], $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $err = 'Referral user not found for that email.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO referral_payouts (
                    client_id,
                    referral_user_id,
                    referred_email,
                    product_type,
                    payout_amount,
                    payout_type,
                    period_month,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, "pending")
            ');
            $stmt->execute([
                $client['id'],
                $user['id'],
                $refMail,
                $ptype,
                $amount,
                $ptypePay,
                $period
            ]);
            $ok = 'Manual payout added as pending.';
        }
    }
}

// For product_type dropdown, reuse product_map codes
$stmt = $pdo->query('SELECT product_code, product_name FROM product_map WHERE active = 1 ORDER BY product_name ASC');
$prodOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3 class="mb-3">Add Manual Payout</h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">Referral User Email</label>
      <input type="email" name="user_email" class="form-control" placeholder="affiliate@domain.com" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Referred Customer Email</label>
      <input type="email" name="referred_email" class="form-control" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Product</label>
      <select name="product_type" class="form-select" required>
        <option value="">Select product</option>
        <?php foreach ($prodOptions as $p): ?>
          <option value="<?php echo htmlspecialchars($p['product_code']); ?>">
            <?php echo htmlspecialchars($p['product_name']); ?> (<?php echo htmlspecialchars($p['product_code']); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 mb-3">
      <label class="form-label">Payout Amount</label>
      <input type="number" step="0.01" name="payout_amount" class="form-control" required>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Payout Type</label>
      <select name="payout_type" class="form-select" required>
        <option value="">Select</option>
        <option value="monthly">Monthly</option>
        <option value="onetime">One Time</option>
      </select>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Period Month</label>
      <input type="date" name="period_month" class="form-control" value="<?php echo date('Y-m-01'); ?>" required>
      <small class="text-muted">Use 1st of month (e.g. 2025-02-01)</small>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Add Manual Payout</button>
  <a href="/clinicsecret/admin/payouts.php" class="btn btn-secondary">Back</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
