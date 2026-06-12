<?php
$pageTitle = 'Report';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$uid    = $_SESSION['user_id'];
$errors = [];

$reportedListingId = (int)($_GET['listing_id'] ?? 0);
$reportedUserId    = (int)($_GET['user_id'] ?? 0);

$targetListing = null;
$targetUser    = null;

if ($reportedListingId) {
    $stmt = $pdo->prepare('SELECT id, title, seller_id FROM listings WHERE id = ? AND status = "active"');
    $stmt->execute([$reportedListingId]);
    $targetListing = $stmt->fetch();
    if (!$targetListing) { header('Location: listings.php'); exit; }
    $reportedUserId = (int)$targetListing['seller_id'];
}

if (!$reportedListingId && $reportedUserId) {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([$reportedUserId]);
    $targetUser = $stmt->fetch();
    if (!$targetUser || $targetUser['id'] === $uid) { header('Location: index.php'); exit; }
}

if (!$reportedListingId && !$reportedUserId) {
    header('Location: index.php'); exit;
}

$reasons = [
    'spam'           => 'Spam or misleading',
    'fraud'          => 'Fraud or scam',
    'prohibited'     => 'Prohibited item',
    'counterfeit'    => 'Counterfeit product',
    'harassment'     => 'Harassment or abuse',
    'inappropriate'  => 'Inappropriate content',
    'other'          => 'Other',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $reason  = sanitize($_POST['reason'] ?? '');
    $details = sanitize($_POST['details'] ?? '');

    if (!array_key_exists($reason, $reasons)) $errors[] = 'Please select a valid reason.';
    if (strlen($details) > 1000)              $errors[] = 'Details must be under 1000 characters.';

    // check for duplicate open report
    if ($reportedListingId) {
        $dupeCheck = $pdo->prepare('SELECT id FROM reports WHERE reporter_id = ? AND reported_listing_id = ? AND status = "open"');
        $dupeCheck->execute([$uid, $reportedListingId]);
    } else {
        $dupeCheck = $pdo->prepare('SELECT id FROM reports WHERE reporter_id = ? AND reported_user_id = ? AND status = "open"');
        $dupeCheck->execute([$uid, $reportedUserId]);
    }
    if ($dupeCheck->fetch()) {
        $errors[] = 'You have already submitted an open report for this item.';
    }

    if (empty($errors)) {
        $pdo->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, reported_listing_id, reason, details, status, created_at)
             VALUES (?, ?, ?, ?, ?, "open", NOW())'
        )->execute([
            $uid,
            $reportedUserId    ?: null,
            $reportedListingId ?: null,
            $reasons[$reason],
            $details,
        ]);

        setFlash('success', 'Your report has been submitted and will be reviewed by our team.');
        $redirect = $reportedListingId ? 'listing.php?id=' . $reportedListingId : 'index.php';
        header('Location: ' . $redirect); exit;
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

      <div class="d-flex align-items-center gap-2 mb-4">
        <?php if ($reportedListingId): ?>
          <a href="listing.php?id=<?= $reportedListingId ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
          </a>
        <?php endif; ?>
        <h4 class="mb-0 fw-bold text-danger"><i class="bi bi-flag me-2"></i>Submit a Report</h4>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e): ?><div><?= $e ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- What is being reported -->
      <div class="bg-light rounded-3 p-3 mb-4 border">
        <?php if ($targetListing): ?>
          <div class="small text-muted mb-1">Reporting listing:</div>
          <div class="fw-semibold"><?= sanitize($targetListing['title']) ?></div>
        <?php elseif ($targetUser): ?>
          <div class="small text-muted mb-1">Reporting user:</div>
          <div class="fw-semibold"><?= sanitize($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-3 shadow-sm p-4">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="listing_id" value="<?= $reportedListingId ?>">
          <input type="hidden" name="user_id" value="<?= $reportedUserId ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
            <?php foreach ($reasons as $val => $label): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="reason"
                       id="r_<?= $val ?>" value="<?= $val ?>"
                       <?= (($_POST['reason'] ?? '') === $val) ? 'checked' : '' ?> required>
                <label class="form-check-label" for="r_<?= $val ?>"><?= $label ?></label>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Additional Details <small class="text-muted fw-normal">(optional)</small></label>
            <textarea name="details" class="form-control" rows="4" maxlength="1000"
                      placeholder="Provide any additional context that may help our review team…"><?= sanitize($_POST['details'] ?? '') ?></textarea>
            <div class="form-text text-end" id="char-count">0 / 1000</div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-danger fw-semibold">
              <i class="bi bi-flag me-1"></i> Submit Report
            </button>
          </div>
        </form>
      </div>

      <p class="text-muted small text-center mt-3">
        Abuse of the reporting system may result in account suspension.
        Reports are reviewed within 24–48 hours.
      </p>

    </div>
  </div>
</div>

<script>
const ta = document.querySelector('textarea[name="details"]');
const cc = document.getElementById('char-count');
if (ta && cc) {
    const update = () => cc.textContent = ta.value.length + ' / 1000';
    ta.addEventListener('input', update);
    update();
}
</script>

<?php include 'includes/footer.php'; ?>
