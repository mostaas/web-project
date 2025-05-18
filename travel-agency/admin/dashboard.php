<?php
$current_admin_page = 'home'; // For active sidebar link
$page_title = "Admin Dashboard";
include_once '../includes/db.php'; // Include db connection
include_once '../includes/header.php';
include_once 'admin_sidebar.php';   // Include the new sidebar

// Placeholder Data (Replace with dynamic data from DB later)
$stats = [
    'total_bookings' => 67343,
    'total_bookings_change' => '+2.5%',
    'total_bookings_period' => 'vs Last Month',
    'new_clients' => 2343,
    'new_clients_change' => '+1.8%',
    'new_clients_period' => 'vs Last Month',
    'pending_reservations' => 35343,
    'pending_reservations_change' => '-0.5%',
    'pending_reservations_period' => 'vs Last Month'
];

$overviewItems = [
    ['name' => 'Recent Booking #TPA1023', 'value' => 'Confirmed', 'status_class' => 'confirmed'],
    ['name' => 'New Hotel "Sunset Inn"', 'value' => 'Added', 'status_class' => 'added'],
    ['name' => 'Pending Reservation #TPP987', 'value' => 'Awaiting Payment', 'status_class' => 'pending'],
    ['name' => 'Client "J. Doe" Registered', 'value' => 'New', 'status_class' => 'new-client'],
];

$activityItems = [
    ['color' => 'green', 'text' => 'Airline "FlyHigh" partnership renewed successfully.'],
    ['color' => 'orange', 'text' => 'Package "Winter Wonderland" requires attention for pricing update.'],
    ['color' => 'red', 'text' => 'System alert: Payment gateway error on reservation #TPX552.'],
    ['color' => 'green', 'text' => 'Feedback received for Trip #TPE089 - 5 Stars.'],
];

$totalBookingGoalPercentage = 70; // Example for the circular progress
?>

            <!-- This admin-main-content is now the primary scrollable area beside the fixed sidebar -->
            <main class="admin-main-content">
                <!-- Top Bar inside main content -->
                <header class="main-content-header">
                    <h2 class="content-title">Dashboard</h2>
                    <div class="user-profile-section">
                        <span class="notification-bell">🔔<span class="notification-count">1</span></span>
                        <img src="<?php echo $basePath; ?>public/images/admin-avatar-placeholder.png" alt="Admin Avatar" class="user-avatar">
                        <span class="user-name">Morris Adrian</span>
                    </div>
                </header>

                <!-- Stats Cards -->
                <section class="stats-card-grid">
                    <div class="stat-card">
                        <div class="card-header">
                            <h4>Total Bookings</h4>
                            <span class="card-icon">📈</span> <!-- Placeholder icon -->
                        </div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['total_bookings']); ?></p>
                            <p class="stat-change <?php echo (strpos($stats['total_bookings_change'], '+') === 0) ? 'positive' : 'negative'; ?>">
                                <?php echo $stats['total_bookings_change']; ?>
                                <span><?php echo $stats['total_bookings_period']; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header">
                            <h4>New Clients</h4>
                            <span class="card-icon">👥</span> <!-- Placeholder icon -->
                        </div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['new_clients']); ?></p>
                            <p class="stat-change <?php echo (strpos($stats['new_clients_change'], '+') === 0) ? 'positive' : 'negative'; ?>">
                                <?php echo $stats['new_clients_change']; ?>
                                <span><?php echo $stats['new_clients_period']; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header">
                            <h4>Pending Reservations</h4>
                            <span class="card-icon">⏳</span> <!-- Placeholder icon -->
                        </div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['pending_reservations']); ?></p>
                            <p class="stat-change <?php echo (strpos($stats['pending_reservations_change'], '+') === 0) ? 'positive' : ((strpos($stats['pending_reservations_change'], '-') === 0) ? 'negative' : ''); ?>">
                                <?php echo $stats['pending_reservations_change']; ?>
                                <span><?php echo $stats['pending_reservations_period']; ?></span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Main Dashboard Content Row (Overview, Total Sale, Activity) -->
                <section class="dashboard-content-row">
                    <!-- Overview Section -->
                    <div class="dashboard-widget overview-widget">
                        <div class="widget-header">
                            <h3>Overview</h3>
                            <!-- <a href="#" class="view-all-link">View All</a> Not in image -->
                        </div>
                        <ul class="overview-list">
                            <?php foreach ($overviewItems as $item): ?>
                            <li>
                                <span class="overview-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="overview-item-value status-<?php echo htmlspecialchars($item['status_class']); ?>"><?php echo htmlspecialchars($item['value']); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Total Sale/Progress Section -->
                    <div class="dashboard-widget total-sale-widget">
                         <div class="widget-header">
                            <h3>Key Metric Progress</h3> <!-- Renamed from "Total Sale" -->
                            <a href="#" class="view-all-link">View Detail</a>
                        </div>
                        <div class="progress-circle-container">
                            <svg class="progress-ring" width="160" height="160">
                                 <circle class="progress-ring-bg" stroke-width="12" fill="transparent" r="70" cx="80" cy="80"/>
                                 <circle class="progress-ring-fg" stroke-width="12" fill="transparent" r="70" cx="80" cy="80"
                                         style="stroke-dasharray: <?php echo (2 * pi() * 70); ?>; stroke-dashoffset: <?php echo (2 * pi() * 70) * (1 - $totalBookingGoalPercentage / 100); ?>;" />
                            </svg>
                            <span class="progress-percentage"><?php echo $totalBookingGoalPercentage; ?>%</span>
                        </div>
                        <p class="progress-subtext">Target Reached This Month</p>
                    </div>

                    <!-- Activity Section -->
                    <div class="dashboard-widget activity-widget">
                        <div class="widget-header">
                            <h3>Activity</h3>
                            <a href="#" class="view-all-link">View All</a>
                        </div>
                        <ul class="activity-list">
                            <?php foreach ($activityItems as $item): ?>
                            <li>
                                <span class="activity-dot <?php echo htmlspecialchars($item['color']); ?>"></span>
                                <p class="activity-text"><?php echo htmlspecialchars($item['text']); ?></p>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

            </main> <!-- .admin-main-content -->
<?php
include_once '../includes/footer.php'; // Closes .admin-main-wrapper and .admin-layout-container
?>