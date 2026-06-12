<?php
$pageTitle = 'Browse Listings';
require_once 'includes/functions.php';

$categories = getCategories();
$perPage    = 12;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

$filters = [
    'search'         => sanitize($_GET['q'] ?? ''),
    'category_id'    => (int)($_GET['cat'] ?? 0),
    'min_price'      => (float)($_GET['min'] ?? 0),
    'max_price'      => (float)($_GET['max'] ?? 0),
    'condition_type' => sanitize($_GET['cond'] ?? ''),
    'sort'           => sanitize($_GET['sort'] ?? 'newest'),
];

$items = getListings($filters, $perPage, $offset);
$total = countListings($filters);

$selectedCat = $filters['category_id'] > 0
    ? (array_values(array_filter($categories, fn($c) => $c['id'] == $filters['category_id']))[0] ?? null)
    : null;

$urlBase = '?' . http_build_query(array_merge($_GET, ['page' => '{page}']));
?>
<?php include 'includes/header.php'; ?>

<!-- BREADCRUMB -->
<div class="bg-white border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/index.php">Home</a></li>
        <li class="breadcrumb-item active">Browse Listings</li>
        <?php if ($selectedCat): ?><li class="breadcrumb-item active"><?= sanitize($selectedCat['name']) ?></li><?php endif; ?>
      </ol>
    </nav>
  </div>
</div>

<div class="container py-4">
  <div class="row g-4">

    <!-- SIDEBAR FILTERS -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm rounded-14 p-3 sticky-top" style="top:80px">
        <h6 class="fw-bold mb-3"><i class="bi bi-funnel-fill me-2 text-warning"></i>Filters</h6>
        <form method="GET" id="filterForm">
          <!-- Category -->
          <div class="mb-3">
            <label class="form-label small fw-semibold">Category</label>
            <select name="cat" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                <?= sanitize($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Price Range -->
          <div class="mb-3">
            <label class="form-label small fw-semibold">Price Range (R)</label>
            <div class="d-flex gap-2">
              <input type="number" name="min" class="form-control form-control-sm" placeholder="Min" min="0" value="<?= $filters['min_price'] ?: '' ?>" id="min_price">
              <input type="number" name="max" class="form-control form-control-sm" placeholder="Max" min="0" value="<?= $filters['max_price'] ?: '' ?>" id="max_price">
            </div>
          </div>
          <!-- Condition -->
          <div class="mb-3">
            <label class="form-label small fw-semibold">Condition</label>
            <select name="cond" class="form-select form-select-sm">
              <option value="">Any Condition</option>
              <option value="new" <?= $filters['condition_type'] === 'new' ? 'selected' : '' ?>>New</option>
              <option value="used_like_new" <?= $filters['condition_type'] === 'used_like_new' ? 'selected' : '' ?>>Like New</option>
              <option value="used_good" <?= $filters['condition_type'] === 'used_good' ? 'selected' : '' ?>>Good</option>
              <option value="used_fair" <?= $filters['condition_type'] === 'used_fair' ? 'selected' : '' ?>>Fair</option>
            </select>
          </div>
          <!-- Search -->
          <?php if (!empty($filters['search'])): ?>
          <input type="hidden" name="q" value="<?= sanitize($filters['search']) ?>">
          <?php endif; ?>
          <input type="hidden" name="sort" value="<?= sanitize($filters['sort']) ?>">
          <button type="submit" class="btn btn-warning btn-sm w-100 mb-2">Apply Filters</button>
          <a href="listings.php" class="btn btn-outline-secondary btn-sm w-100">Clear All</a>
        </form>
      </div>
    </div>

    <!-- LISTINGS GRID -->
    <div class="col-lg-9">
      <!-- Toolbar -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h5 class="mb-0 fw-bold">
            <?php if (!empty($filters['search'])): ?>
              Results for "<?= sanitize($filters['search']) ?>"
            <?php elseif ($selectedCat): ?>
              <?= sanitize($selectedCat['name']) ?>
            <?php else: ?>
              All Listings
            <?php endif; ?>
          </h5>
          <small class="text-muted"><?= number_format($total) ?> listing<?= $total != 1 ? 's' : '' ?> found</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <label class="small text-muted me-1">Sort:</label>
          <select class="form-select form-select-sm w-auto" onchange="window.location='listings.php?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(location.search)), sort: this.value})">
            <option value="newest" <?= $filters['sort']==='newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $filters['sort']==='price_asc' ? 'selected' : '' ?>>Price: Low–High</option>
            <option value="price_desc" <?= $filters['sort']==='price_desc' ? 'selected' : '' ?>>Price: High–Low</option>
            <option value="popular" <?= $filters['sort']==='popular' ? 'selected' : '' ?>>Most Popular</option>
          </select>
        </div>
      </div>

      <!-- Grid -->
      <?php if (empty($items)): ?>
      <div class="text-center py-5">
        <i class="bi bi-search" style="font-size:4rem;color:#ddd"></i>
        <h5 class="mt-3 text-muted">No listings found</h5>
        <p class="text-muted">Try adjusting your filters or search terms.</p>
        <a href="listings.php" class="btn btn-warning">Browse All</a>
      </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($items as $item): ?>
        <div class="col-6 col-md-4">
          <?php include 'includes/listing_card.php'; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <div class="mt-4">
        <?= paginate($total, $perPage, $page, '?' . http_build_query(array_merge(array_filter($_GET), ['page' => '{page}']))) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
