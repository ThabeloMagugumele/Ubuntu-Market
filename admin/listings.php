<?php
$pageTitle = 'Manage Listings';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $lid    = (int)($_POST['listing_id'] ?? 0);
    if ($action === 'suspend') {
        $pdo->prepare("UPDATE listings SET status = 'suspended' WHERE id = ?")->execute([$lid]);
        setFlash('success', 'Listing suspended.');
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE listings SET status = 'active' WHERE id = ?")->execute([$lid]);
        setFlash('success', 'Listing activated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$lid]);
        setFlash('success', 'Listing deleted.');
    }
    header('Location: listings.php'); exit;
}

$statusFilter   = sanitize($_GET['status'] ?? '');
$searchFilter   = sanitize($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
if ($statusFilter) { $where[] = 'l.status = ?'; $params[] = $statusFilter; }
if ($searchFilter) { $where[] = '(l.title LIKE ? OR u.first_name LIKE ?)'; $params = array_merge($params, ["%$searchFilter%","%$searchFilter%"]); }

$stmt = $pdo->prepare(
    'SELECT l.*, c.name AS category_name, u.first_name, u.last_name
     FROM listings l
     JOIN categories c ON l.category_id = c.id
     JOIN users u ON l.seller_id = u.id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY l.created_at DESC LIMIT 100'
);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <form class="d-flex gap-2" method="GET">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search listings..." value="<?= sanitize($searchFilter) ?>">
    <select name="status" class="form-select form-select-sm w-auto">
      <option value="">All Statuses</option>
      <?php foreach (['active','sold','pending','suspended'] as $s): ?>
      <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-warning btn-sm">Filter</button>
    <a href="listings.php" class="btn btn-outline-secondary btn-sm">Reset</a>
  </form>
</div>

<div class="bg-white rounded-12 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Listing</th><th>Seller</th><th>Category</th><th>Price</th><th>Views</th><th>Status</th><th>Posted</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $sc = ['active'=>'success','sold'=>'secondary','pending'=>'warning','suspended'=>'danger']; ?>
        <?php foreach ($listings as $l): ?>
        <tr>
          <td><small><?= $l['id'] ?></small></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <img src="<?= SITE_URL ?>/<?= sanitize($l['image_main'] ?? 'assets/images/no_image.png') ?>"
                   width="40" height="40" style="object-fit:cover;border-radius:6px"
                   onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
              <a href="<?= SITE_URL ?>/listing.php?id=<?= $l['id'] ?>" class="small fw-semibold text-decoration-none text-dark" target="_blank">
                <?= sanitize($l['title']) ?>
              </a>
            </div>
          </td>
          <td><small><?= sanitize($l['first_name'] . ' ' . $l['last_name']) ?></small></td>
          <td><small><?= sanitize($l['category_name']) ?></small></td>
          <td class="fw-bold small"><?= formatPrice($l['price']) ?></td>
          <td><small><?= number_format($l['views']) ?></small></td>
          <td><span class="badge bg-<?= $sc[$l['status']] ?? 'secondary' ?>"><?= ucfirst($l['status']) ?></span></td>
          <td><small><?= date('d M Y', strtotime($l['created_at'])) ?></small></td>
          <td>
            <div class="d-flex gap-1">
              <?php if ($l['status'] === 'active'): ?>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend">
                  <i class="bi bi-pause-circle"></i>
                </button>
              </form>
              <?php else: ?>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="activate">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                  <i class="bi bi-play-circle"></i>
                </button>
              </form>
              <?php endif; ?>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this listing?">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
