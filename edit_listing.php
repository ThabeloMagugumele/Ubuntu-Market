<?php
$pageTitle = 'Edit Listing';
require_once 'includes/functions.php';
requireLogin();
requireRole(['seller', 'admin', 'superadmin']);

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ? AND seller_id = ?');
$stmt->execute([$id, $uid]);
$listing = $stmt->fetch();

if (!$listing) {
    header('Location: my_listings.php'); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition   = sanitize($_POST['condition'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $quantity    = max(1, (int)($_POST['quantity'] ?? 1));

    if (strlen($title) < 3)             $errors[] = 'Title must be at least 3 characters.';
    if ($price <= 0)                    $errors[] = 'Price must be greater than zero.';
    if (!$category_id)                  $errors[] = 'Please select a category.';
    if (!in_array($condition, ['new','like_new','good','fair','poor'])) $errors[] = 'Invalid condition.';

    // images
    $image_main   = $listing['image_main'];
    $image_2      = $listing['image_2'];
    $image_3      = $listing['image_3'];

    if (!empty($_FILES['image_main']['tmp_name'])) {
        $up = uploadImage($_FILES['image_main'], 'listing');
        if ($up) $image_main = $up; else $errors[] = 'Main image: invalid type or too large (max 2MB).';
    }
    if (!empty($_FILES['image_2']['tmp_name'])) {
        $up = uploadImage($_FILES['image_2'], 'listing');
        if ($up) $image_2 = $up; else $errors[] = 'Image 2: invalid type or too large (max 2MB).';
    }
    if (!empty($_FILES['image_3']['tmp_name'])) {
        $up = uploadImage($_FILES['image_3'], 'listing');
        if ($up) $image_3 = $up; else $errors[] = 'Image 3: invalid type or too large (max 2MB).';
    }

    if (empty($errors)) {
        $pdo->prepare(
            'UPDATE listings SET title=?, description=?, price=?, category_id=?, `condition`=?,
             location=?, quantity=?, image_main=?, image_2=?, image_3=?, updated_at=NOW()
             WHERE id=? AND seller_id=?'
        )->execute([$title, $description, $price, $category_id, $condition,
                    $location, $quantity, $image_main, $image_2, $image_3, $id, $uid]);

        setFlash('success', 'Listing updated successfully!');
        header('Location: my_listings.php'); exit;
    }

    $listing = array_merge($listing, compact('title','description','price','category_id','condition','location','quantity'));
}

$categories = getCategories();
include 'includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="d-flex align-items-center gap-2 mb-4">
        <a href="my_listings.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Edit Listing</h4>
      </div>

      <?= renderFlash() ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <div class="bg-white rounded-3 shadow-sm p-4">
        <form method="POST" enctype="multipart/form-data">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= sanitize($listing['title']) ?>" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">Select category…</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= $listing['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= sanitize($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
              <select name="condition" class="form-select" required>
                <?php foreach (['new'=>'New','like_new'=>'Like New','good'=>'Good','fair'=>'Fair','poor'=>'Poor'] as $v => $l): ?>
                  <option value="<?= $v ?>" <?= $listing['condition'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Price (R) <span class="text-danger">*</span></label>
              <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                     value="<?= $listing['price'] ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Quantity</label>
              <input type="number" name="quantity" class="form-control" min="1"
                     value="<?= $listing['quantity'] ?? 1 ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Location</label>
              <input type="text" name="location" class="form-control"
                     value="<?= sanitize($listing['location'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="5"><?= sanitize($listing['description'] ?? '') ?></textarea>
          </div>

          <hr class="my-4">
          <h6 class="fw-semibold mb-3">Images <small class="text-muted fw-normal">(leave blank to keep existing)</small></h6>

          <div class="row g-3 mb-4">
            <?php
            $imgSlots = [
                ['main', $listing['image_main'], 'Main Image'],
                ['2',    $listing['image_2'],    'Image 2'],
                ['3',    $listing['image_3'],    'Image 3'],
            ];
            foreach ($imgSlots as [$slot, $src, $label]):
                $fieldName = $slot === 'main' ? 'image_main' : 'image_' . $slot;
            ?>
            <div class="col-md-4">
              <label class="form-label small fw-semibold"><?= $label ?></label>
              <?php if ($src): ?>
                <div class="mb-2">
                  <img src="<?= SITE_URL ?>/<?= sanitize($src) ?>" class="img-thumbnail" width="100"
                       onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
                </div>
              <?php endif; ?>
              <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm img-upload-input"
                     accept="image/jpeg,image/png,image/webp">
            </div>
            <?php endforeach; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-semibold px-4">Save Changes</button>
            <a href="my_listings.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
