<?php
$pageTitle = 'Home – South Africa\'s Township Marketplace';
require_once 'includes/functions.php';

// Featured listings
$featured = getListings(['sort' => 'popular'], 8, 0);
// Latest listings
$latest = getListings(['sort' => 'newest'], 8, 0);
$categories = getCategories();

// Stats
$pdo = getDB();
$totalListings = $pdo->query('SELECT COUNT(*) FROM listings WHERE status="active"')->fetchColumn();
$totalUsers    = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalOrders   = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
?>
<?php include 'includes/header.php'; ?>

<!-- HERO -->
<section class="hero position-relative">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill">🇿🇦 Proudly South African</span>
        <h1 class="mb-3">Buy &amp; Sell in Your <span class="text-warning">Community</span></h1>
        <p class="lead mb-4">Ubuntu Market connects buyers and sellers across South African townships. Verified sellers, secure payments, local logistics.</p>
        <!-- Hero Search -->
        <form class="hero-search d-flex gap-2 flex-wrap" action="search.php" method="GET">
          <div class="input-group flex-grow-1" style="max-width:580px">
            <input type="text" class="form-control form-control-lg" name="q" placeholder="What are you looking for?" autofocus>
            <select class="form-select" name="cat" style="max-width:160px">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-warning btn-lg" type="submit"><i class="bi bi-search me-1"></i>Search</button>
          </div>
        </form>
        <div class="mt-3 d-flex gap-3 flex-wrap">
          <a href="listings.php" class="btn btn-outline-light">Browse All Listings</a>
          <a href="register.php" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i>Start Selling</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-flex justify-content-center mt-4 mt-lg-0">
        <div class="text-center">
          <i class="bi bi-shop-window" style="font-size:9rem;color:rgba(245,166,35,0.3)"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAR -->
<section class="stats-bar py-3">
  <div class="container">
    <div class="row text-center">
      <div class="col-4 stat-item">
        <div class="number"><?= number_format((int)$totalListings) ?>+</div>
        <div class="label">Active Listings</div>
      </div>
      <div class="col-4 stat-item">
        <div class="number"><?= number_format((int)$totalUsers) ?>+</div>
        <div class="label">Registered Traders</div>
      </div>
      <div class="col-4 stat-item">
        <div class="number"><?= number_format((int)$totalOrders) ?>+</div>
        <div class="label">Successful Trades</div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="py-5">
  <div class="container">
    <h2 class="section-title mb-4">Shop by Category</h2>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="listings.php?cat=<?= $cat['id'] ?>" class="category-card">
          <div class="category-icon"><i class="bi <?= $cat['icon'] ?>"></i></div>
          <span><?= sanitize($cat['name']) ?></span>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURED LISTINGS -->
<?php if ($featured): ?>
<section class="py-4 bg-white">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Popular Listings</h2>
      <a href="listings.php?sort=popular" class="btn btn-outline-warning btn-sm">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($featured as $item): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <?php include 'includes/listing_card.php'; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- HOW IT WORKS -->
<section class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="section-title mx-auto mb-5" style="width:fit-content">How Ubuntu Market Works</h2>
    <div class="row g-4">
      <div class="col-md-3">
        <div class="rounded-circle bg-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px">
          <i class="bi bi-person-plus-fill fs-2 text-white"></i>
        </div>
        <h6 class="fw-bold">1. Register</h6>
        <p class="small text-muted">Create your free account in minutes. Verified sellers build trust with buyers.</p>
      </div>
      <div class="col-md-3">
        <div class="rounded-circle bg-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px">
          <i class="bi bi-camera-fill fs-2 text-white"></i>
        </div>
        <h6 class="fw-bold">2. Post a Listing</h6>
        <p class="small text-muted">Add photos, set your price and description. Your item is live immediately.</p>
      </div>
      <div class="col-md-3">
        <div class="rounded-circle bg-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px">
          <i class="bi bi-chat-dots-fill fs-2 text-white"></i>
        </div>
        <h6 class="fw-bold">3. Connect &amp; Chat</h6>
        <p class="small text-muted">Buyers message sellers directly. Negotiate securely inside the platform.</p>
      </div>
      <div class="col-md-3">
        <div class="rounded-circle bg-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px">
          <i class="bi bi-shield-check-fill fs-2 text-white"></i>
        </div>
        <h6 class="fw-bold">4. Secure Payment</h6>
        <p class="small text-muted">Pay via EFT, card or mobile money. Funds released after delivery confirmation.</p>
      </div>
    </div>
  </div>
</section>

<!-- LATEST LISTINGS -->
<?php if ($latest): ?>
<section class="py-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Newest Listings</h2>
      <a href="listings.php" class="btn btn-outline-warning btn-sm">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($latest as $item): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <?php include 'includes/listing_card.php'; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA BANNER -->
<section class="py-5" style="background:linear-gradient(135deg,#1a1a2e,#0f3460)">
  <div class="container text-center text-white">
    <h2 class="fw-bold mb-3">Ready to Start Selling?</h2>
    <p class="lead opacity-75 mb-4">Join thousands of South African traders already on Ubuntu Market.</p>
    <a href="register.php" class="btn btn-warning btn-lg px-5 me-2"><i class="bi bi-rocket-takeoff-fill me-2"></i>Get Started Free</a>
    <a href="listings.php" class="btn btn-outline-light btn-lg px-5">Browse Listings</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
