<?php
$current_admin_page = 'reservations';
$page_title = "Manage Reservations";
include_once '../includes/db.php';

// --- Handle Status Update Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token validation failed. Operation aborted.';
        header("Location: manage_reservations.php");
        exit;
    }

    $reservation_id_action = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT);
    $action_type = $_POST['action'];

    if ($reservation_id_action) {
        try {
            $new_status = '';
            $success_action_verb = '';

            if ($action_type === 'confirm_reservation') {
                $new_status = 'Confirmed';
                $success_action_verb = 'confirmed';
            } elseif ($action_type === 'mark_done') {
                $new_status = 'Done'; // Or 'Completed' if you prefer
                $success_action_verb = 'marked as done';
            }

            if ($new_status) {
                // Check current status to prevent invalid transitions
                $stmt_check = $pdo->prepare("SELECT status FROM Reservation WHERE reservationId = ?");
                $stmt_check->execute([$reservation_id_action]);
                $current_status = $stmt_check->fetchColumn();

                $can_proceed = false;
                if ($action_type === 'confirm_reservation' && $current_status && $current_status !== 'Confirmed' && $current_status !== 'Cancelled' && $current_status !== 'Done') {
                    $can_proceed = true;
                } elseif ($action_type === 'mark_done' && $current_status === 'Confirmed') { // Only allow marking 'Confirmed' reservations as 'Done'
                    $can_proceed = true;
                }

                if ($can_proceed) {
                    $stmt = $pdo->prepare("UPDATE Reservation SET status = ? WHERE reservationId = ?");
                    $stmt->execute([$new_status, $reservation_id_action]);

                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success_message'] = "Reservation ID #".htmlspecialchars($reservation_id_action)." has been ".$success_action_verb." successfully!";
                    } else {
                        $_SESSION['warning_message'] = "Reservation ID #".htmlspecialchars($reservation_id_action)." was not updated. Status might already be '".$new_status."' or an issue occurred.";
                    }
                } elseif ($current_status === $new_status) {
                    $_SESSION['info_message'] = "Reservation ID #".htmlspecialchars($reservation_id_action)." is already " . strtolower($new_status) . ".";
                } elseif ($action_type === 'mark_done' && $current_status !== 'Confirmed') {
                     $_SESSION['error_message'] = "Reservation ID #".htmlspecialchars($reservation_id_action)." must be 'Confirmed' before it can be marked as 'Done'. Current status: " . htmlspecialchars($current_status);
                } else {
                    $_SESSION['error_message'] = "Reservation ID #".htmlspecialchars($reservation_id_action)." not found or its current status ('".htmlspecialchars($current_status)."') does not allow this action.";
                }
            } else {
                $_SESSION['error_message'] = "Invalid action specified.";
            }
        } catch (PDOException $e) {
            error_log("Error updating reservation status (ID: $reservation_id_action, Action: $action_type): " . $e->getMessage());
            $_SESSION['error_message'] = "A database error occurred while updating the reservation status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid Reservation ID for action.";
    }
    header("Location: manage_reservations.php");
    exit;
}


// --- Fetch all reservations for display (same as before) ---
$reservations = [];
$page_load_error_message = '';
try {
    $sql = "SELECT r.reservationId, r.bookingDate, r.travelDate, r.numberOfPeople, r.status,
                   u_client.username AS clientUsername, u_client.userId AS clientId,
                   tp.name AS packageName, tp.packageId AS packageId,
                   p_client.fullName AS clientFullName
            FROM Reservation r
            JOIN User u_client ON r.clientId = u_client.userId
            LEFT JOIN Profile p_client ON u_client.userId = p_client.userId
            JOIN TripPackage tp ON r.packageId = tp.packageId
            ORDER BY r.bookingDate DESC";
    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching reservations: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve reservations from the database. Please try again later.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Client Reservations</h2>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="admin-message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="admin-message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['info_message'])): ?>
        <div class="admin-message info"><?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['warning_message'])): ?>
        <div class="admin-message warning"><?php echo $_SESSION['warning_message']; unset($_SESSION['warning_message']); ?></div>
    <?php endif; ?>
    <?php if (!empty($page_load_error_message)): ?>
        <div class="admin-message error"><?php echo $page_load_error_message; ?></div>
    <?php endif; ?>

    <section class="admin-table-section">
        <h3>All Reservations</h3>
        <?php if (empty($reservations) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No reservations found.</p>
        <?php elseif (!empty($reservations)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Res. ID</th>
                            <th>Client</th>
                            <th>Package</th>
                            <th>Booking Dt.</th>
                            <th>Travel Dt.</th>
                            <th># People</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['reservationId']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($reservation['clientFullName'] ?? $reservation['clientUsername']); ?>
                                <small>(ID: <?php echo htmlspecialchars($reservation['clientId']); ?>)</small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($reservation['packageName']); ?>
                                <small>(ID: <?php echo htmlspecialchars($reservation['packageId']); ?>)</small>
                            </td>
                            <td><?php echo htmlspecialchars(date("M d, Y H:i", strtotime($reservation['bookingDate']))); ?></td>
                            <td><?php echo htmlspecialchars(date("M d, Y", strtotime($reservation['travelDate']))); ?></td>
                            <td><?php echo htmlspecialchars($reservation['numberOfPeople']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $reservation['status'] ?? 'unknown'))); ?>">
                                    <?php echo htmlspecialchars($reservation['status'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <?php
                                $currentStatus = $reservation['status'] ?? '';
                                $resId = htmlspecialchars($reservation['reservationId']);
                                $csrfTokenField = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
                                $resIdField = '<input type="hidden" name="reservation_id" value="' . $resId . '">';

                                if ($currentStatus !== 'Confirmed' && $currentStatus !== 'Done' && $currentStatus !== 'Cancelled'):
                                ?>
                                    <form action="manage_reservations.php" method="POST" style="display: inline;">
                                        <?php echo $csrfTokenField; ?>
                                        <?php echo $resIdField; ?>
                                        <input type="hidden" name="action" value="confirm_reservation">
                                        <button type="submit" class="btn-action confirm" 
                                                onclick="return confirm('Are you sure you want to confirm reservation #<?php echo $resId; ?>?');">
                                            ✔️ <span class="action-text">Confirm</span>
                                        </button>
                                    </form>
                                <?php elseif ($currentStatus === 'Confirmed'): ?>
                                    <form action="manage_reservations.php" method="POST" style="display: inline;">
                                        <?php echo $csrfTokenField; ?>
                                        <?php echo $resIdField; ?>
                                        <input type="hidden" name="action" value="mark_done">
                                        <button type="submit" class="btn-action done"
                                                onclick="return confirm('Are you sure you want to mark reservation #<?php echo $resId; ?> as done/completed?');">
                                            🏁 <span class="action-text">Mark as Done</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="action-text-disabled">
                                        <?php echo ($currentStatus === 'Done' || $currentStatus === 'Cancelled') ? 'No further actions' : 'N/A'; ?>
                                    </span>
                                <?php endif; ?>
                                 <a href="#" class="btn-action view-details" title="View Details (Not Implemented)">ℹ️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
include_once '../includes/footer.php';
?>