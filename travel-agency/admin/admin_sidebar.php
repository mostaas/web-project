<?php
// admin_sidebar.php
// $current_admin_page is expected to be set by the including PHP file (e.g., dashboard.php)
// $basePath is available from header.php, but for fixed relative paths like this, it's often simpler.
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h1 class="sidebar-logo">TraVelino Panel</h1>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_admin_page == 'home') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Home</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'hotels') ? 'active' : ''; ?>">
                <a href="manage_hotels.php">
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
                <a href="manage_reservations.php">
                    <span class="nav-icon">📅</span>
                    <span class="nav-text">Manage Reservations</span>
                </a>
            </li>
            <li class="<?php echo ($current_admin_page == 'clients') ? 'active' : ''; ?>">
                <a href="manage_clients.php">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Clients</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <!-- ***** UPDATED LOGOUT LINK HERE ***** -->
        <a href="../account/logout.php" class="sidebar-logout-link">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Logout</span>
        </a>
        <!-- Optional support graphic -->
        <!-- <div class="sidebar-support-graphic">
            <img src="<?php // echo $basePath; ?>public/images/admin-support.png" alt="24/7 Support">
        </div> -->
    </div>
</aside>