<?php require_once __DIR__ . '/header.php'; ?>

<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo '<div class="alert alert-danger">Invalid product.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code   = trim($_POST['product_code'] ?? '');
    $name   = trim($_POST['product_name'] ?? '');
    $pid    = trim($_POST['samcart_product_id'] ?? '');
    $amount = (float)($_POST['payout_amount'] ?? 0);
    $ptype  = trim($_POST['payout_type'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if (!$code || !$name || !$pid || !$amount || !$ptype) {
        $err = 'All fields except Active are required.';
    } else {
        $stmt = $pdo->prepare('
            UPDATE product_map
            SET product_code = ?, product_name = ?, samcart_product_id = ?, payout_amount = ?, payout_type = ?, active = ?
            WHERE id = ?
        ');
        try {
            $stmt->execute([$code, $name, $pid, $amount, $ptype, $active, $id]);
            $ok = 'Product updated successfully.';
        } catch (Exception $e) {
            $err = 'Error updating product: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM product_map WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    echo '<div class="alert alert-danger">Product not found.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}
?>

<h3 class="mb-3">Edit Product</h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row">
    <div class="col-md-3 mb-3">
      <label class="form-label">Product Code</label>
      <input type="text" name="product_code" class="form-control" value="<?php echo htmlspecialchars($p['product_code']); ?>" required>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($p['product_name']); ?>" required>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">SamCart Product ID</label>
      <input type="text" name="samcart_product_id" class="form-control" value="<?php echo htmlspecialchars($p['samcart_product_id']); ?>" required>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Payout Amount</label>
      <input type="number" step="0.01" name="payout_amount" class="form-control" value="<?php echo htmlspecialchars($p['payout_amount']); ?>" required>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Payout Type</label>
      <select name="payout_type" class="form-select" required>
        <option value="">Select</option>
        <option value="monthly" <?php if ($p['payout_type'] === 'monthly') echo 'selected'; ?>>Monthly</option>
        <option value="onetime" <?php if ($p['payout_type'] === 'onetime') echo 'selected'; ?>>One Time</option>
      </select>
    </div>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="active" id="activeCheck" <?php if ($p['active']) echo 'checked'; ?>>
    <label class="form-check-label" for="activeCheck">Active</label>
  </div>
  <button type="submit" class="btn btn-primary">Save Changes</button>
  <a href="/clinicsecret/admin/products.php" class="btn btn-secondary">Back</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
