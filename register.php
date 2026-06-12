<?php
$pageTitle = 'Register';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid request. Please try again.'; }
    else {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName  = sanitize($_POST['last_name']  ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $phone     = sanitize($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $role      = in_array($_POST['role'] ?? '', ['buyer','seller']) ? $_POST['role'] : 'buyer';

        // Validation
        if (strlen($firstName) < 2) $errors[] = 'First name must be at least 2 characters.';
        if (strlen($lastName) < 2)  $errors[] = 'Last name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8)  $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';

        if (empty($errors)) {
            $pdo = getDB();
            // Check email unique
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (?,?,?,?,?,?)'
                );
                $stmt->execute([$firstName, $lastName, $email, $phone, $hash, $role]);
                $userId = $pdo->lastInsertId();

                // Auto login
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $firstName;
                $_SESSION['user_role']  = $role;
                $_SESSION['user_image'] = 'assets/images/default_avatar.png';

                setFlash('success', 'Welcome to Ubuntu Market, ' . $firstName . '! Your account has been created.');
                header('Location: ' . SITE_URL . '/index.php');
                exit;
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <div class="auth-card">
    <div class="auth-logo"><i class="bi bi-person-plus-fill"></i></div>
    <h2>Create Account</h2>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-6">
          <label class="form-label fw-semibold">First Name *</label>
          <input type="text" name="first_name" class="form-control" value="<?= sanitize($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">Last Name *</label>
          <input type="text" name="last_name" class="form-control" value="<?= sanitize($_POST['last_name'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Email Address *</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Phone Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
            <input type="tel" name="phone" class="form-control" placeholder="+27 or 0XX XXX XXXX" value="<?= sanitize($_POST['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">I want to *</label>
          <div class="d-flex gap-3">
            <div class="form-check flex-fill border rounded p-3 cursor-pointer role-option">
              <input class="form-check-input" type="radio" name="role" id="role_buyer" value="buyer"
                     <?= (($_POST['role'] ?? 'buyer') === 'buyer') ? 'checked' : '' ?>>
              <label class="form-check-label cursor-pointer w-100" for="role_buyer">
                <i class="bi bi-bag-fill text-warning me-2"></i><strong>Buy</strong><br>
                <small class="text-muted">Shop & discover deals</small>
              </label>
            </div>
            <div class="form-check flex-fill border rounded p-3 cursor-pointer role-option">
              <input class="form-check-input" type="radio" name="role" id="role_seller" value="seller"
                     <?= (($_POST['role'] ?? '') === 'seller') ? 'checked' : '' ?>>
              <label class="form-check-label cursor-pointer w-100" for="role_seller">
                <i class="bi bi-shop text-warning me-2"></i><strong>Sell</strong><br>
                <small class="text-muted">List items &amp; earn</small>
              </label>
            </div>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Password *</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="Min 8 characters" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password', this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="form-text">Must include uppercase letter and number.</div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Confirm Password *</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password2" id="password2" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password2', this)"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="terms" required>
            <label class="form-check-label small" for="terms">
              I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
            </label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-warning w-100 btn-lg fw-bold">
            <i class="bi bi-person-check-fill me-2"></i>Create My Account
          </button>
        </div>
      </div>
    </form>

    <div class="text-center mt-4">
      <p class="text-muted">Already have an account? <a href="login.php" class="fw-semibold">Sign In</a></p>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = '<i class="bi bi-eye-slash"></i>'; }
    else { inp.type = 'password'; btn.innerHTML = '<i class="bi bi-eye"></i>'; }
}
// Highlight selected role
document.querySelectorAll('.role-option input').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('border-warning'));
        if (radio.checked) radio.closest('.role-option').classList.add('border-warning');
    });
    if (radio.checked) radio.closest('.role-option').classList.add('border-warning');
});
</script>

<?php include 'includes/footer.php'; ?>
