// footer.js - NSBM NewsDesk footer behaviour
document.addEventListener('DOMContentLoaded', function () {
    // Keep copyright year current
    const yearEl = document.getElementById('footerYear');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    // Confirm before logging out
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to log out?')) {
                e.preventDefault();
            }
        });
    }
});
