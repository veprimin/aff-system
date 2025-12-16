<?php require_once __DIR__ . '/header.php'; ?>

<h3 class="mb-4">Admin Dashboard</h3>

<div class="row">
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Referral Users</div>
        <div class="fs-4 fw-bold">
          <?php
          $stmt = $pdo->query('SELECT COUNT(*) FROM referral_users');
          echo (int)$stmt->fetchColumn();
          ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Total Payouts Pending</div>
        <div class="fs-4 fw-bold">
          <?php
          $stmt = $pdo->query('SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE status="pending"');
          echo '$' . number_format((float)$stmt->fetchColumn(), 2);
          ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Total Payouts Paid</div>
        <div class="fs-4 fw-bold">
          <?php
          $stmt = $pdo->query('SELECT COALESCE(SUM(payout_amount),0) FROM referral_payouts WHERE status="paid"');
          echo '$' . number_format((float)$stmt->fetchColumn(), 2);
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<a href="/clinicsecret/admin/users.php" class="btn btn-primary mt-3">Manage Users</a>
<a href="/clinicsecret/admin/payouts.php" class="btn btn-secondary mt-3">Manage Payouts</a>

<?php require_once __DIR__ . '/footer.php'; ?>
