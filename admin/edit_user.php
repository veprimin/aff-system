<?php
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    echo "Missing user ID";
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT * FROM referral_users WHERE id = ? LIMIT 1
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? "");
    $lastName  = trim($_POST['last_name'] ?? "");
    $eligible  = isset($_POST['eligible']) ? 1 : 0;

    if ($firstName === "") $firstName = "Unknown";
    if ($lastName === "")  $lastName = "User";

    $update = $pdo->prepare("
        UPDATE referral_users
        SET first_name = ?, 
            last_name  = ?, 
            eligible   = ?
        WHERE id = ?
    ");
    $update->execute([$firstName, $lastName, $eligible, $id]);

    echo "<div class='alert alert-success'>User Updated!</div>";

    // Reload user
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<h3>Edit Referral User</h3>

<form method="post">
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="text" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
  </div>

  <div class="mb-3">
    <label class="form-label">First Name</label>
    <input type="text" name="first_name" class="form-control"
           value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Last Name</label>
    <input type="text" name="last_name" class="form-control"
           value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label>
      <input type="checkbox" name="eligible"
             <?= !empty($user['eligible']) ? 'checked' : '' ?>>
      Eligible for payouts
    </label>
  </div>

  <button class="btn btn-primary">Save Changes</button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
