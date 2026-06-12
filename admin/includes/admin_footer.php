</div><!-- end admin-content -->
</div><!-- end d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
// Admin sidebar mobile toggle
const adminToggle = document.getElementById('adminSidebarToggle');
const adminSidebar = document.getElementById('adminSidebar');
if (adminToggle && adminSidebar) {
    adminToggle.addEventListener('click', () => adminSidebar.classList.toggle('d-none'));
}
</script>
</body>
</html>
