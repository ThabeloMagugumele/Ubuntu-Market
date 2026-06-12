<?php require_once __DIR__ . '/../config/database.php'; ?>
<footer class="bg-dark text-white mt-5 pt-5 pb-3">
  <div class="container">
    <div class="row g-4">
      <!-- Brand -->
      <div class="col-lg-3 col-md-6">
        <h5 class="fw-bold mb-3"><i class="bi bi-shop-window me-2 text-warning"></i>UbuntuMarket</h5>
        <p class="text-secondary small">South Africa's trusted C2C marketplace empowering informal traders and township entrepreneurs.</p>
        <div class="d-flex gap-2 mt-3">
          <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-facebook"></i></a>
          <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-instagram"></i></a>
          <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <!-- Quick Links -->
      <div class="col-lg-2 col-md-6">
        <h6 class="fw-bold mb-3 text-warning">Quick Links</h6>
        <ul class="list-unstyled small text-secondary">
          <li class="mb-1"><a href="<?= SITE_URL ?>/index.php" class="text-secondary text-decoration-none">Home</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php" class="text-secondary text-decoration-none">Browse Listings</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/post_listing.php" class="text-secondary text-decoration-none">Sell an Item</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/register.php" class="text-secondary text-decoration-none">Register</a></li>
        </ul>
      </div>
      <!-- Categories -->
      <div class="col-lg-3 col-md-6">
        <h6 class="fw-bold mb-3 text-warning">Categories</h6>
        <ul class="list-unstyled small text-secondary">
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php?cat=1" class="text-secondary text-decoration-none">Electronics</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php?cat=2" class="text-secondary text-decoration-none">Clothing &amp; Shoes</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php?cat=3" class="text-secondary text-decoration-none">Home &amp; Garden</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php?cat=5" class="text-secondary text-decoration-none">Food &amp; Beverages</a></li>
          <li class="mb-1"><a href="<?= SITE_URL ?>/listings.php?cat=6" class="text-secondary text-decoration-none">Services</a></li>
        </ul>
      </div>
      <!-- Contact -->
      <div class="col-lg-4 col-md-6">
        <h6 class="fw-bold mb-3 text-warning">Contact &amp; Support</h6>
        <ul class="list-unstyled small text-secondary">
          <li class="mb-2"><i class="bi bi-telephone-fill me-2 text-warning"></i>0800 UBUNTU (082 688 6)</li>
          <li class="mb-2"><i class="bi bi-envelope-fill me-2 text-warning"></i>support@ubuntumarket.co.za</li>
          <li class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-warning"></i>Johannesburg, South Africa</li>
        </ul>
        <div class="mt-3">
          <img src="<?= SITE_URL ?>/assets/images/payment_icons.png" alt="Payment methods" class="img-fluid" style="max-height:30px" onerror="this.style.display='none'">
          <small class="text-secondary d-block mt-1">EFT | Mobile Money | Card | Cash on Delivery</small>
        </div>
      </div>
    </div>

    <hr class="border-secondary my-4">

    <div class="row align-items-center">
      <div class="col-md-6 text-secondary small">
        &copy; <?= date('Y') ?> Ubuntu Market. All rights reserved. | ITECA3-12 Web Development &amp; E-Commerce
      </div>
      <div class="col-md-6 text-md-end">
        <small class="text-secondary">
          <a href="#" class="text-secondary text-decoration-none me-3">Privacy Policy</a>
          <a href="#" class="text-secondary text-decoration-none me-3">Terms of Service</a>
          <a href="#" class="text-secondary text-decoration-none">Help Centre</a>
        </small>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
