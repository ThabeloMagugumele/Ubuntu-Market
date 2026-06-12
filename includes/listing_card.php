<?php
<div class="listing-card">
  <div class="img-wrapper">
    <a href="<?= SITE_URL ?>/listing.php?id=<?= $item['id'] ?>">
      <img src="<?= SITE_URL ?>/<?= sanitize($item['image_main'] ?? 'assets/images/no_image.png') ?>"
           class="card-img-top"
           alt="<?= sanitize($item['title']) ?>"
           loading="lazy"
           onerror="this.src='<?= SITE_URL ?>/assets/images/no_image.png'">
    </a>
    <?php if (isLoggedIn()): ?>
    <button class="wishlist-btn" data-listing-id="<?= $item['id'] ?>"
            data-bs-toggle="tooltip" title="Add to Wishlist">
      <i class="bi bi-heart"></i>
    </button>
    <?php endif; ?>
    <div class="position-absolute top-0 start-0 m-2">
      <?= conditionLabel($item['condition_type']) ?>
    </div>
  </div>
  <div class="card-body">
    <div class="price mb-1"><?= formatPrice($item['price']) ?></div>
    <h6 class="card-title mb-1">
      <a href="<?= SITE_URL ?>/listing.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
        <?= sanitize($item['title']) ?>
      </a>
    </h6>
    <div class="seller-info d-flex align-items-center gap-1 mb-2">
      <i class="bi bi-person-circle"></i>
      <?= sanitize($item['first_name'] . ' ' . $item['last_name']) ?>
      <?php if (!empty($item['city'])): ?>
        <span class="ms-auto"><i class="bi bi-geo-alt"></i><?= sanitize($item['city']) ?></span>
      <?php endif; ?>
    </div>
    <div class="d-flex justify-content-between align-items-center">
      <small class="text-muted"><i class="bi bi-tag me-1"></i><?= sanitize($item['category_name'] ?? '') ?></small>
      <small class="text-muted"><?= timeAgo($item['created_at']) ?></small>
    </div>
  </div>
</div>
