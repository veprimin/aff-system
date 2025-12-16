<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/connect.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('This link is invalid or expired.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE referral_users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $row['referral_user_id']]);
        $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = ?');
        $stmt->execute([$row['id']]);
        echo 'Password set successfully. You may now <a href="/clinicsecret/login.php">login</a>.';
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Set Your Password</h3>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Save Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
