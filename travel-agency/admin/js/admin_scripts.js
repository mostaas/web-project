// admin/js/admin_scripts.js
document.addEventListener('DOMContentLoaded', function() {
    // This script would be for a hamburger toggle on mobile/tablet if the sidebar wasn't fixed
    // For the current fixed-sidebar-from-image design, this isn't strictly necessary
    // unless you want a true "toggle off screen" behavior for smaller viewports.

    // Example: If you add a <button id="mobileSidebarToggle">...</button> in header
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle'); // You'd need to add this button to HTML
    const adminSidebar = document.querySelector('.admin-sidebar');

    if (mobileSidebarToggle && adminSidebar) {
        mobileSidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('open'); // You'd need @media styles for .admin-sidebar.open
        });
    }

    console.log("Admin scripts loaded.");
});