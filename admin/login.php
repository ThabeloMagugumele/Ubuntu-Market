<?php
require_once '../includes/functions.php';
if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['admin','superadmin'])) {
    header('Location: ' . SITE_URL . '/admin/index.php'); exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request.'; }
    else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin','superadmin') AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['first_name'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_image'] = $user['profile_image'];
            header('Location: ' . SITE_URL . '/admin/index.php'); exit;
        } else {
            $errors[] = 'Invalid credentials or insufficient privileges.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Ubuntu Market</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body style="background:linear-gradient(135deg,#1a1a2e,#0f3460);min-height:100vh;display:flex;align-items:center">
<div class="container">
  <div class="auth-card" style="max-width:420px">
    <div class="auth-logo"><i class="bi bi-shield-lock-fill text-warning"></i></div>
    <h2 class="text-center">Admin Login</h2>
    <p class="text-center text-muted small mb-4">Ubuntu Market Administration Panel</p>

    <?php if ($errors): ?>
    <div class="alert alert-danger"><?= sanitize($errors[0]) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">Admin Email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
          <input type="email" name="email" class="form-control" required autofocus value="<?= sanitize($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="password" class="form-control" required>
        </div>
      </div>
      <button type="submit" class="btn btn-warning w-100 btn-lg fw-bold">
        <i class="bi bi-shield-check me-2"></i>Access Admin Panel
      </button>
    </form>
    <div class="text-center mt-3">
      <a href="<?= SITE_URL ?>/index.php" class="small text-muted">← Back to main site</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
