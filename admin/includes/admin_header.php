<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();
$adminUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Ubuntu Market Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="d-flex">

<!-- SIDEBAR -->
<div class="admin-sidebar d-none d-lg-flex flex-column" id="adminSidebar">
  <div class="sidebar-brand d-flex align-items-center gap-2">
    <i class="bi bi-shop-window text-warning fs-5"></i>
    <span>Ubuntu<span class="text-warning">Admin</span></span>
  </div>

  <nav class="flex-grow-1 py-2">
    <div class="px-3 py-2 text-uppercase text-secondary" style="font-size:.68rem;letter-spacing:1px">Main</div>
    <a href="<?= SITE_URL ?>/admin/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?= SITE_URL ?>/admin/users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i> Users
    </a>
    <a href="<?= SITE_URL ?>/admin/listings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'listings.php' ? 'active' : '' ?>">
      <i class="bi bi-grid-fill"></i> Listings
    </a>
    <a href="<?= SITE_URL ?>/admin/orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
      <i class="bi bi-bag-check-fill"></i> Orders
    </a>
    <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
      <i class="bi bi-tags-fill"></i> Categories
    </a>
    <div class="px-3 py-2 mt-2 text-uppercase text-secondary" style="font-size:.68rem;letter-spacing:1px">Reports</div>
    <a href="<?= SITE_URL ?>/admin/reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
      <i class="bi bi-flag-fill"></i> User Reports
    </a>
    <div class="px-3 py-2 mt-2 text-uppercase text-secondary" style="font-size:.68rem;letter-spacing:1px">System</div>
    <a href="<?= SITE_URL ?>/index.php" class="nav-link" target="_blank">
      <i class="bi bi-box-arrow-up-right"></i> View Site
    </a>
    <a href="<?= SITE_URL ?>/logout.php" class="nav-link text-danger">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </nav>

  <div class="p-3 border-top border-secondary">
    <div class="d-flex align-items-center gap-2">
      <img src="<?= SITE_URL ?>/<?= sanitize($adminUser['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
           width="34" height="34" class="rounded-circle object-fit-cover" alt="">
      <div>
        <div class="small fw-semibold text-white"><?= sanitize($adminUser['first_name']) ?></div>
        <div style="font-size:.7rem;color:rgba(255,255,255,0.5)"><?= ucfirst($adminUser['role']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="admin-content flex-grow-1">

  <!-- Top Bar -->
  <div class="admin-topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <button id="adminSidebarToggle" class="btn btn-sm btn-outline-secondary d-lg-none"><i class="bi bi-list fs-5"></i></button>
      <h6 class="mb-0 fw-bold"><?= htmlspecialchars($pageTitle) ?></h6>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-warning text-dark">Admin Panel</span>
      <a href="<?= SITE_URL ?>/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
  </div>

  <!-- Flash -->
  <div><?= renderFlash() ?></div>
