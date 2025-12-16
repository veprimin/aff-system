<?php require_once __DIR__ . '/header.php'; ?>

<?php
function safeFetchColumn(PDO $pdo, string $sql, $default = 0) {
    try {
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            return $default;
        }
        return $stmt->fetchColumn() ?? $default;
    } catch (Throwable $e) {
        error_log('Dashboard metric query failed: ' . $e->getMessage());
        return $default;
    }
}

function safeFetchAll(PDO $pdo, string $sql): array {
    try {
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Dashboard list query failed: ' . $e->getMessage());
        return [];
    }
}

$totalUsers = (int)safeFetchColumn($pdo, 'SELECT COUNT(*) FROM referral_users');
$pendingPayouts = (float)safeFetchColumn($pdo, 'SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE status="pending"');
$paidPayouts = (float)safeFetchColumn($pdo, 'SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE status="paid"');
$totalReferrals = (int)safeFetchColumn($pdo, 'SELECT COUNT(*) FROM referral_orders');
$newUsersToday = (int)safeFetchColumn($pdo, 'SELECT COUNT(*) FROM referral_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');

$latestUsers = safeFetchAll($pdo, 'SELECT id, email, first_name, last_name, referral_code, created_at FROM referral_users ORDER BY created_at DESC LIMIT 6');

$recentOrders = safeFetchAll($pdo, 'SELECT ro.*, ru.first_name, ru.last_name, ru.email AS referrer_email FROM referral_orders ro LEFT JOIN referral_users ru ON ro.referral_user_id = ru.id ORDER BY ro.created_at DESC LIMIT 10');
?>

<h3 class="mb-4">Admin Dashboard</h3>

<div class="row g-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted">Referral Users</div>
        <div class="fs-3 fw-bold"><?php echo $totalUsers; ?></div>
        <div class="small text-muted">Total ambassadors created</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted">Payouts Pending</div>
        <div class="fs-3 fw-bold text-warning">$<?php echo number_format($pendingPayouts, 2); ?></div>
        <div class="small text-muted">Awaiting review</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted">Payouts Paid</div>
        <div class="fs-3 fw-bold text-success">$<?php echo number_format($paidPayouts, 2); ?></div>
        <div class="small text-muted">Lifetime payouts</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="text-muted">New Users (24h)</div>
        <div class="fs-3 fw-bold"><?php echo $newUsersToday; ?></div>
        <div class="small text-muted">Fresh signups to monitor</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="text-muted">Referral Orders Logged</div>
            <div class="fs-3 fw-bold"><?php echo $totalReferrals; ?></div>
          </div>
          <span class="badge bg-primary">Tracking</span>
        </div>
        <p class="mb-0 text-muted small">Referrals recorded across all ambassadors.</p>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">Latest Ambassador Signups</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Referral Code</th>
                <th class="text-end">Created</th>
              </tr>
            </thead>
            <tbody>
            <?php if (count($latestUsers) === 0): ?>
              <tr><td colspan="3" class="text-center text-muted">No users found.</td></tr>
            <?php else: ?>
              <?php foreach ($latestUsers as $user):
                  $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                  if ($fullName === '') $fullName = 'Unknown User';
                  $createdAt = $user['created_at'] ?? '';
                  $createdText = $createdAt ? date('M j, Y g:ia', strtotime($createdAt)) : '—';
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($fullName); ?></div>
                  <div class="text-muted small"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                </td>
                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($user['referral_code'] ?? ''); ?></span></td>
                <td class="text-end">
                  <span class="badge bg-success-subtle text-success border">New</span>
                  <div class="text-muted small"><?php echo $createdText; ?></div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mt-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Recent Referral Activity</h5>
      <div>
        <a href="/clinicsecret/admin/users.php" class="btn btn-sm btn-outline-primary">Manage Users</a>
        <a href="/clinicsecret/admin/payouts.php" class="btn btn-sm btn-outline-secondary ms-2">Manage Payouts</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Referred User</th>
            <th>Referred By</th>
            <th>Order #</th>
            <th>Product</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($recentOrders) === 0): ?>
          <tr><td colspan="5" class="text-center text-muted">No referral activity logged yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentOrders as $order):
              $referrerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
              $referrerEmail = $order['referrer_email'] ?? '';
              $hasReferrer = !empty($order['referral_user_id']);
              $referrerLabel = $referrerName !== '' ? $referrerName : ($referrerEmail ?: 'Unassigned');
              $orderDate = $order['created_at'] ?? '';
              $orderDateText = $orderDate ? date('M j, Y g:ia', strtotime($orderDate)) : '—';
          ?>
          <tr>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($order['referred_email'] ?? ''); ?></div>
              <span class="badge <?php echo $hasReferrer ? 'bg-success' : 'bg-secondary'; ?>">
                <?php echo $hasReferrer ? 'Referred' : 'Not referred'; ?>
              </span>
            </td>
            <td>
              <?php if ($hasReferrer): ?>
                <div class="fw-semibold"><?php echo htmlspecialchars($referrerLabel); ?></div>
                <div class="text-muted small">Ambassador ID #<?php echo (int)($order['referral_user_id'] ?? 0); ?></div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="fw-semibold">#<?php echo htmlspecialchars($order['samcart_order_id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($order['product_type'] ?? ''); ?></td>
            <td class="text-muted small"><?php echo $orderDateText; ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
