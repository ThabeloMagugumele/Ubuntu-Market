<?php
$pageTitle = 'Manage Orders';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$where = ['1=1'];
$params = [];
if ($statusFilter) { $where[] = 'o.order_status = ?'; $params[] = $statusFilter; }

$stmt = $pdo->prepare(
    'SELECT o.*, l.title, l.image_main,
            ub.first_name AS buyer_fn, ub.last_name AS buyer_ln,
            us.first_name AS seller_fn, us.last_name AS seller_ln
     FROM orders o
     JOIN listings l ON o.listing_id = l.id
     JOIN users ub ON o.buyer_id = ub.id
     JOIN users us ON o.seller_id = us.id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY o.created_at DESC'
);
$stmt->execute($params);
$orders = $stmt->fetchAll();
$totalRevenue = $pdo->query('SELECT COALESCE(SUM(total_price),0) FROM orders WHERE payment_status="paid"')->fetchColumn();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="kpi-card">
      <div class="value"><?= $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() ?></div>
      <div class="label">Total Orders</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="kpi-card" style="border-left-color:#ffc107">
      <div class="value"><?= $pdo->query('SELECT COUNT(*) FROM orders WHERE order_status="pending"')->fetchColumn() ?></div>
      <div class="label">Pending</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="kpi-card" style="border-left-color:#198754">
      <div class="value"><?= $pdo->query('SELECT COUNT(*) FROM orders WHERE order_status="delivered"')->fetchColumn() ?></div>
      <div class="label">Delivered</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="kpi-card" style="border-left-color:#0d6efd">
      <div class="value">R<?= number_format($totalRevenue) ?></div>
      <div class="label">Total Revenue</div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val => $label): ?>
  <a href="orders.php?status=<?= $val ?>"
     class="btn btn-sm <?= $statusFilter === $val ? 'btn-warning' : 'btn-outline-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-12 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Item</th><th>Buyer</th><th>Seller</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php $sc = ['pending'=>'warning','confirmed'=>'primary','shipped'=>'info','delivered'=>'success','cancelled'=>'danger','disputed'=>'dark']; ?>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><small>#<?= $o['id'] ?></small></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <img src="<?= SITE_URL ?>/<?= sanitize($o['image_main'] ?? 'assets/images/no_image.png') ?>"
                   width="38" height="38" style="object-fit:cover;border-radius:6px"
                   onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
              <small class="fw-semibold"><?= sanitize($o['title']) ?></small>
            </div>
          </td>
          <td><small><?= sanitize($o['buyer_fn'] . ' ' . $o['buyer_ln']) ?></small></td>
          <td><small><?= sanitize($o['seller_fn'] . ' ' . $o['seller_ln']) ?></small></td>
          <td class="fw-bold small"><?= formatPrice($o['total_price']) ?></td>
          <td><span class="badge bg-secondary small"><?= strtoupper(str_replace('_',' ',$o['payment_method'])) ?></span></td>
          <td><span class="badge bg-<?= $sc[$o['order_status']] ?? 'secondary' ?>"><?= ucfirst($o['order_status']) ?></span></td>
          <td><small><?= date('d M Y', strtotime($o['created_at'])) ?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
