<?php
$pageTitle = 'My Profile';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name']  ?? '');
    $phone     = sanitize($_POST['phone']       ?? '');
    $address   = sanitize($_POST['address']     ?? '');
    $city      = sanitize($_POST['city']        ?? '');
    $bio       = sanitize($_POST['bio']         ?? '');

    if (strlen($firstName) < 2) $errors[] = 'First name too short.';
    if (strlen($lastName)  < 2) $errors[] = 'Last name too short.';

    // Password change (optional)
    $newPwd = $_POST['new_password'] ?? '';
    if ($newPwd) {
        if (!password_verify($_POST['current_password'] ?? '', $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPwd) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
    }

    // Profile image
    $imgPath = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $uploaded = uploadImage($_FILES['profile_image'], 'avatar');
        if (!$uploaded) $errors[] = 'Profile image upload failed. JPEG/PNG only, max 2MB.';
        else $imgPath = $uploaded;
    }

    if (empty($errors)) {
        if ($newPwd) {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        }
        $pdo->prepare(
            'UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, bio=?, profile_image=? WHERE id=?'
        )->execute([$firstName, $lastName, $phone, $address, $city, $bio, $imgPath, $userId]);

        $_SESSION['user_name']  = $firstName;
        $_SESSION['user_image'] = $imgPath;
        setFlash('success', 'Profile updated successfully!');
        header('Location: profile.php');
        exit;
    }
}

// Stats
$listingCount = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id = ?');
$listingCount->execute([$userId]); $listingCount = $listingCount->fetchColumn();

$orderCount = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE buyer_id = ?');
$orderCount->execute([$userId]); $orderCount = $orderCount->fetchColumn();

$saleCount = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE seller_id = ? AND order_status = "delivered"');
$saleCount->execute([$userId]); $saleCount = $saleCount->fetchColumn();

$rating = getSellerRating($userId);
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
      <div class="profile-card mb-3">
        <div class="profile-banner"></div>
        <div class="px-3 pb-3">
          <img src="<?= SITE_URL ?>/<?= sanitize($user['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
               class="profile-avatar mb-2"
               alt="Profile"
               onerror="this.src='<?= SITE_URL ?>/assets/images/default_avatar.png'">
          <h5 class="fw-bold mb-0"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></h5>
          <div class="text-muted small mb-1"><?= sanitize($user['email']) ?></div>
          <span class="badge bg-warning text-dark"><?= ucfirst($user['role']) ?></span>
          <?php if ($user['is_verified']): ?><span class="badge bg-success ms-1"><i class="bi bi-patch-check me-1"></i>Verified</span><?php endif; ?>
          <?php if ($user['bio']): ?><p class="mt-3 small"><?= nl2br(sanitize($user['bio'])) ?></p><?php endif; ?>
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-2 mb-3">
        <div class="col-6"><div class="stat-card"><div class="num"><?= $listingCount ?></div><div class="small text-muted">Listings</div></div></div>
        <div class="col-6"><div class="stat-card"><div class="num"><?= $orderCount ?></div><div class="small text-muted">Orders</div></div></div>
        <div class="col-6"><div class="stat-card"><div class="num"><?= $saleCount ?></div><div class="small text-muted">Sales</div></div></div>
        <div class="col-6"><div class="stat-card"><div class="num"><?= round((float)($rating['avg_rating'] ?? 0), 1) ?>★</div><div class="small text-muted"><?= $rating['total'] ?? 0 ?> Reviews</div></div></div>
      </div>

      <div class="d-grid gap-2">
        <a href="my_listings.php" class="btn btn-outline-warning"><i class="bi bi-grid me-2"></i>My Listings</a>
        <a href="orders.php" class="btn btn-outline-primary"><i class="bi bi-bag-check me-2"></i>My Orders</a>
        <a href="wishlist.php" class="btn btn-outline-secondary"><i class="bi bi-heart me-2"></i>Wishlist</a>
      </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="col-lg-8">
      <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <div class="bg-white rounded-14 shadow-sm p-4">
        <h5 class="fw-bold border-bottom pb-2 mb-4">Edit Profile</h5>
        <form method="POST" enctype="multipart/form-data">
          <?= csrfField() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Phone Number</label>
              <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">City / Town</label>
              <input type="text" name="city" class="form-control" value="<?= sanitize($user['city'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Address</label>
              <input type="text" name="address" class="form-control" value="<?= sanitize($user['address'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Bio</label>
              <textarea name="bio" class="form-control" rows="3"><?= sanitize($user['bio'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Profile Photo</label>
              <div class="d-flex align-items-center gap-3">
                <img id="prev_avatar" src="<?= SITE_URL ?>/<?= sanitize($user['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
                     width="64" height="64" class="rounded-circle object-fit-cover border">
                <input type="file" name="profile_image" accept="image/*" class="form-control img-upload-input" data-preview="prev_avatar">
              </div>
            </div>
            <div class="col-12"><hr><h6 class="fw-bold">Change Password <small class="text-muted fw-normal">(leave blank to keep current)</small></h6></div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Current Password</label>
              <input type="password" name="current_password" class="form-control" placeholder="••••••••">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning px-4 fw-bold">
                <i class="bi bi-check2-circle me-2"></i>Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
