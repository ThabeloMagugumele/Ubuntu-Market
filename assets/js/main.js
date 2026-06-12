/**
 * Ubuntu Market - Main JavaScript
 * Author: Thabelo Magugumele (EDUV4949239)
 */

'use strict';

// ---- DOM Ready ---------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    initTooltips();
    initWishlistButtons();
    initImagePreview();
    initCartQuantity();
    initConfirmDialogs();
    initAutoHideAlerts();
    initSearchSuggestions();
});

// ---- Bootstrap Tooltips ------------------------------------
function initTooltips() {
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));
}

// ---- Wishlist Toggle (AJAX) --------------------------------
function initWishlistButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const listingId = this.dataset.listingId;
            const icon = this.querySelector('i');

            fetch(`/ubuntu_market/ajax/toggle_wishlist.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `listing_id=${listingId}&csrf_token=${document.querySelector('meta[name="csrf"]')?.content || ''}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'added') {
                    icon.classList.replace('bi-heart', 'bi-heart-fill');
                    icon.style.color = '#e74c3c';
                    showToast('Added to wishlist', 'success');
                } else if (data.status === 'removed') {
                    icon.classList.replace('bi-heart-fill', 'bi-heart');
                    icon.style.color = '';
                    showToast('Removed from wishlist', 'info');
                } else if (data.status === 'login_required') {
                    window.location.href = '/ubuntu_market/login.php';
                }
            })
            .catch(() => showToast('Something went wrong', 'danger'));
        });
    });
}

// ---- Image Preview for Upload Inputs -----------------------
function initImagePreview() {
    document.querySelectorAll('.img-upload-input').forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (!preview) return;
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        });
    });
}

// ---- Cart Quantity Controls --------------------------------
function initCartQuantity() {
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.qty-group').querySelector('.qty-input');
            let val = parseInt(input.value) || 1;
            const min = parseInt(input.min) || 1;
            const max = parseInt(input.max) || 99;
            if (this.dataset.action === 'plus' && val < max) val++;
            if (this.dataset.action === 'minus' && val > min) val--;
            input.value = val;
            // Trigger change for cart update
            input.dispatchEvent(new Event('change'));
        });
    });

    // Auto-submit cart form on quantity change
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function () {
            const form = this.closest('form');
            if (form && form.dataset.autosubmit) form.submit();
        });
    });
}

// ---- Confirm Dialogs (delete, cancel etc) ------------------
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });
}

// ---- Auto-hide Bootstrap Alerts ----------------------------
function initAutoHideAlerts() {
    document.querySelectorAll('.alert-auto-hide').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });
}

// ---- Search Suggestions (simple inline) --------------------
function initSearchSuggestions() {
    const searchInput = document.querySelector('.hero-search input[name="q"]');
    if (!searchInput) return;
    let timer;
    searchInput.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) {
            closeSuggestions();
            return;
        }
        timer = setTimeout(() => fetchSuggestions(q, this), 300);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.search-suggestions-wrap')) closeSuggestions();
    });
}

function fetchSuggestions(q, input) {
    fetch(`/ubuntu_market/ajax/search_suggestions.php?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => renderSuggestions(data, input))
        .catch(() => {});
}

function renderSuggestions(items, input) {
    closeSuggestions();
    if (!items.length) return;
    const wrap = document.createElement('div');
    wrap.className = 'search-suggestions-wrap position-absolute bg-white shadow rounded-2 mt-1 w-100';
    wrap.style.cssText = 'z-index:9999;max-height:260px;overflow-y:auto;';
    items.forEach(item => {
        const a = document.createElement('a');
        a.href = `/ubuntu_market/listing.php?id=${item.id}`;
        a.className = 'd-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-dark border-bottom';
        a.innerHTML = `<img src="/ubuntu_market/${item.image_main}" width="40" height="40" style="object-fit:cover;border-radius:6px">
                       <div><div class="fw-semibold small">${escapeHtml(item.title)}</div>
                       <div class="text-muted" style="font-size:.75rem">R ${parseFloat(item.price).toFixed(2)}</div></div>`;
        wrap.appendChild(a);
    });
    const container = input.closest('.input-group') || input.parentElement;
    container.style.position = 'relative';
    container.appendChild(wrap);
}

function closeSuggestions() {
    document.querySelectorAll('.search-suggestions-wrap').forEach(el => el.remove());
}

// ---- Toast Notifications -----------------------------------
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = 11000;
        document.body.appendChild(container);
    }
    const id = 'toast-' + Date.now();
    const iconMap = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
          <div class="d-flex">
            <div class="toast-body"><i class="bi ${iconMap[type] || 'bi-bell'} me-2"></i>${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>
        </div>`);
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// ---- Utility: Escape HTML ----------------------------------
function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// ---- Price Range Slider ------------------------------------
const minInput = document.getElementById('min_price');
const maxInput = document.getElementById('max_price');
if (minInput && maxInput) {
    [minInput, maxInput].forEach(inp => {
        inp.addEventListener('change', function () {
            const min = parseFloat(minInput.value) || 0;
            const max = parseFloat(maxInput.value) || Infinity;
            if (min > max) maxInput.value = min;
        });
    });
}

// ---- Admin: Sidebar Toggle (mobile) ------------------------
const adminToggle = document.getElementById('adminSidebarToggle');
const adminSidebar = document.querySelector('.admin-sidebar');
if (adminToggle && adminSidebar) {
    adminToggle.addEventListener('click', () => adminSidebar.classList.toggle('d-none'));
}
