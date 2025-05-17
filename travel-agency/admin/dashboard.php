<?php
// This page assumes an admin user is already logged in.
// Session start and authentication check would normally be here.
// session_start();
// if (!isset($_SESSION['admin_user_id']) || $_SESSION['user_role'] !== 'Admin') {
//     header('Location: login.php'); // Redirect to an admin login page
//     exit;
// }

// Include database connection - useful for fetching real data later
require_once '../includes/db.php';

// Placeholder data for summary cards
// In a real application, you would fetch these counts from the database using $conn
$totalHotels = 0; // Example: SELECT COUNT(*) FROM Hotel
$totalAirlines = 0; // Example: SELECT COUNT(*) FROM Airline
$totalTripPackages = 0; // Example: SELECT COUNT(*) FROM TripPackage
$pendingReservations = 0; // Example: SELECT COUNT(*) FROM Reservation WHERE status = 'Pending'

// Simulate fetching data (replace with actual DB queries when ready)
if ($conn) { // Check if $conn is valid
    // Example: Fetch total hotels (actual query commented out)
    // $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM Hotel");
    // if ($result) $totalHotels = mysqli_fetch_assoc($result)['count'];
    $totalHotels = 125; // Static placeholder

    // $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM Airline");
    // if ($result) $totalAirlines = mysqli_fetch_assoc($result)['count'];
    $totalAirlines = 30; // Static placeholder

    // $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM TripPackage");
    // if ($result) $totalTripPackages = mysqli_fetch_assoc($result)['count'];
    $totalTripPackages = 75; // Static placeholder

    // $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM Reservation WHERE status = 'Pending'"); // Assuming a 'status' column
    // if ($result) $pendingReservations = mysqli_fetch_assoc($result)['count'];
    $pendingReservations = 15; // Static placeholder
}


$page_title = "Admin Dashboard"; // Set page title for header
include_once '../includes/header.php';
?>

<div class="admin-dashboard-content">
    <h1 class="page-title">Welcome, Admin!</h1>
    <p class="welcome-message">This is your central hub for managing the Travel Agency system.</p>

    <section class="dashboard-summary-cards">
        <div class="summary-card">
            <h3 class="card-title">Total Hotels</h3>
            <div class="count"><?php echo $totalHotels; ?></div>
            <p class="card-description">Registered hotels in the system.</p>
            <a href="#" class="card-link">Manage Hotels →</a>
        </div>
        <div class="summary-card">
            <h3 class="card-title">Total Airlines</h3>
            <div class="count"><?php echo $totalAirlines; ?></div>
            <p class="card-description">Partner airlines available.</p>
            <a href="#" class="card-link">Manage Airlines →</a>
        </div>
        <div class="summary-card">
            <h3 class="card-title">Total Trip Packages</h3>
            <div class="count"><?php echo $totalTripPackages; ?></div>
            <p class="card-description">Configured travel packages.</p>
            <a href="#" class="card-link">Manage Packages →</a>
        </div>
        <div class="summary-card">
            <h3 class="card-title">Pending Reservations</h3>
            <div class="count"><?php echo $pendingReservations; ?></div>
            <p class="card-description">Bookings requiring attention.</p>
            <a href="#" class="card-link">View Reservations →</a>
        </div>
    </section>

    <section class="admin-quick-links">
        <h2 class="section-title">Quick Actions</h2>
        <ul class="quick-links-list">
            <li><a href="#" class="quick-link-btn">Manage Hotels</a></li>
            <li><a href="#" class="quick-link-btn">Manage Airlines</a></li>
            <li><a href="#" class="quick-link-btn">Manage Trip Packages</a></li>
            <li><a href="#" class="quick-link-btn">Manage Reservations</a></li>
            <li><a href="#" class="quick-link-btn">Manage Client Accounts</a></li>
            <li><a href="#" class="quick-link-btn">Manage Agreements</a></li>
            <li><a href="#" class="quick-link-btn">View Client Feedback</a></li>
            <li><a href="#" class="quick-link-btn">Generate Reports</a></li>
            <li><a href="#" class="quick-link-btn">System Settings</a></li>
            <li><a href="#" class="quick-link-btn logout-btn">Logout</a></li>
        </ul>
    </section>
</div>

<?php
include_once '../includes/footer.php';
?>