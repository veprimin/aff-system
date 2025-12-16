<?php require_once __DIR__ . '/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Referral Users</h3>
  <a href="/clinicsecret/admin/add_user.php" class="btn btn-primary">Add Manual User</a>
</div>

<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Email</th>
      <th>Name</th>
      <th>Code</th>
      <th>Eligible</th>
      <th>Clicks</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>

<?php
$stmt = $pdo->query("SELECT * FROM referral_users ORDER BY id DESC LIMIT 500");

while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Build safe full name
    $first = trim($u['first_name'] ?? '');
    $last  = trim($u['last_name'] ?? '');
    $fullName = trim("$first $last");
    if ($fullName === '') $fullName = 'Unknown User';

    $emailSafe = htmlspecialchars($u['email'] ?? '');
    $referralCodeSafe = htmlspecialchars($u['referral_code'] ?? '');
    $createdSafe = htmlspecialchars($u['created_at'] ?? '');
    $isEligible = !empty($u['eligible']);

    // Count clicks safely on referral_clicks table
    $clickCount = 0;
    try {
        $clickStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM referral_clicks 
            WHERE referral_user_id = ?
        ");
        $clickStmt->execute([$u['id']]);
        $clickCount = (int)$clickStmt->fetchColumn();
    } catch (Exception $e) {
        $clickCount = 0; // fallback if table doesn’t exist
    }
?>

    <tr>
      <td><?= (int)($u['id'] ?? 0) ?></td>
      <td><?= $emailSafe ?></td>
      <td><?= htmlspecialchars($fullName) ?></td>
      <td><?= $referralCodeSafe ?></td>
      <td><?= $isEligible ? 'Yes' : 'No' ?></td>
      <td><?= $clickCount ?></td>
      <td><?= $createdSafe ?></td>
      <td>
        <a href="/clinicsecret/admin/edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
        <a href="/clinicsecret/admin/user_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">View</a>
        <form action="/clinicsecret/admin/send_reset.php" method="post" style="display:inline;">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button class="btn btn-sm btn-outline-secondary">Send Reset</button>
        </form>
      </td>
    </tr>

<?php } ?>

  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
