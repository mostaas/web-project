<?php
$current_admin_page = 'home';
$page_title = "Admin Dashboard";
include_once '../includes/db.php';

// --- Fetch Dynamic Data for Dashboard ---
$stats = [
    'totalHotels' => 0,
    'totalAirlines' => 0,
    'activePackages' => 0,
    'pendingReservations' => 0,
    'newFeedbackCount' => 0,
    'totalClients' => 0,
];
$recentActivities = [];
$overviewItems = [];
$mostReservedHotels = [];

try {
    // Total Hotels
    $stmt = $pdo->query("SELECT COUNT(*) FROM Hotel");
    $stats['totalHotels'] = $stmt->fetchColumn();

    // Total Airlines
    $stmt = $pdo->query("SELECT COUNT(*) FROM Airline");
    $stats['totalAirlines'] = $stmt->fetchColumn();

    // Active Trip Packages
    $today = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TripPackage WHERE endDate >= ?");
    $stmt->execute([$today]);
    $stats['activePackages'] = $stmt->fetchColumn();

    // Pending Reservations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE status = ? OR status = ?");
    $stmt->execute(['Pending', 'Awaiting Payment']);
    $stats['pendingReservations'] = $stmt->fetchColumn();

    // New Feedback (last 7 days)
    $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Feedback WHERE submissionDate >= ?");
    $stmt->execute([$sevenDaysAgo]);
    $stats['newFeedbackCount'] = $stmt->fetchColumn();

    // Total Clients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE role = ?");
    $stmt->execute(['Client']);
    $stats['totalClients'] = $stmt->fetchColumn();

    // Recent Activity (same as before)
    $stmt_recent_res = $pdo->query("
        SELECT r.reservationId, r.status, u.username AS clientUsername, tp.name AS packageName, r.bookingDate
        FROM Reservation r JOIN User u ON r.clientId = u.userId JOIN TripPackage tp ON r.packageId = tp.packageId
        ORDER BY r.bookingDate DESC LIMIT 3
    ");
    while ($row = $stmt_recent_res->fetch()) {
        $recentActivities[] = ['icon' => '📅', 'text' => "New Reservation #{$row['reservationId']} for \"{$row['packageName']}\" by {$row['clientUsername']}. Status: {$row['status']}", 'time' => date("M d, H:i", strtotime($row['bookingDate'])), 'type' => 'reservation'];
    }
    $stmt_recent_feed = $pdo->query("
        SELECT f.feedbackId, f.hotelRating, f.airlineRating, u.username AS clientUsername, f.submissionDate, r.packageId, tp.name AS packageName
        FROM Feedback f JOIN User u ON f.clientId = u.userId JOIN Reservation r ON f.reservationId = r.reservationId JOIN TripPackage tp ON r.packageId = tp.packageId
        ORDER BY f.submissionDate DESC LIMIT 2
    ");
    while ($row = $stmt_recent_feed->fetch()) {
        $ratingText = '';
        if ($row['hotelRating']) $ratingText .= "Hotel: {$row['hotelRating']}/5 ";
        if ($row['airlineRating']) $ratingText .= "Airline: {$row['airlineRating']}/5";
        $recentActivities[] = ['icon' => '⭐', 'text' => "Feedback from {$row['clientUsername']} for package \"{$row['packageName']}\". " . ($ratingText ?: "Review submitted."), 'time' => date("M d, H:i", strtotime($row['submissionDate'])), 'type' => 'feedback'];
    }
    usort($recentActivities, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
    $recentActivities = array_slice($recentActivities, 0, 5);

    // Quick Overview Items (same as before)
    $fourteenDaysLater = date('Y-m-d', strtotime('+14 days'));
    $stmt_upcoming = $pdo->prepare("
        SELECT tp.name AS packageName, tp.startDate, COUNT(r.reservationId) as reservationCount
        FROM TripPackage tp LEFT JOIN Reservation r ON tp.packageId = r.packageId AND r.status = 'Confirmed'
        WHERE tp.startDate BETWEEN ? AND ? GROUP BY tp.packageId, tp.name, tp.startDate ORDER BY tp.startDate ASC LIMIT 3
    ");
    $stmt_upcoming->execute([$today, $fourteenDaysLater]);
    while ($row = $stmt_upcoming->fetch()) {
        $overviewItems[] = ['name' => "Upcoming: \"{$row['packageName']}\"", 'value' => date("M d, Y", strtotime($row['startDate'])) . " ({$row['reservationCount']} confirmed)", 'status_class' => 'upcoming-trip'];
    }
    $stmt_attention = $pdo->prepare("
        SELECT name, availability, endDate FROM TripPackage 
        WHERE endDate BETWEEN ? AND ? OR availability LIKE ? ORDER BY endDate ASC LIMIT 2
    ");
    $stmt_attention->execute([$today, date('Y-m-d', strtotime('+5 days')), '%Limited%']);
    while ($row = $stmt_attention->fetch()) {
         $overviewItems[] = ['name' => "Attention: \"{$row['name']}\"", 'value' => "Ends: " . date("M d", strtotime($row['endDate'])) . " / Avail: {$row['availability']}", 'status_class' => 'attention-needed'];
    }

    // Fetch Most Reserved Hotels (e.g., Top 3 or 5 - adjust LIMIT)
    $stmt_top_hotels = $pdo->query("
        SELECT h.hotelId, h.name AS hotelName, h.location, COUNT(r.reservationId) AS reservation_count
        FROM Hotel h
        JOIN TripPackage tp ON h.hotelId = tp.hotelId
        JOIN Reservation r ON tp.packageId = r.packageId
        WHERE r.status IN ('Confirmed', 'Done') /* Count only confirmed/completed reservations */
        GROUP BY h.hotelId, h.name, h.location
        ORDER BY reservation_count DESC
        LIMIT 3 
    "); // Changed LIMIT to 3 to fit better in the row with 3 widgets
    $mostReservedHotels = $stmt_top_hotels->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard Data Fetch Error: " . $e->getMessage());
    $dashboard_load_error = "Could not load some dashboard data. Please try refreshing.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

            <main class="admin-main-content">
                <header class="main-content-header">
                    <h2 class="content-title">Dashboard</h2>
                    <div class="user-profile-section">

                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    </div>
                </header>

                <?php if (isset($dashboard_load_error)): ?>
                    <div class="admin-message error"><?php echo $dashboard_load_error; ?></div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <section class="stats-card-grid">
                    <div class="stat-card">
                        <div class="card-header"><h4>Total Hotels</h4><span class="card-icon">🏨</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['totalHotels']); ?></p>
                            <p class="stat-change"><a href="manage_hotels.php">View All</a></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header"><h4>Total Airlines</h4><span class="card-icon">✈️</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['totalAirlines']); ?></p>
                            <p class="stat-change"><a href="manage_airlines.php">View All</a></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header"><h4>Active Packages</h4><span class="card-icon">📦</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['activePackages']); ?></p>
                            <p class="stat-change"><a href="manage_trip_package.php">View All</a></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header"><h4>Pending Reservations</h4><span class="card-icon">⏳</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['pendingReservations']); ?></p>
                            <p class="stat-change"><a href="manage_reservations.php">View All</a></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header"><h4>New Feedback (7d)</h4><span class="card-icon">⭐</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['newFeedbackCount']); ?></p>
                             <p class="stat-change"><a href="manage_feedback.php">View All</a></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card-header"><h4>Total Clients</h4><span class="card-icon">👥</span></div>
                        <div class="card-body">
                            <p class="stat-value"><?php echo number_format($stats['totalClients']); ?></p>
                            <p class="stat-change"><a href="manage_clients.php">View All</a></p>
                        </div>
                    </div>
                </section>

                <!-- Main Dashboard Content Row -->
                <section class="dashboard-content-row">
                    <!-- Overview Section -->
                    <div class="dashboard-widget overview-widget">
                        <div class="widget-header"><h3>Quick Overview</h3></div>
                        <?php if (empty($overviewItems)): ?>
                            <p class="no-data-widget">No pressing items or upcoming departures.</p>
                        <?php else: ?>
                            <ul class="overview-list">
                                <?php foreach ($overviewItems as $item): ?>
                                <li>
                                    <span class="overview-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="overview-item-value status-<?php echo htmlspecialchars($item['status_class']); ?>"><?php echo htmlspecialchars($item['value']); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- REPLACED Booking Goal with Most Reserved Hotels -->
                    <div class="dashboard-widget most-reserved-hotels-widget">
                        <div class="widget-header">
                            <h3>🏆 Most Reserved Hotels</h3>
                            <a href="manage_hotels.php?sort=most_reserved" class="view-all-link">View Full List</a>
                        </div>
                        <?php if (empty($mostReservedHotels)): ?>
                            <p class="no-data-widget">Not enough data for top hotels.</p>
                        <?php else: ?>
                            <ul class="top-items-list">
                                <?php foreach ($mostReservedHotels as $hotel): ?>
                                <li class="top-item">
                                    <div class="item-rank"></div> <!-- CSS handles numbering -->
                                    <div class="item-details">
                                        <span class="item-name"><?php echo htmlspecialchars($hotel['hotelName']); ?></span>
                                        <small class="item-subtext"><?php echo htmlspecialchars($hotel['location']); ?></small>
                                    </div>
                                    <span class="item-value"><?php echo htmlspecialchars($hotel['reservation_count']); ?> Res.</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Activity Section -->
                    <div class="dashboard-widget activity-widget">
                        <div class="widget-header"><h3>Recent Activity</h3><a href="#" class="view-all-link">View All</a></div>
                        <?php if (empty($recentActivities)): ?>
                            <p class="no-data-widget">No recent activity logged.</p>
                        <?php else: ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivities as $item): ?>
                                <li>
                                    <span class="activity-dot <?php echo htmlspecialchars($item['type']); ?>"></span>
                                    <div class="activity-text-content">
                                        <p class="activity-text"><?php echo htmlspecialchars($item['text']); ?></p>
                                        <small class="activity-time"><?php echo htmlspecialchars($item['time']); ?></small>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Chart Placeholders (Can be removed if not planning to use them soon) -->
                <!-- 
                <section class="dashboard-charts">
                     <div class="chart-container-placeholder">
                        <h3>Bookings Overview (Chart Placeholder)</h3>
                        <div class="chart-placeholder-content">[Line chart showing bookings over time]</div>
                    </div>
                     <div class="chart-container-placeholder">
                        <h3>Popular Packages (Chart Placeholder)</h3>
                        <div class="chart-placeholder-content">[Pie chart showing package popularity]</div>
                    </div>
                </section>
                -->

            </main>
<?php
include_once '../includes/footer.php';
?>