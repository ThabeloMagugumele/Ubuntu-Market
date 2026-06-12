<?php
$pageTitle = 'Checkout';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser();

// buy now
$buyNowId = (int)($_GET['buy_now'] ?? 0);
if ($buyNowId) {
    $item = getListing($buyNowId);
    if (!$item || $item['status'] !== 'active') { setFlash('error','Item unavailable.'); header('Location: listings.php'); exit; }
    $items = [array_merge($item, ['listing_id' => $item['id'], 'quantity' => 1])];
} else {
    $items = getCartItems($userId);
    if (empty($items)) { header('Location: cart.php'); exit; }
}

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
$shipping = 50;
$total    = $subtotal + $shipping;
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        $deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
        $paymentMethod   = sanitize($_POST['payment_method'] ?? '');
        $notes           = sanitize($_POST['notes'] ?? '');
        $validPayments   = ['eft','card','mobile_money','cash_on_delivery'];

        if (strlen($deliveryAddress) < 10) $errors[] = 'Please enter a valid delivery address.';
        if (!in_array($paymentMethod, $validPayments)) $errors[] = 'Please select a payment method.';

        if (empty($errors)) {
            foreach ($items as $item) {
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (buyer_id, seller_id, listing_id, quantity, unit_price, total_price, payment_method, delivery_address, notes)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                );
                $itemTotal = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $userId, $item['seller_id'], $item['listing_id'],
                    $item['quantity'], $item['price'], $itemTotal,
                    $paymentMethod, $deliveryAddress, $notes
                ]);
                $pdo->prepare('UPDATE listings SET quantity = quantity - ? WHERE id = ? AND quantity >= ?')
                    ->execute([$item['quantity'], $item['listing_id'], $item['quantity']]);
            }
            if (!$buyNowId) {
                $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
            }
            setFlash('success', 'Order placed successfully! The seller will be in touch shortly.');
            header('Location: orders.php');
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h2 class="section-title mb-4"><i class="bi bi-bag-check me-2 text-warning"></i>Checkout</h2>

  <?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <form method="POST" class="bg-white rounded-14 shadow-sm p-4">
        <?= csrfField() ?>
        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-truck me-2 text-warning"></i>Delivery Details</h5>
        <div class="mb-3">
          <label class="form-label fw-semibold">Full Delivery Address *</label>
          <textarea name="delivery_address" class="form-control" rows="3"
                    placeholder="Street address, suburb, city, province"
                    required><?= sanitize($user['address'] ?? '') ?></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Order Notes (optional)</label>
          <input type="text" name="notes" class="form-control" placeholder="Any special instructions for the seller?">
        </div>

        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-credit-card me-2 text-warning"></i>Payment Method</h5>
        <div class="row g-2 mb-4">
          <?php $methods = [
            'eft'             => ['icon' => 'bi-bank2',        'label' => 'EFT / Bank Transfer',   'desc' => 'Pay directly to seller\'s bank account'],
            'card'            => ['icon' => 'bi-credit-card',  'label' => 'Debit / Credit Card',   'desc' => 'Secure online card payment'],
            'mobile_money'    => ['icon' => 'bi-phone',        'label' => 'Mobile Money',           'desc' => 'Pay with your mobile wallet'],
            'cash_on_delivery'=> ['icon' => 'bi-cash-coin',    'label' => 'Cash on Delivery',       'desc' => 'Pay when you receive the item'],
          ]; ?>
          <?php foreach ($methods as $val => $m): ?>
          <div class="col-6">
            <label class="d-flex align-items-center gap-2 border rounded-3 p-3 cursor-pointer payment-option">
              <input type="radio" name="payment_method" value="<?= $val ?>" class="form-check-input mt-0" required>
              <span>
                <i class="bi <?= $m['icon'] ?> fs-5 text-warning d-block"></i>
                <strong class="small"><?= $m['label'] ?></strong>
                <span class="d-block small text-muted"><?= $m['desc'] ?></span>
              </span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-warning w-100 btn-lg fw-bold">
          <i class="bi bi-lock-fill me-2"></i>Place Order – <?= formatPrice($total) ?>
        </button>
        <small class="text-muted d-block text-center mt-2">
          <i class="bi bi-shield-lock me-1"></i>Your transaction is protected by Ubuntu Market
        </small>
      </form>
    </div>

    <!-- Order Summary -->
    <div class="col-lg-5">
      <div class="order-summary">
        <h5 class="fw-bold mb-3 border-bottom pb-2">Order Summary</h5>
        <?php foreach ($items as $item): ?>
        <div class="d-flex gap-2 mb-2 align-items-center">
          <img src="<?= SITE_URL ?>/<?= sanitize($item['image_main'] ?? 'assets/images/no_image.png') ?>"
               width="48" height="48" style="object-fit:cover;border-radius:6px"
               onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
          <div class="flex-grow-1">
            <div class="small fw-semibold"><?= sanitize($item['title']) ?></div>
            <div class="small text-muted">Qty: <?= $item['quantity'] ?></div>
          </div>
          <div class="small fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
        </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Shipping</span><span><?= formatPrice($shipping) ?></span></div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span class="text-warning"><?= formatPrice($total) ?></span></div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.payment-option input').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('border-warning'));
        if (radio.checked) radio.closest('.payment-option').classList.add('border-warning');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
