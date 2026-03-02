document.addEventListener('DOMContentLoaded', function () {

    // Theme Toggle
    const themeToggle = document.getElementById('wd-theme-toggle');
    if (themeToggle) {
        const savedTheme = localStorage.getItem('hashguardian-theme') || 'dark';
        applyTheme(savedTheme);

        themeToggle.addEventListener('click', function () {
            const current = document.body.classList.contains('wd-light') ? 'light' : 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem('hashguardian-theme', next);
        });
    }

    function applyTheme(theme) {
        if (theme === 'light') {
            document.body.classList.add('wd-light');
        } else {
            document.body.classList.remove('wd-light');
        }
        const icon = document.getElementById('wd-theme-icon');
        if (icon) {
            icon.innerHTML = theme === 'dark'
                ? '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>'
                : '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
        }
    }

    // Date Range Filter
    const dateForm = document.getElementById('wd-date-filter');
    if (dateForm) {
        dateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const since = document.getElementById('wd-since').value;
            const until = document.getElementById('wd-until').value;
            const params = new URLSearchParams(window.location.search);

            if (since) params.set('since', since);
            else params.delete('since');

            if (until) params.set('until', until);
            else params.delete('until');

            params.delete('before');
            window.location.search = params.toString();
        });

        const clearBtn = document.getElementById('wd-date-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                const params = new URLSearchParams(window.location.search);
                params.delete('since');
                params.delete('until');
                params.delete('before');
                window.location.search = params.toString();
            });
        }
    }

    // Live Search (client-side filter on table rows)
    const searchInput = document.getElementById('wd-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const table = document.querySelector('.wd-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Auto-refresh toggle
    const refreshToggle = document.getElementById('wd-auto-refresh');
    if (refreshToggle) {
        let refreshInterval = null;

        refreshToggle.addEventListener('change', function () {
            if (this.checked) {
                refreshInterval = setInterval(function () {
                    window.location.reload();
                }, 15000);
            } else {
                clearInterval(refreshInterval);
            }
        });
    }

    // Relative time updater
    function updateRelativeTimes() {
        document.querySelectorAll('[data-timestamp]').forEach(function (el) {
            const ts = parseInt(el.getAttribute('data-timestamp'), 10);
            const diff = Math.floor((Date.now() / 1000) - ts);

            if (diff < 60) el.textContent = diff + 's ago';
            else if (diff < 3600) el.textContent = Math.floor(diff / 60) + 'm ago';
            else if (diff < 86400) el.textContent = Math.floor(diff / 3600) + 'h ago';
            else el.textContent = Math.floor(diff / 86400) + 'd ago';
        });
    }

    setInterval(updateRelativeTimes, 10000);
});
