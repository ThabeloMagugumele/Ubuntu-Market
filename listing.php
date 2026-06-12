<?php
require_once 'includes/functions.php';

$id   = (int)($_GET['id'] ?? 0);
$item = getListing($id);
if (!$item) { header('Location: ' . SITE_URL . '/listings.php'); exit; }

// Increment views
$pdo = getDB();
$pdo->prepare('UPDATE listings SET views = views + 1 WHERE id = ?')->execute([$id]);

$pageTitle = sanitize($item['title']);
$seller    = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$seller->execute([$item['seller_id']]);
$seller = $seller->fetch();

$rating = getSellerRating($item['seller_id']);

// Related listings
$related = getListings(['category_id' => $item['category_id']], 4, 0);
$related = array_filter($related, fn($r) => $r['id'] != $id);

// Check wishlist
$inWishlist = false;
if (isLoggedIn()) {
    $wl = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND listing_id = ?');
    $wl->execute([$_SESSION['user_id'], $id]);
    $inWishlist = (bool)$wl->fetch();
}

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireLogin();
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid request.'); }
    else {
        if ($_POST['action'] === 'add_to_cart') {
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            $pdo->prepare('INSERT INTO cart (user_id, listing_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?')
                ->execute([$_SESSION['user_id'], $id, $qty, $qty]);
            setFlash('success', 'Added to cart!');
        }
    }
    header('Location: listing.php?id=' . $id);
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<div class="bg-white border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/listings.php?cat=<?= $item['category_id'] ?>"><?= sanitize($item['category_name']) ?></a></li>
        <li class="breadcrumb-item active"><?= sanitize($item['title']) ?></li>
      </ol>
    </nav>
  </div>
</div>

<div class="container py-4">
  <div class="row g-4">

    <!-- Images -->
    <div class="col-lg-7">
      <div class="mb-2">
        <img id="mainImg" src="<?= SITE_URL ?>/<?= sanitize($item['image_main'] ?? 'assets/images/no_image.png') ?>"
             class="listing-detail-img w-100 shadow-sm"
             alt="<?= sanitize($item['title']) ?>"
             onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
      </div>
      <?php if ($item['image_2'] || $item['image_3']): ?>
      <div class="d-flex gap-2">
        <img src="<?= SITE_URL ?>/<?= sanitize($item['image_main']) ?>" class="img-thumbnail cursor-pointer thumb-img" style="width:80px;height:80px;object-fit:cover" onclick="document.getElementById('mainImg').src=this.src" alt="">
        <?php if ($item['image_2']): ?><img src="<?= SITE_URL ?>/<?= sanitize($item['image_2']) ?>" class="img-thumbnail cursor-pointer thumb-img" style="width:80px;height:80px;object-fit:cover" onclick="document.getElementById('mainImg').src=this.src" alt=""><?php endif; ?>
        <?php if ($item['image_3']): ?><img src="<?= SITE_URL ?>/<?= sanitize($item['image_3']) ?>" class="img-thumbnail cursor-pointer thumb-img" style="width:80px;height:80px;object-fit:cover" onclick="document.getElementById('mainImg').src=this.src" alt=""><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="col-lg-5">
      <div class="mb-1"><?= conditionLabel($item['condition_type']) ?> <span class="badge bg-light text-dark border ms-1"><i class="bi bi-tag me-1"></i><?= sanitize($item['category_name']) ?></span></div>
      <h2 class="fw-bold mb-1"><?= sanitize($item['title']) ?></h2>
      <div class="display-6 fw-bold text-warning mb-2"><?= formatPrice($item['price']) ?></div>

      <div class="d-flex gap-3 text-muted small mb-3">
        <span><i class="bi bi-eye me-1"></i><?= number_format($item['views']) ?> views</span>
        <span><i class="bi bi-clock me-1"></i><?= timeAgo($item['created_at']) ?></span>
        <?php if ($item['location']): ?><span><i class="bi bi-geo-alt me-1"></i><?= sanitize($item['location']) ?></span><?php endif; ?>
      </div>

      <!-- Status check -->
      <?php if ($item['status'] !== 'active'): ?>
      <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>This listing is no longer available.</div>
      <?php elseif (isLoggedIn() && $_SESSION['user_id'] == $item['seller_id']): ?>
      <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>This is your listing. <a href="edit_listing.php?id=<?= $id ?>">Edit it</a></div>
      <?php else: ?>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_to_cart">
        <div class="d-flex align-items-center gap-3 mb-3">
          <label class="fw-semibold mb-0">Qty:</label>
          <div class="qty-group d-flex align-items-center border rounded overflow-hidden">
            <button type="button" class="btn btn-sm btn-light qty-btn px-3" data-action="minus">−</button>
            <input type="number" name="qty" class="qty-input form-control border-0 text-center" value="1" min="1" max="<?= $item['quantity'] ?>" style="width:60px">
            <button type="button" class="btn btn-sm btn-light qty-btn px-3" data-action="plus">+</button>
          </div>
          <small class="text-muted"><?= $item['quantity'] ?> available</small>
        </div>
        <div class="d-flex gap-2 mb-3">
          <button type="submit" class="btn btn-warning btn-lg flex-fill fw-bold">
            <i class="bi bi-cart-plus me-2"></i>Add to Cart
          </button>
          <a href="checkout.php?buy_now=<?= $id ?>" class="btn btn-primary btn-lg flex-fill fw-bold">
            <i class="bi bi-lightning-fill me-2"></i>Buy Now
          </a>
        </div>
      </form>
      <?php endif; ?>

      <!-- Wishlist / Share -->
      <div class="d-flex gap-2 mb-4">
        <?php if (isLoggedIn() && $_SESSION['user_id'] != $item['seller_id']): ?>
        <button class="btn btn-outline-danger btn-sm wishlist-btn" data-listing-id="<?= $id ?>">
          <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?> me-1" <?= $inWishlist ? 'style="color:#e74c3c"' : '' ?>></i>
          <?= $inWishlist ? 'Saved' : 'Save' ?>
        </button>
        <?php endif; ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(window.location.href);showToast('Link copied!','success')">
          <i class="bi bi-share me-1"></i>Share
        </button>
        <?php if (isLoggedIn() && $_SESSION['user_id'] != $item['seller_id']): ?>
        <a href="report.php?listing_id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-flag me-1"></i>Report
        </a>
        <?php endif; ?>
      </div>

      <!-- Seller Card -->
      <div class="seller-card">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="<?= SITE_URL ?>/<?= sanitize($seller['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
               width="52" height="52" class="rounded-circle object-fit-cover border" alt="seller">
          <div>
            <div class="fw-bold"><?= sanitize($seller['first_name'] . ' ' . $seller['last_name']) ?></div>
            <div class="small text-muted">
              <?php $r = $rating; $avg = round((float)($r['avg_rating'] ?? 0), 1); $cnt = (int)($r['total'] ?? 0); ?>
              <span class="star-rating"><?= str_repeat('★', (int)$avg) ?><?= str_repeat('☆', 5 - (int)$avg) ?></span>
              <?= $avg ?> (<?= $cnt ?> review<?= $cnt != 1 ? 's' : '' ?>)
            </div>
            <?php if ($seller['city']): ?><div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= sanitize($seller['city']) ?></div><?php endif; ?>
          </div>
        </div>
        <?php if (isLoggedIn() && $_SESSION['user_id'] != $item['seller_id']): ?>
        <a href="messages.php?to=<?= $item['seller_id'] ?>&listing=<?= $id ?>" class="btn btn-outline-warning w-100 btn-sm">
          <i class="bi bi-chat-dots me-2"></i>Message Seller
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="mt-4 bg-white rounded-14 shadow-sm p-4">
    <h5 class="fw-bold border-bottom pb-2 mb-3">Description</h5>
    <p class="mb-0" style="white-space:pre-wrap"><?= nl2br(sanitize($item['description'])) ?></p>
  </div>

  <!-- Related Listings -->
  <?php if (!empty($related)): ?>
  <div class="mt-5">
    <h5 class="section-title mb-4">Similar Listings</h5>
    <div class="row g-3">
      <?php foreach (array_slice($related, 0, 4) as $item): ?>
      <div class="col-6 col-md-3">
        <?php include 'includes/listing_card.php'; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
