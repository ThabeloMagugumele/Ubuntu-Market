<?php
$pageTitle = 'Search Results';
require_once 'includes/functions.php';

$q       = sanitize($_GET['q'] ?? '');
$catId   = (int)($_GET['cat'] ?? 0);
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$filters = [
    'search'      => $q,
    'category_id' => $catId,
    'sort'        => sanitize($_GET['sort'] ?? 'newest'),
];

$items = $q || $catId ? getListings($filters, $perPage, $offset) : [];
$total = $q || $catId ? countListings($filters) : 0;

if (!$q && !$catId) {
    header('Location: listings.php');
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h4 class="fw-bold mb-1">
    Search results for: <span class="text-warning">"<?= sanitize($q) ?>"</span>
  </h4>
  <p class="text-muted mb-4"><?= number_format($total) ?> result<?= $total != 1 ? 's' : '' ?> found</p>

  <?php if (empty($items)): ?>
  <div class="text-center py-5">
    <i class="bi bi-search" style="font-size:4rem;color:#ddd"></i>
    <h5 class="mt-3 text-muted">No results found for "<?= sanitize($q) ?>"</h5>
    <p class="text-muted">Try different keywords or browse all categories.</p>
    <a href="listings.php" class="btn btn-warning mt-2">Browse All Listings</a>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($items as $item): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <?php include 'includes/listing_card.php'; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-4">
    <?= paginate($total, $perPage, $page, '?q=' . urlencode($q) . '&cat=' . $catId . '&sort=' . $filters['sort'] . '&page={page}') ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
