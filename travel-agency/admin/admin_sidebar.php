<?php
// admin_sidebar.php
// $current_admin_page is expected to be set by the including PHP file (e.g., dashboard.php)
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h1 class="sidebar-logo">TravelPanel</h1>
        <!-- Or use an image: <img src="<?php echo $basePath; ?>public/images/admin-logo.png" alt="Logo" class="sidebar-logo-img"> -->
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_admin_page == 'home') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span class="nav-icon">🏠</span> <!-- Replace with actual icon if using a library -->
                    <span class="nav-text">Home</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'hotels') ? 'active' : ''; ?>">
                <a href="manage_hotels.php"> <!-- Replace # with actual page links -->
                    <span class="nav-icon">🏨</span>
                    <span class="nav-text">Manage Hotels</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'airlines') ? 'active' : ''; ?>">
                <a href="manage_airlines.php">
                    <span class="nav-icon">✈️</span>
                    <span class="nav-text">Manage Airlines</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'packages') ? 'active' : ''; ?>">
                <a href="manage_trip_package.php">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Manage Trip Package</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'agreements') ? 'active' : ''; ?>">
                <a href="manage_agreements.php">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">Manage Agreements</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'reservations') ? 'active' : ''; ?>">
                <a href="#">
                    <span class="nav-icon">📅</span>
                    <span class="nav-text">Manage Reservations</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'clients') ? 'active' : ''; ?>">
                <a href="#">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Clients</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="#" class="sidebar-logout-link">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Logout</span>
        </a>
        <!-- You can add a "Support" or "Help" like image here if needed -->
        <!-- <div class="sidebar-support-graphic">
            <img src="<?php echo $basePath; ?>public/images/admin-support.png" alt="24/7 Support">
        </div> -->
    </div>
</aside>