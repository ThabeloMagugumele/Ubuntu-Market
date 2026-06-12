<?php
// run this once to set the admin password, then delete the file
require_once 'config/database.php';

$password  = 'Admin@1234';
$hash      = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$email     = 'admin@ubuntumarket.co.za';

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    $updated = $stmt->rowCount();
} catch (Exception $e) {
    die('<p style="color:red;font-family:sans-serif">DB Error: ' . htmlspecialchars($e->getMessage()) . '</p>');
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Setup</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#f8f9fa">
<div class="card shadow p-4" style="max-width:480px;width:100%">
  <h4 class="fw-bold mb-3">
    <?php if ($updated): ?>
      <span class="text-success">Admin password updated!</span>
    <?php else: ?>
      <span class="text-warning">No rows updated</span>
    <?php endif; ?>
  </h4>

  <?php if ($updated): ?>
  <table class="table table-bordered">
    <tr><th>Email</th><td><?= htmlspecialchars($email) ?></td></tr>
    <tr><th>Password</th><td><code><?= htmlspecialchars($password) ?></code></td></tr>
    <tr><th>Hash</th><td><small style="word-break:break-all"><?= htmlspecialchars($hash) ?></small></td></tr>
  </table>

  <div class="alert alert-warning mt-3">
    <strong>Important:</strong> Delete <code>setup_admin.php</code> after logging in for security.
  </div>

  <a href="admin/login.php" class="btn btn-warning w-100 fw-bold">Go to Admin Login</a>

  <?php else: ?>
  <p class="text-muted">The admin user <code><?= htmlspecialchars($email) ?></code> was not found in the database.
  Make sure you imported <code>ubuntu_market.sql</code> correctly first.</p>
  <a href="#" onclick="history.back()" class="btn btn-outline-secondary">Go Back</a>
  <?php endif; ?>
</div>
</body>
</html>
