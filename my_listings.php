<?php
$pageTitle = 'My Listings';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Delete listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $lid    = (int)($_POST['listing_id'] ?? 0);
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM listings WHERE id = ? AND seller_id = ?')->execute([$lid, $userId]);
        setFlash('success', 'Listing deleted.');
    } elseif ($action === 'toggle') {
        $row = $pdo->prepare('SELECT status FROM listings WHERE id = ? AND seller_id = ?');
        $row->execute([$lid, $userId]);
        $row = $row->fetch();
        if ($row) {
            $newStatus = $row['status'] === 'active' ? 'sold' : 'active';
            $pdo->prepare('UPDATE listings SET status = ? WHERE id = ? AND seller_id = ?')->execute([$newStatus, $lid, $userId]);
            setFlash('success', 'Listing status updated.');
        }
    }
    header('Location: my_listings.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT l.*, c.name AS category_name FROM listings l
     JOIN categories c ON l.category_id = c.id
     WHERE l.seller_id = ? ORDER BY l.created_at DESC'
);
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="section-title mb-0">My Listings</h2>
    <a href="post_listing.php" class="btn btn-warning"><i class="bi bi-plus-circle me-2"></i>Post New Listing</a>
  </div>

  <?php if (empty($listings)): ?>
  <div class="text-center py-5">
    <i class="bi bi-camera" style="font-size:4rem;color:#ddd"></i>
    <h5 class="mt-3 text-muted">You haven't posted any listings yet</h5>
    <a href="post_listing.php" class="btn btn-warning mt-3">Post Your First Listing</a>
  </div>
  <?php else: ?>
  <div class="table-responsive bg-white rounded-14 shadow-sm">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Item</th>
          <th>Category</th>
          <th>Price</th>
          <th>Status</th>
          <th>Views</th>
          <th>Posted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listings as $l): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <img src="<?= SITE_URL ?>/<?= sanitize($l['image_main'] ?? 'assets/images/no_image.png') ?>"
                   width="50" height="50" style="object-fit:cover;border-radius:8px"
                   onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
              <a href="listing.php?id=<?= $l['id'] ?>" class="fw-semibold text-decoration-none text-dark small">
                <?= sanitize($l['title']) ?>
              </a>
            </div>
          </td>
          <td><small><?= sanitize($l['category_name']) ?></small></td>
          <td class="fw-bold"><?= formatPrice($l['price']) ?></td>
          <td>
            <?php $sc = ['active'=>'success','sold'=>'secondary','pending'=>'warning','suspended'=>'danger']; ?>
            <span class="badge bg-<?= $sc[$l['status']] ?? 'secondary' ?>"><?= ucfirst($l['status']) ?></span>
          </td>
          <td><small><?= number_format($l['views']) ?></small></td>
          <td><small><?= date('d M Y', strtotime($l['created_at'])) ?></small></td>
          <td>
            <div class="d-flex gap-1">
              <a href="edit_listing.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Toggle status">
                  <i class="bi bi-toggle-on"></i>
                </button>
              </form>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this listing permanently?">
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
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
