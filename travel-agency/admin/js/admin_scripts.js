// admin/js/admin_scripts.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin specific JavaScript loaded.');

    // Example: Mobile navigation toggle for admin header (if needed)
    const navbarToggler = document.querySelector('.admin-page .navbar-toggler');
    const adminNavbarNav = document.querySelector('.admin-page .navbar-nav');

    if (navbarToggler && adminNavbarNav) {
        navbarToggler.addEventListener('click', () => {
            adminNavbarNav.classList.toggle('active'); // You'd need CSS for .navbar-nav.active
        });
    }

    // You can add more admin-specific interactions here
    // For example, handling clicks on dashboard links if they were to make AJAX calls,
    // or initializing charts if you add them later.
});