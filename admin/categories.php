<?php
$pageTitle = 'Manage Categories';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'bi-tag');
        if (strlen($name) >= 2) {
            try {
                $pdo->prepare('INSERT INTO categories (name, description, icon) VALUES (?,?,?)')->execute([$name, $desc, $icon]);
                setFlash('success', 'Category added.');
            } catch (PDOException $e) {
                setFlash('error', 'Category name already exists.');
            }
        }
    } elseif ($action === 'toggle') {
        $cid = (int)($_POST['cat_id'] ?? 0);
        $cur = $pdo->prepare('SELECT is_active FROM categories WHERE id = ?');
        $cur->execute([$cid]);
        $cur = $cur->fetchColumn();
        $pdo->prepare('UPDATE categories SET is_active = ? WHERE id = ?')->execute([$cur ? 0 : 1, $cid]);
        setFlash('success', 'Category updated.');
    } elseif ($action === 'delete') {
        $cid = (int)($_POST['cat_id'] ?? 0);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$cid]);
        setFlash('success', 'Category deleted.');
    }
    header('Location: categories.php'); exit;
}

$categories = $pdo->query('SELECT c.*, COUNT(l.id) AS listing_count FROM categories c LEFT JOIN listings l ON c.id = l.category_id AND l.status="active" GROUP BY c.id ORDER BY c.name')->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="row g-4">
  <!-- Add Category Form -->
  <div class="col-md-4">
    <div class="bg-white rounded-12 shadow-sm p-4">
      <h6 class="fw-bold mb-3">Add New Category</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Category Name *</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
          <input type="text" name="icon" class="form-control" placeholder="bi-tag" value="bi-tag">
          <div class="form-text">E.g. bi-phone, bi-car-front, bi-house</div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-warning w-100">Add Category</button>
      </form>
    </div>
  </div>

  <!-- Categories List -->
  <div class="col-md-8">
    <div class="bg-white rounded-12 shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Icon</th><th>Name</th><th>Listings</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
          <tr>
            <td><i class="bi <?= sanitize($c['icon']) ?> fs-5 text-warning"></i></td>
            <td>
              <div class="fw-semibold"><?= sanitize($c['name']) ?></div>
              <small class="text-muted"><?= sanitize($c['description'] ?? '') ?></small>
            </td>
            <td><span class="badge bg-secondary"><?= $c['listing_count'] ?></span></td>
            <td><span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
              <div class="d-flex gap-1">
                <form method="POST" class="d-inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>">
                    <i class="bi bi-<?= $c['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                  </button>
                </form>
                <?php if ($c['listing_count'] == 0): ?>
                <form method="POST" class="d-inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this category?">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
