/**
 * Phantom v3 – Theme Toggle (Dark / Light)
 */

(function () {
    const saved = localStorage.getItem('phantom-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);

    function updateButton(theme) {
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.textContent = theme === 'dark' ? '☀️' : '🌙';
            btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    // Wait until DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        updateButton(saved);

        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('phantom-theme', next);
                updateButton(next);
            });
        }
    });
})();
