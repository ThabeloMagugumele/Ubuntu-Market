<?php
require_once __DIR__ . '/../includes/functions.php';
$categories = getCategories();
$cartCount   = isLoggedIn() ? getCartCount((int)$_SESSION['user_id']) : 0;
$unreadCount = isLoggedIn() ? getUnreadCount((int)$_SESSION['user_id']) : 0;
$pageTitle   = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Ubuntu Market – South Africa's trusted C2C marketplace for townships and informal traders">
<title><?= htmlspecialchars($pageTitle) ?> | <?= SITE_NAME ?></title>
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Custom CSS -->
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar bg-dark text-white py-1 d-none d-md-block">
  <div class="container d-flex justify-content-between align-items-center">
    <small><i class="bi bi-geo-alt-fill me-1"></i>South Africa's Township Marketplace</small>
    <small>
      <i class="bi bi-telephone-fill me-1"></i>0800 UBUNTU &nbsp;|&nbsp;
      <i class="bi bi-envelope-fill me-1"></i>support@ubuntumarket.co.za
    </small>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid px-3 px-lg-4">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2 me-3 flex-shrink-0" href="<?= SITE_URL ?>/index.php">
      <span class="brand-icon"><i class="bi bi-shop-window"></i></span>
      <span class="brand-name">Ubuntu<span class="text-warning">Market</span></span>
    </a>

    <!-- Mobile: cart + toggler -->
    <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
      <?php if (isLoggedIn()): ?>
      <a href="<?= SITE_URL ?>/cart.php" class="btn btn-outline-warning btn-sm position-relative">
        <i class="bi bi-cart3"></i>
        <?php if ($cartCount > 0): ?><span class="badge bg-danger position-absolute top-0 start-100 translate-middle"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <?php endif; ?>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="mainNav">

      <!-- Search Bar -->
      <form class="d-flex flex-grow-1 mx-lg-3 my-2 my-lg-0" action="<?= SITE_URL ?>/search.php" method="GET">
        <div class="input-group">
          <input type="text" class="form-control" name="q"
                 placeholder="Search listings..."
                 value="<?= sanitize($_GET['q'] ?? '') ?>">
          <select class="form-select flex-shrink-0" name="cat" style="max-width:140px">
            <option value="">All Cats</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (($_GET['cat'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
              <?= sanitize($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-warning px-3" type="submit"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <!-- Right-side nav -->
      <ul class="navbar-nav align-items-lg-center gap-2 flex-shrink-0 ms-lg-2 mt-2 mt-lg-0">

        <!-- Browse dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-medium" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-grid me-1"></i>Browse
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/listings.php">All Listings</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php foreach ($categories as $cat): ?>
            <li>
              <a class="dropdown-item" href="<?= SITE_URL ?>/listings.php?cat=<?= $cat['id'] ?>">
                <i class="bi <?= sanitize($cat['icon']) ?> me-2 text-warning"></i><?= sanitize($cat['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <?php if (isLoggedIn()): ?>
          <!-- Cart -->
          <li class="nav-item">
            <a class="btn btn-outline-warning position-relative" href="<?= SITE_URL ?>/cart.php">
              <i class="bi bi-cart3 me-1"></i>Cart
              <?php if ($cartCount > 0): ?>
              <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <!-- Messages -->
          <li class="nav-item">
            <a class="btn btn-outline-secondary position-relative" href="<?= SITE_URL ?>/messages.php" title="Messages">
              <i class="bi bi-chat-dots"></i>
              <?php if ($unreadCount > 0): ?>
              <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"><?= $unreadCount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <!-- Sell -->
          <li class="nav-item">
            <a class="btn btn-warning fw-semibold" href="<?= SITE_URL ?>/post_listing.php">
              <i class="bi bi-plus-circle me-1"></i>Sell
            </a>
          </li>
          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-0" href="#" data-bs-toggle="dropdown">
              <img src="<?= SITE_URL ?>/<?= sanitize($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>"
                   class="rounded-circle border" width="34" height="34" alt="avatar" style="object-fit:cover"
                   onerror="this.src='<?= SITE_URL ?>/assets/images/default_avatar.png'">
              <span class="d-none d-xl-inline"><?= sanitize($_SESSION['user_name'] ?? 'Account') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
              <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>/my_listings.php"><i class="bi bi-grid me-2"></i>My Listings</a></li>
              <li><a class="dropdown-item" href="<?= SITE_URL ?>/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
              <?php if (in_array($_SESSION['user_role'] ?? '', ['admin','superadmin'])): ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/admin/index.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-outline-primary" href="<?= SITE_URL ?>/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-warning fw-semibold" href="<?= SITE_URL ?>/register.php">Register</a></li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>

<!-- Flash Message -->
<div class="container mt-2"><?= renderFlash() ?></div>
