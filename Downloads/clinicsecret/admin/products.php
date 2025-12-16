<?php require_once __DIR__ . '/header.php'; ?>

<h3 class="mb-3">Product Mapping & Payouts</h3>

<?php
$err = '';
$ok  = '';

// Handle new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $code   = trim($_POST['product_code'] ?? '');
    $name   = trim($_POST['product_name'] ?? '');
    $pid    = trim($_POST['samcart_product_id'] ?? '');
    $amount = (float)($_POST['payout_amount'] ?? 0);
    $ptype  = trim($_POST['payout_type'] ?? '');

    if (!$code || !$name || !$pid || !$amount || !$ptype) {
        $err = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO product_map (product_code, product_name, samcart_product_id, payout_amount, payout_type, active)
            VALUES (?, ?, ?, ?, ?, 1)
        ');
        try {
            $stmt->execute([$code, $name, $pid, $amount, $ptype]);
            $ok = 'Product added successfully.';
        } catch (Exception $e) {
            $err = 'Error adding product: ' . $e->getMessage();
        }
    }
}

// Fetch products
$stmt = $pdo->query('SELECT * FROM product_map ORDER BY id DESC');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($err): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">Add New Product Mapping</h5>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Product Code (internal)</label>
          <input type="text" name="product_code" class="form-control" placeholder="sema_monthly" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" name="product_name" class="form-control" placeholder="Semaglutide Monthly" required>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">SamCart Product ID</label>
          <input type="text" name="samcart_product_id" class="form-control" placeholder="123" required>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">Payout Amount</label>
          <input type="number" step="0.01" name="payout_amount" class="form-control" required>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label">Payout Type</label>
          <select name="payout_type" class="form-select" required>
            <option value="">Select</option>
            <option value="monthly">Monthly</option>
            <option value="onetime">One Time</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Add Product</button>
    </form>
  </div>
</div>

<h5>Existing Products</h5>
<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Code</th>
      <th>Name</th>
      <th>SamCart ID</th>
      <th>Payout</th>
      <th>Type</th>
      <th>Active</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?php echo (int)$p['id']; ?></td>
      <td><?php echo htmlspecialchars($p['product_code']); ?></td>
      <td><?php echo htmlspecialchars($p['product_name']); ?></td>
      <td><?php echo htmlspecialchars($p['samcart_product_id']); ?></td>
      <td><?php echo '$' . number_format((float)$p['payout_amount'], 2); ?></td>
      <td><?php echo htmlspecialchars($p['payout_type']); ?></td>
      <td><?php echo $p['active'] ? 'Yes' : 'No'; ?></td>
      <td>
        <a href="/clinicsecret/admin/product_edit.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
