<?php
$pageTitle = 'My Wishlist';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $lid = (int)($_POST['listing_id'] ?? 0);
    $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND listing_id = ?')->execute([$userId, $lid]);
    setFlash('info', 'Removed from wishlist.');
    header('Location: wishlist.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT w.*, l.title, l.price, l.image_main, l.status AS listing_status, l.condition_type,
            c.name AS category_name, u.first_name, u.last_name, u.city
     FROM wishlist w
     JOIN listings l ON w.listing_id = l.id
     JOIN categories c ON l.category_id = c.id
     JOIN users u ON l.seller_id = u.id
     WHERE w.user_id = ? ORDER BY w.added_at DESC'
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h2 class="section-title mb-4"><i class="bi bi-heart-fill me-2 text-danger"></i>My Wishlist</h2>

  <?php if (empty($items)): ?>
  <div class="text-center py-5">
    <i class="bi bi-heart" style="font-size:4rem;color:#ddd"></i>
    <h5 class="mt-3 text-muted">Your wishlist is empty</h5>
    <a href="listings.php" class="btn btn-warning mt-3">Discover Listings</a>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($items as $item): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="listing-card position-relative">
        <a href="listing.php?id=<?= $item['listing_id'] ?>">
          <img src="<?= SITE_URL ?>/<?= sanitize($item['image_main'] ?? 'assets/images/no_image.png') ?>"
               class="card-img-top" alt="<?= sanitize($item['title']) ?>"
               onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
        </a>
        <!-- Remove Button -->
        <form method="POST" style="position:absolute;top:8px;right:8px">
          <?= csrfField() ?>
          <input type="hidden" name="listing_id" value="<?= $item['listing_id'] ?>">
          <button type="submit" class="wishlist-btn" style="background:rgba(255,255,255,0.9);border:none;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center">
            <i class="bi bi-heart-fill text-danger"></i>
          </button>
        </form>
        <div class="card-body">
          <div class="price"><?= formatPrice($item['price']) ?></div>
          <h6 class="card-title small fw-semibold">
            <a href="listing.php?id=<?= $item['listing_id'] ?>" class="text-decoration-none text-dark"><?= sanitize($item['title']) ?></a>
          </h6>
          <?php if ($item['listing_status'] !== 'active'): ?>
          <span class="badge bg-secondary small">No longer available</span>
          <?php else: ?>
          <a href="cart.php?add=<?= $item['listing_id'] ?>" class="btn btn-warning btn-sm w-100 mt-1">
            <i class="bi bi-cart-plus me-1"></i>Add to Cart
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
