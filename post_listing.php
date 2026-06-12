<?php
$pageTitle = 'Post a Listing';
require_once 'includes/functions.php';
requireLogin();

$categories = getCategories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        $title      = sanitize($_POST['title'] ?? '');
        $desc       = sanitize($_POST['description'] ?? '');
        $price      = (float)($_POST['price'] ?? 0);
        $catId      = (int)($_POST['category_id'] ?? 0);
        $condition  = sanitize($_POST['condition_type'] ?? '');
        $qty        = max(1, (int)($_POST['quantity'] ?? 1));
        $location   = sanitize($_POST['location'] ?? '');

        $validConditions = ['new','used_like_new','used_good','used_fair'];

        if (strlen($title) < 5)            $errors[] = 'Title must be at least 5 characters.';
        if (strlen($desc) < 20)            $errors[] = 'Description must be at least 20 characters.';
        if ($price <= 0)                   $errors[] = 'Please enter a valid price.';
        if ($catId < 1)                    $errors[] = 'Please select a category.';
        if (!in_array($condition, $validConditions)) $errors[] = 'Please select a valid condition.';

        $imagePaths = [];
        foreach (['image_main', 'image_2', 'image_3'] as $key) {
            if (!empty($_FILES[$key]['name'])) {
                $path = uploadImage($_FILES[$key], 'listing');
                if (!$path) $errors[] = 'Image upload failed for ' . $key . '. Max size 2MB, JPEG/PNG only.';
                else $imagePaths[$key] = $path;
            }
        }

        if (empty($errors)) {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                'INSERT INTO listings (seller_id, category_id, title, description, price, condition_type, quantity, location, image_main, image_2, image_3)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $_SESSION['user_id'], $catId, $title, $desc, $price, $condition, $qty, $location,
                $imagePaths['image_main'] ?? 'assets/images/no_image.png',
                $imagePaths['image_2'] ?? null,
                $imagePaths['image_3'] ?? null,
            ]);
            $listingId = $pdo->lastInsertId();
            setFlash('success', 'Your listing is live!');
            header('Location: ' . SITE_URL . '/listing.php?id=' . $listingId);
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4" style="max-width:720px">
  <h2 class="section-title mb-4"><i class="bi bi-camera-fill me-2 text-warning"></i>Post a New Listing</h2>

  <?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded-14 shadow-sm">
    <?= csrfField() ?>

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label fw-semibold">Listing Title *</label>
        <input type="text" name="title" class="form-control" maxlength="200"
               placeholder="e.g. Samsung Galaxy S23 – Excellent Condition"
               value="<?= sanitize($_POST['title'] ?? '') ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Category *</label>
        <select name="category_id" class="form-select" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
            <?= sanitize($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Condition *</label>
        <select name="condition_type" class="form-select" required>
          <option value="">-- Select Condition --</option>
          <option value="new" <?= (($_POST['condition_type'] ?? '') === 'new') ? 'selected' : '' ?>>Brand New</option>
          <option value="used_like_new" <?= (($_POST['condition_type'] ?? '') === 'used_like_new') ? 'selected' : '' ?>>Used – Like New</option>
          <option value="used_good" <?= (($_POST['condition_type'] ?? '') === 'used_good') ? 'selected' : '' ?>>Used – Good</option>
          <option value="used_fair" <?= (($_POST['condition_type'] ?? '') === 'used_fair') ? 'selected' : '' ?>>Used – Fair</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Price (R) *</label>
        <div class="input-group">
          <span class="input-group-text">R</span>
          <input type="number" name="price" class="form-control" min="0.01" step="0.01"
                 value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
        </div>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Quantity Available *</label>
        <input type="number" name="quantity" class="form-control" min="1" value="<?= (int)($_POST['quantity'] ?? 1) ?>" required>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Location</label>
        <input type="text" name="location" class="form-control" placeholder="e.g. Soweto, Johannesburg"
               value="<?= sanitize($_POST['location'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Description *</label>
        <textarea name="description" class="form-control" rows="5" minlength="20"
                  placeholder="Describe your item in detail – condition, features, reason for selling..."
                  required><?= sanitize($_POST['description'] ?? '') ?></textarea>
      </div>

      <!-- Images -->
      <div class="col-12">
        <label class="form-label fw-semibold">Photos <small class="text-muted">(JPEG/PNG, max 2MB each)</small></label>
        <div class="row g-2">
          <?php foreach (['image_main' => 'Main Photo *', 'image_2' => 'Photo 2', 'image_3' => 'Photo 3'] as $key => $label): ?>
          <div class="col-md-4">
            <div class="border rounded-3 p-3 text-center bg-light">
              <img id="prev_<?= $key ?>" src="#" style="display:none;width:100%;height:120px;object-fit:cover;border-radius:8px" class="mb-2">
              <label class="d-block cursor-pointer">
                <i class="bi bi-camera fs-2 text-muted"></i>
                <div class="small text-muted mt-1"><?= $label ?></div>
                <input type="file" name="<?= $key ?>" accept="image/*"
                       class="d-none img-upload-input" data-preview="prev_<?= $key ?>">
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
          <i class="bi bi-rocket-takeoff-fill me-2"></i>Post Listing
        </button>
      </div>
    </div>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
