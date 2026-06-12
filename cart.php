<?php
$pageTitle = 'My Cart';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $cartId = (int)($_POST['cart_id'] ?? 0);

    if ($action === 'remove') {
        $pdo->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?')->execute([$cartId, $userId]);
        setFlash('success', 'Item removed from cart.');
    } elseif ($action === 'update') {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $pdo->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?')->execute([$qty, $cartId, $userId]);
    } elseif ($action === 'clear') {
        $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
        setFlash('info', 'Cart cleared.');
    }
    header('Location: cart.php');
    exit;
}

$items    = getCartItems($userId);
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$shipping = $subtotal > 0 ? 50 : 0;  // Flat R50 shipping demo
$total    = $subtotal + $shipping;
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h2 class="section-title mb-4"><i class="bi bi-cart3 me-2 text-warning"></i>My Cart</h2>

  <?php if (empty($items)): ?>
  <div class="text-center py-5">
    <i class="bi bi-cart-x" style="font-size:5rem;color:#ddd"></i>
    <h4 class="mt-3 text-muted">Your cart is empty</h4>
    <a href="listings.php" class="btn btn-warning mt-3">Browse Listings</a>
  </div>
  <?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <!-- Cart Items -->
      <?php foreach ($items as $item): ?>
      <div class="cart-item d-flex gap-3 align-items-start">
        <a href="listing.php?id=<?= $item['listing_id'] ?>">
          <img src="<?= SITE_URL ?>/<?= sanitize($item['image_main'] ?? 'assets/images/no_image.png') ?>"
               alt="<?= sanitize($item['title']) ?>"
               onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
        </a>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-1">
            <a href="listing.php?id=<?= $item['listing_id'] ?>" class="text-decoration-none text-dark"><?= sanitize($item['title']) ?></a>
          </h6>
          <div class="small text-muted mb-2">Seller: <?= sanitize($item['first_name'] . ' ' . $item['last_name']) ?></div>
          <?php if ($item['listing_status'] !== 'active'): ?>
          <span class="badge bg-danger">Unavailable</span>
          <?php else: ?>
          <form method="POST" class="d-flex align-items-center gap-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
            <div class="qty-group d-flex border rounded overflow-hidden">
              <button type="button" class="btn btn-sm btn-light qty-btn px-2" data-action="minus">−</button>
              <input type="number" name="quantity" class="qty-input form-control border-0 text-center" value="<?= $item['quantity'] ?>" min="1" style="width:55px" data-autosubmit="1">
              <button type="button" class="btn btn-sm btn-light qty-btn px-2" data-action="plus">+</button>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
          </form>
          <?php endif; ?>
        </div>
        <div class="text-end">
          <div class="fw-bold text-warning"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
          <div class="small text-muted"><?= formatPrice($item['price']) ?> each</div>
          <form method="POST" class="mt-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Remove this item?">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>

      <form method="POST" class="mt-2">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Clear your entire cart?">
          <i class="bi bi-trash3 me-1"></i>Clear Cart
        </button>
      </form>
    </div>

    <!-- Order Summary -->
    <div class="col-lg-4">
      <div class="order-summary sticky-top" style="top:90px">
        <h5 class="fw-bold mb-3 border-bottom pb-2">Order Summary</h5>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Subtotal (<?= count($items) ?> items)</span>
          <span><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Shipping</span>
          <span><?= formatPrice($shipping) ?></span>
        </div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
          <span>Total</span>
          <span class="text-warning"><?= formatPrice($total) ?></span>
        </div>
        <a href="checkout.php" class="btn btn-warning w-100 btn-lg fw-bold mb-2">
          <i class="bi bi-lock-fill me-2"></i>Proceed to Checkout
        </a>
        <a href="listings.php" class="btn btn-outline-secondary w-100 btn-sm">Continue Shopping</a>

        <!-- Accepted payments -->
        <div class="mt-4 text-center">
          <small class="text-muted d-block mb-2">Accepted Payment Methods</small>
          <div class="d-flex justify-content-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border">EFT</span>
            <span class="badge bg-light text-dark border">Card</span>
            <span class="badge bg-light text-dark border">Mobile Money</span>
            <span class="badge bg-light text-dark border">Cash on Delivery</span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
