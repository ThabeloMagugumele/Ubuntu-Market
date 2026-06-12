<?php
$pageTitle = 'Dashboard';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

// stats
$stats = [
    'total_users'      => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'total_listings'   => $pdo->query('SELECT COUNT(*) FROM listings')->fetchColumn(),
    'active_listings'  => $pdo->query('SELECT COUNT(*) FROM listings WHERE status="active"')->fetchColumn(),
    'total_orders'     => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending_orders'   => $pdo->query('SELECT COUNT(*) FROM orders WHERE order_status="pending"')->fetchColumn(),
    'total_revenue'    => $pdo->query('SELECT COALESCE(SUM(total_price),0) FROM orders WHERE payment_status="paid"')->fetchColumn(),
    'open_reports'     => $pdo->query('SELECT COUNT(*) FROM reports WHERE status="open"')->fetchColumn(),
    'new_users_today'  => $pdo->query('SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
];

// Recent orders
$recentOrders = $pdo->query(
    'SELECT o.*, l.title, ub.first_name AS buyer_fn, ub.last_name AS buyer_ln
     FROM orders o
     JOIN listings l ON o.listing_id = l.id
     JOIN users ub ON o.buyer_id = ub.id
     ORDER BY o.created_at DESC LIMIT 8'
)->fetchAll();

// Recent users
$recentUsers = $pdo->query(
    'SELECT * FROM users ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

// Listings by category for chart
$catStats = $pdo->query(
    'SELECT c.name, COUNT(l.id) as cnt FROM categories c
     LEFT JOIN listings l ON l.category_id = c.id AND l.status="active"
     GROUP BY c.id ORDER BY cnt DESC'
)->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="value"><?= number_format($stats['total_users']) ?></div>
          <div class="label">Total Users</div>
        </div>
        <i class="bi bi-people-fill fs-2 text-warning"></i>
      </div>
      <small class="text-success"><i class="bi bi-plus-circle me-1"></i><?= $stats['new_users_today'] ?> today</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card" style="border-left-color:#0d6efd">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="value"><?= number_format($stats['active_listings']) ?></div>
          <div class="label">Active Listings</div>
        </div>
        <i class="bi bi-grid-fill fs-2 text-primary"></i>
      </div>
      <small class="text-muted"><?= number_format($stats['total_listings']) ?> total</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card" style="border-left-color:#198754">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="value"><?= number_format($stats['total_orders']) ?></div>
          <div class="label">Total Orders</div>
        </div>
        <i class="bi bi-bag-check-fill fs-2 text-success"></i>
      </div>
      <small class="text-warning"><i class="bi bi-clock me-1"></i><?= $stats['pending_orders'] ?> pending</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card" style="border-left-color:#dc3545">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="value">R<?= number_format($stats['total_revenue']) ?></div>
          <div class="label">Revenue (Paid)</div>
        </div>
        <i class="bi bi-cash-coin fs-2 text-danger"></i>
      </div>
      <?php if ($stats['open_reports'] > 0): ?>
      <small class="text-danger"><i class="bi bi-flag me-1"></i><?= $stats['open_reports'] ?> open reports</small>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Charts + Recent Users -->
<div class="row g-4 mb-4">
  <!-- Category Distribution Chart -->
  <div class="col-lg-6">
    <div class="bg-white rounded-12 shadow-sm p-4">
      <h6 class="fw-bold mb-3">Listings by Category</h6>
      <canvas id="categoryChart" height="200"></canvas>
    </div>
  </div>
  <!-- Recent Users -->
  <div class="col-lg-6">
    <div class="bg-white rounded-12 shadow-sm p-4">
      <div class="d-flex justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Recent Users</h6>
        <a href="users.php" class="small text-warning">View all</a>
      </div>
      <?php foreach ($recentUsers as $u): ?>
      <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
        <img src="<?= SITE_URL ?>/<?= sanitize($u['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
             width="36" height="36" class="rounded-circle object-fit-cover">
        <div class="flex-grow-1">
          <div class="small fw-semibold"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></div>
          <div class="text-muted" style="font-size:.72rem"><?= sanitize($u['email']) ?></div>
        </div>
        <span class="badge bg-<?= $u['role'] === 'admin' || $u['role'] === 'superadmin' ? 'danger' : ($u['role'] === 'seller' ? 'warning text-dark' : 'info') ?>"><?= ucfirst($u['role']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-12 shadow-sm p-4">
  <div class="d-flex justify-content-between mb-3">
    <h6 class="fw-bold mb-0">Recent Orders</h6>
    <a href="orders.php" class="small text-warning">View all</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Item</th><th>Buyer</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php $sc = ['pending'=>'warning','confirmed'=>'primary','shipped'=>'info','delivered'=>'success','cancelled'=>'danger']; ?>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><small>#<?= $o['id'] ?></small></td>
          <td><small class="fw-semibold"><?= sanitize($o['title']) ?></small></td>
          <td><small><?= sanitize($o['buyer_fn'] . ' ' . $o['buyer_ln']) ?></small></td>
          <td class="fw-bold small"><?= formatPrice($o['total_price']) ?></td>
          <td><span class="badge bg-secondary small"><?= strtoupper(str_replace('_',' ',$o['payment_method'])) ?></span></td>
          <td><span class="badge bg-<?= $sc[$o['order_status']] ?? 'secondary' ?> small"><?= ucfirst($o['order_status']) ?></span></td>
          <td><small><?= date('d M Y', strtotime($o['created_at'])) ?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('categoryChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($catStats, 'name')) ?>,
        datasets: [{
            label: 'Active Listings',
            data: <?= json_encode(array_column($catStats, 'cnt')) ?>,
            backgroundColor: '#f5a623',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
