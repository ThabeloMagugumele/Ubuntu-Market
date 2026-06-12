<?php
$pageTitle = 'User Reports';
require_once '../includes/functions.php';
requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $rid    = (int)($_POST['report_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    if (in_array($status, ['reviewed','resolved','dismissed'])) {
        $pdo->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute([$status, $rid]);
        setFlash('success', 'Report status updated.');
    }
    header('Location: reports.php'); exit;
}

$reports = $pdo->query(
    'SELECT r.*, u.first_name, u.last_name, u.email,
            ru.first_name AS rep_user_fn, ru.last_name AS rep_user_ln,
            l.title AS listing_title
     FROM reports r
     JOIN users u ON r.reporter_id = u.id
     LEFT JOIN users ru ON r.reported_user_id = ru.id
     LEFT JOIN listings l ON r.reported_listing_id = l.id
     ORDER BY r.created_at DESC'
)->fetchAll();
?>
<?php include 'includes/admin_header.php'; ?>

<div class="bg-white rounded-12 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Reporter</th><th>Reported</th><th>Reason</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php $sc = ['open'=>'danger','reviewed'=>'warning','resolved'=>'success','dismissed'=>'secondary']; ?>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><small><?= $r['id'] ?></small></td>
          <td><small class="fw-semibold"><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?><br><span class="text-muted"><?= sanitize($r['email']) ?></span></small></td>
          <td>
            <small>
              <?php if ($r['rep_user_fn']): ?><strong>User:</strong> <?= sanitize($r['rep_user_fn'] . ' ' . $r['rep_user_ln']) ?><?php endif; ?>
              <?php if ($r['listing_title']): ?><br><strong>Listing:</strong> <?= sanitize($r['listing_title']) ?><?php endif; ?>
            </small>
          </td>
          <td><small><?= sanitize($r['reason']) ?></small></td>
          <td><span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
          <td><small><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
          <td>
            <form method="POST" class="d-flex gap-1">
              <?= csrfField() ?>
              <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
              <select name="status" class="form-select form-select-sm w-auto">
                <?php foreach (['open','reviewed','resolved','dismissed'] as $s): ?>
                <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-warning">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($reports)): ?>
    <div class="p-4 text-center text-muted">No reports submitted yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
