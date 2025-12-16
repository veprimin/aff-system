<?php
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    echo "Missing user ID";
    exit;
}

$id = (int)$_GET['id'];

// Load user
$stmt = $pdo->prepare("SELECT * FROM referral_users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found";
    exit;
}

// Load click count
$clickStmt = $pdo->prepare("
    SELECT COUNT(*) FROM referral_clicks WHERE referral_user_id = ?
");
$clickStmt->execute([$id]);
$clickCount = (int)$clickStmt->fetchColumn();

// Load referral orders
$orderStmt = $pdo->prepare("
    SELECT * FROM referral_orders
    WHERE referral_user_id = ?
    ORDER BY id DESC
");
$orderStmt->execute([$id]);

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>

<h3>User Detail</h3>

<div class="card mb-3">
  <div class="card-body">
    <h5><?= htmlspecialchars($fullName) ?></h5>
    <p>Email: <?= htmlspecialchars($user['email']) ?></p>
    <p>Referral Code: <?= htmlspecialchars($user['referral_code']) ?></p>
    <p>Eligible: <?= $user['eligible'] ? 'Yes' : 'No' ?></p>
    <p>Clicks: <?= $clickCount ?></p>
    <p>Created: <?= htmlspecialchars($user['created_at']) ?></p>

    <a href="/clinicsecret/admin/edit_user.php?id=<?= $id ?>" class="btn btn-warning">Edit User</a>
  </div>
</div>

<h4>Referral Orders</h4>
<table class="table table-bordered table-sm">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Email</th>
      <th>Product Type</th>
      <th>Order Amount</th>
      <th>Order ID</th>
      <th>Subscription</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>

<?php while ($o = $orderStmt->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
  <td><?= $o['id'] ?></td>
  <td><?= htmlspecialchars($o['referred_email']) ?></td>
  <td><?= htmlspecialchars($o['product_type']) ?></td>
  <td>$<?= number_format($o['order_amount'], 2) ?></td>
  <td><?= htmlspecialchars($o['samcart_order_id']) ?></td>
  <td><?= htmlspecialchars($o['samcart_subscription_id']) ?></td>
  <td><?= htmlspecialchars($o['created_at']) ?></td>
</tr>
<?php endwhile; ?>

  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
