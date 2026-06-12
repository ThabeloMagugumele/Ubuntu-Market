<?php
$pageTitle = 'Manage Users';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active') {
        $cur = $pdo->prepare('SELECT is_active FROM users WHERE id = ?');
        $cur->execute([$uid]);
        $cur = $cur->fetchColumn();
        $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$cur ? 0 : 1, $uid]);
        setFlash('success', 'User status updated.');
    } elseif ($action === 'change_role' && $_SESSION['user_role'] === 'superadmin') {
        $role = sanitize($_POST['role'] ?? '');
        if (in_array($role, ['buyer','seller','admin'])) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
            setFlash('success', 'User role updated.');
        }
    } elseif ($action === 'delete' && $_SESSION['user_role'] === 'superadmin') {
        $pdo->prepare('DELETE FROM users WHERE id = ? AND role NOT IN ("superadmin")')->execute([$uid]);
        setFlash('success', 'User deleted.');
    }
    header('Location: users.php'); exit;
}

// Filters
$roleFilter   = sanitize($_GET['role'] ?? '');
$searchFilter = sanitize($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
if ($roleFilter) { $where[] = 'role = ?'; $params[] = $roleFilter; }
if ($searchFilter) { $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)'; $params = array_merge($params, ["%$searchFilter%","%$searchFilter%","%$searchFilter%"]); }

$stmt = $pdo->prepare('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div></div>
  <form class="d-flex gap-2" method="GET">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search users..." value="<?= sanitize($searchFilter) ?>">
    <select name="role" class="form-select form-select-sm w-auto">
      <option value="">All Roles</option>
      <option value="buyer" <?= $roleFilter==='buyer'?'selected':'' ?>>Buyer</option>
      <option value="seller" <?= $roleFilter==='seller'?'selected':'' ?>>Seller</option>
      <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
    </select>
    <button type="submit" class="btn btn-warning btn-sm">Filter</button>
    <a href="users.php" class="btn btn-outline-secondary btn-sm">Reset</a>
  </form>
</div>

<div class="bg-white rounded-12 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><small><?= $u['id'] ?></small></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <img src="<?= SITE_URL ?>/<?= sanitize($u['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
                   width="36" height="36" class="rounded-circle object-fit-cover">
              <span class="small fw-semibold"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></span>
            </div>
          </td>
          <td><small><?= sanitize($u['email']) ?></small></td>
          <td><small><?= sanitize($u['phone'] ?? '-') ?></small></td>
          <td>
            <?php if ($_SESSION['user_role'] === 'superadmin' && $u['id'] !== (int)$_SESSION['user_id']): ?>
            <form method="POST" class="d-inline">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="change_role">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role" class="form-select form-select-sm w-auto d-inline" onchange="this.form.submit()">
                <?php foreach (['buyer','seller','admin'] as $r): ?>
                <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <?php else: ?>
            <span class="badge bg-<?= $u['role'] === 'admin' || $u['role'] === 'superadmin' ? 'danger' : ($u['role'] === 'seller' ? 'warning text-dark' : 'info') ?>"><?= ucfirst($u['role']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Suspended' ?></span>
          </td>
          <td><small><?= date('d M Y', strtotime($u['created_at'])) ?></small></td>
          <td>
            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
            <div class="d-flex gap-1">
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" title="<?= $u['is_active'] ? 'Suspend' : 'Activate' ?>">
                  <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                </button>
              </form>
              <?php if ($_SESSION['user_role'] === 'superadmin'): ?>
              <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this user permanently?">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <small class="text-muted">You</small>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
