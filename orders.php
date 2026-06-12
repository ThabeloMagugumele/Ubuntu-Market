<?php
$pageTitle = 'My Orders';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];
$tab    = sanitize($_GET['tab'] ?? 'buying');

if ($tab === 'selling') {
    $stmt = $pdo->prepare(
        'SELECT o.*, l.title, l.image_main, u.first_name, u.last_name
         FROM orders o
         JOIN listings l ON o.listing_id = l.id
         JOIN users u ON o.buyer_id = u.id
         WHERE o.seller_id = ? ORDER BY o.created_at DESC'
    );
} else {
    $stmt = $pdo->prepare(
        'SELECT o.*, l.title, l.image_main, u.first_name AS seller_fn, u.last_name AS seller_ln
         FROM orders o
         JOIN listings l ON o.listing_id = l.id
         JOIN users u ON o.seller_id = u.id
         WHERE o.buyer_id = ? ORDER BY o.created_at DESC'
    );
}
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Update order status (seller action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? '');
    $allowed   = ['confirmed','shipped','delivered','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ? AND seller_id = ?')
            ->execute([$newStatus, $orderId, $userId]);
        setFlash('success', 'Order status updated.');
    }
    header('Location: orders.php?tab=selling');
    exit;
}

$statusColors = ['pending'=>'warning','confirmed'=>'primary','shipped'=>'info','delivered'=>'success','cancelled'=>'danger','disputed'=>'dark'];
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h2 class="section-title mb-4"><i class="bi bi-bag-check me-2 text-warning"></i>My Orders</h2>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'buying' ? 'active' : '' ?>" href="orders.php?tab=buying">
        <i class="bi bi-bag me-1"></i>Purchases
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab === 'selling' ? 'active' : '' ?>" href="orders.php?tab=selling">
        <i class="bi bi-shop me-1"></i>Sales
      </a>
    </li>
  </ul>

  <?php if (empty($orders)): ?>
  <div class="text-center py-5">
    <i class="bi bi-bag-x" style="font-size:4rem;color:#ddd"></i>
    <h5 class="mt-3 text-muted">No orders yet</h5>
    <a href="listings.php" class="btn btn-warning mt-3">Browse Listings</a>
  </div>
  <?php else: ?>
  <div class="table-responsive bg-white rounded-14 shadow-sm">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#ID</th>
          <th>Item</th>
          <th><?= $tab === 'buying' ? 'Seller' : 'Buyer' ?></th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <?php if ($tab === 'selling'): ?><th>Action</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><small>#<?= $o['id'] ?></small></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <img src="<?= SITE_URL ?>/<?= sanitize($o['image_main'] ?? 'assets/images/no_image.png') ?>"
                   width="45" height="45" style="object-fit:cover;border-radius:6px"
                   onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
              <span class="small fw-semibold"><?= sanitize($o['title']) ?></span>
            </div>
          </td>
          <td class="small">
            <?php if ($tab === 'buying'): ?>
              <?= sanitize($o['seller_fn'] . ' ' . $o['seller_ln']) ?>
            <?php else: ?>
              <?= sanitize($o['first_name'] . ' ' . $o['last_name']) ?>
            <?php endif; ?>
          </td>
          <td class="fw-bold"><?= formatPrice($o['total_price']) ?></td>
          <td><span class="badge bg-secondary"><?= strtoupper(str_replace('_',' ',$o['payment_method'])) ?></span></td>
          <td><span class="badge bg-<?= $statusColors[$o['order_status']] ?? 'secondary' ?>"><?= ucfirst($o['order_status']) ?></span></td>
          <td><small><?= date('d M Y', strtotime($o['created_at'])) ?></small></td>
          <?php if ($tab === 'selling'): ?>
          <td>
            <form method="POST" class="d-flex gap-1">
              <?= csrfField() ?>
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <select name="status" class="form-select form-select-sm" style="width:120px">
                <?php foreach (['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $o['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-warning">Update</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
