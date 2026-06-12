<?php
$pageTitle = 'Login';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Please fill in all fields.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['first_name'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_image'] = $user['profile_image'] ?? 'assets/images/default_avatar.png';

                // Admin redirect
                if (in_array($user['role'], ['admin','superadmin'])) {
                    setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
                    header('Location: ' . SITE_URL . '/admin/index.php');
                    exit;
                }

                setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
                $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Invalid email or password. Please try again.';
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-5">
  <div class="auth-card">
    <div class="auth-logo"><i class="bi bi-shop-window"></i></div>
    <h2>Welcome Back</h2>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><p class="mb-0"><?= sanitize($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
          <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" required autofocus>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()"><i class="bi bi-eye"></i></button>
        </div>
        <div class="d-flex justify-content-end mt-1">
          <a href="forgot_password.php" class="small text-muted">Forgot password?</a>
        </div>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label small" for="remember">Keep me signed in</label>
      </div>
      <button type="submit" class="btn btn-warning w-100 btn-lg fw-bold mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <div class="text-center mt-3">
      <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="fw-semibold">Register free</a></p>
    </div>
  </div>
</div>

<script>
function togglePwd() {
    const inp = document.getElementById('pwd');
    const btn = inp.nextElementSibling;
    if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = '<i class="bi bi-eye-slash"></i>'; }
    else { inp.type = 'password'; btn.innerHTML = '<i class="bi bi-eye"></i>'; }
}
</script>

<?php include 'includes/footer.php'; ?>
