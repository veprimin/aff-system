<?php require_once __DIR__ . '/header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <h3 class="mb-3">Forgot Password</h3>
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <form method="post" action="/clinicsecret/api/auth.php?action=forgot">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
