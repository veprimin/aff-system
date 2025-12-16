<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../api/helpers.php';
require_once __DIR__ . '/../api/send_set_password.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');

    if (!$email || !$first || !$last) {
        $error = 'All fields are required.';
    } else {
        $client = get_client($pdo);
        $user = upsert_referral_user_from_order($pdo, $client, $email, $first, $last, null);

        if (empty($user['password_hash'])) {
            send_password_setup_email($user);
        }
        $success = 'User created and set-password email sent.';
    }
}
?>

<h3 class="mb-3">Add Manual Referral User</h3>

<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="post">
  <div class="mb-3">
    <label class="form-label">First Name</label>
    <input type="text" name="first_name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Last Name</label>
    <input type="text" name="last_name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Create User</button>
  <a href="/clinicsecret/admin/users.php" class="btn btn-secondary">Back</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
