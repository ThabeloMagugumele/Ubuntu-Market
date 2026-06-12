<?php
$pageTitle = 'Forgot Password';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$pdo    = getDB();
$step   = $_GET['step'] ?? 'request';  // request | reset
$token  = sanitize($_GET['token'] ?? '');
$errors = [];
$success = '';

// step 2 - validate token and show reset form
if ($step === 'reset' && $token) {
    $stmt = $pdo->prepare(
        'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0'
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
        $step = 'request';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '') && $reset) {
        $pw  = $_POST['password'] ?? '';
        $pw2 = $_POST['password2'] ?? '';

        if (strlen($pw) < 8)             $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $pw)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $pw)) $errors[] = 'Password must contain at least one number.';
        if ($pw !== $pw2)                $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([$hash, $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')
                ->execute([$token]);
            setFlash('success', 'Password reset successfully. You can now log in.');
            header('Location: login.php'); exit;
        }
    }
}

// step 1 - get email and generate reset link
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $email = sanitize($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                user_id     INT NOT NULL,
                token       VARCHAR(64) NOT NULL UNIQUE,
                expires_at  DATETIME NOT NULL,
                used        TINYINT(1) NOT NULL DEFAULT 0,
                created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$user['id']]);

            $resetToken = bin2hex(random_bytes(32));
            $expires    = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)')
                ->execute([$user['id'], $resetToken, $expires]);

            $resetUrl = SITE_URL . '/forgot_password.php?step=reset&token=' . $resetToken;
            $success  = 'Reset link generated. In a real system this would be emailed to you.';

            $_SESSION['demo_reset_url'] = $resetUrl;
        } else {
            $success = 'If that email is registered, a reset link has been sent.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-5">
      <div class="auth-card p-4 p-md-5">

        <div class="text-center mb-4">
          <i class="bi bi-shield-lock-fill text-warning" style="font-size:2.5rem"></i>
          <h4 class="fw-bold mt-2"><?= $step === 'reset' ? 'Set New Password' : 'Forgot Password' ?></h4>
          <p class="text-muted small">
            <?= $step === 'reset'
                ? 'Enter and confirm your new password below.'
                : 'Enter your registered email and we\'ll send you a reset link.' ?>
          </p>
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?><div><?= $e ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
          <?php if (!empty($_SESSION['demo_reset_url'])): ?>
            <div class="alert alert-info small">
              <strong>Demo link:</strong><br>
              <a href="<?= sanitize($_SESSION['demo_reset_url']) ?>" class="word-break-all">
                <?= sanitize($_SESSION['demo_reset_url']) ?>
              </a>
            </div>
            <?php unset($_SESSION['demo_reset_url']); ?>
          <?php endif; ?>
          <div class="text-center mt-3">
            <a href="login.php" class="btn btn-warning w-100 fw-semibold">Back to Login</a>
          </div>

        <?php elseif ($step === 'reset' && !$errors): ?>
          <!-- Reset password form -->
          <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
              <label class="form-label fw-semibold">New Password</label>
              <div class="input-group">
                <input type="password" id="pw" name="password" class="form-control"
                       placeholder="Min 8 chars, 1 uppercase, 1 number" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw')">
                  <i class="bi bi-eye" id="pw-icon"></i>
                </button>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Confirm New Password</label>
              <div class="input-group">
                <input type="password" id="pw2" name="password2" class="form-control"
                       placeholder="Repeat password" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw2')">
                  <i class="bi bi-eye" id="pw2-icon"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-semibold">Reset Password</button>
          </form>

        <?php else: ?>
          <!-- Email request form -->
          <form method="POST">
            <?= csrfField() ?>
            <div class="mb-4">
              <label class="form-label fw-semibold">Email Address</label>
              <input type="email" name="email" class="form-control"
                     placeholder="you@example.com" required autofocus>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-semibold">Send Reset Link</button>
          </form>
        <?php endif; ?>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
          Remember your password? <a href="login.php" class="text-warning fw-semibold">Sign in</a>
        </p>

      </div>
    </div>
  </div>
</div>

<script>
function togglePw(id) {
    const el = document.getElementById(id);
    const ic = document.getElementById(id + '-icon');
    if (el.type === 'password') { el.type = 'text'; ic.className = 'bi bi-eye-slash'; }
    else                        { el.type = 'password'; ic.className = 'bi bi-eye'; }
}
</script>

<?php include 'includes/footer.php'; ?>
