/**
 * FleetFlow – Application JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {

    // ---- Sidebar Toggle (Mobile) ----
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function () {
            sidebar.classList.remove('show');
        });
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) &&
            toggleBtn && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });

    // ---- Delete Confirmation ----
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ---- Live Table Search ----
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const table = document.getElementById('dataTable');
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // ---- Auto-dismiss alerts after 5s ----
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});
