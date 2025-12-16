<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Clinic Secret</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="bg-light">
<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h3 class="mb-3">Admin Login</h3>
          <?php if (!empty($_SESSION['admin_error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['admin_error']); ?></div>
            <?php unset($_SESSION['admin_error']); ?>
          <?php endif; ?>
          <form method="post" action="/clinicsecret/admin/auth.php">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
