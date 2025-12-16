<?php require_once __DIR__ . '/header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Login</h3>
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <form method="post" action="/clinicsecret/api/auth.php?action=login">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Login</button>
          <a href="/clinicsecret/forgot-password.php" class="btn btn-link">Forgot Password?</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
