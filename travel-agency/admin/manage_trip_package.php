<?php
$current_admin_page = 'packages'; // For active sidebar link
$page_title = "Manage Trip Packages";
include_once '../includes/db.php';

// --- Auto-delete expired packages (endDate passed) ---
// This could also be a cron job for better performance on busy sites
try {
    $today = date("Y-m-d");
    // Before deleting, check if they are in reservations (ON DELETE RESTRICT will prevent this)
    // It's better to mark them as 'expired' or 'inactive' rather than outright deleting if they have reservations.
    // For now, let's just try to delete based on endDate as per requirement, DB constraints will apply.
    $stmt_expire = $pdo->prepare("DELETE FROM TripPackage WHERE endDate < ?");
    $stmt_expire->execute([$today]);
    if ($stmt_expire->rowCount() > 0) {
        $_SESSION['info_message'] = $stmt_expire->rowCount() . " expired trip package(s) were automatically removed (if not in use by reservations).";
    }
} catch (PDOException $e) {
    // Catch foreign key constraint violations if trying to delete packages with reservations
    if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'fk_reservation_trippackage') !== false) {
        $_SESSION['warning_message'] = "Some expired packages could not be automatically deleted as they have existing reservations. Please review them.";
    } else {
        error_log("Error auto-deleting expired packages: " . $e->getMessage());
        // Don't show a direct error to user for this background task unless critical
    }
}


// --- Handle Manual Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_package' && isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $delete_id = (int)$_GET['id'];
    try {
        // Check if package is used in Reservation before deleting
        $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE packageId = ?");
        $stmt_check_usage->execute([$delete_id]);
        if ($stmt_check_usage->fetchColumn() > 0) {
            $_SESSION['error_message'] = 'Cannot delete trip package: It is currently associated with one or more reservations. Please cancel or reassign these reservations first.';
        } else {
            $stmt_get_name = $pdo->prepare("SELECT name FROM TripPackage WHERE packageId = ?");
            $stmt_get_name->execute([$delete_id]);
            $package_to_delete = $stmt_get_name->fetch();

            if ($package_to_delete) {
                $stmt_delete = $pdo->prepare("DELETE FROM TripPackage WHERE packageId = ?");
                $stmt_delete->execute([$delete_id]);
                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['success_message'] = "Trip Package \"".htmlspecialchars($package_to_delete['name'])."\" deleted successfully!";
                } else {
                     $_SESSION['error_message'] = "Could not delete trip package. It might have been already deleted.";
                }
            } else {
                 $_SESSION['error_message'] = "Trip Package with ID ".htmlspecialchars($delete_id)." not found.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error deleting trip package (ID: $delete_id): " . $e->getMessage());
         if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'fk_reservation_trippackage') !== false) {
            $_SESSION['error_message'] = 'Cannot delete trip package: It has existing reservations.';
        } else {
            $_SESSION['error_message'] = "A database error occurred while trying to delete the trip package.";
        }
    }
    header("Location: manage_trip_package.php");
    exit;
}

// --- Fetch all trip packages for display ---
$packages = [];
$page_load_error_message = '';
try {
    $sql = "SELECT tp.packageId, tp.name, tp.destination, tp.startDate, tp.endDate, tp.price, tp.type,
                   h.name AS hotelName, 
                   al.name AS airlineName,
                   ag.agreementNumber
            FROM TripPackage tp
            JOIN Hotel h ON tp.hotelId = h.hotelId
            JOIN Airline al ON tp.airlineId = al.airlineId
            LEFT JOIN Agreement ag ON tp.agreementId = ag.agreementId
            ORDER BY tp.startDate DESC, tp.name ASC";
    $stmt = $pdo->query($sql);
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching trip packages: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve trip packages from the database. Please try again later.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Trip Packages</h2>
        <div class="header-actions">
            <a href="trip_package_form.php" class="btn-primary-admin">
                <span class="btn-icon">➕📦</span> Add New Trip Package
            </a>
        </div>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="admin-message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="admin-message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['info_message'])): /* For auto-delete info */ ?>
        <div class="admin-message info"><?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?></div>
    <?php endif; ?>
     <?php if (isset($_SESSION['warning_message'])): /* For auto-delete warning */ ?>
        <div class="admin-message warning"><?php echo $_SESSION['warning_message']; unset($_SESSION['warning_message']); ?></div>
    <?php endif; ?>
    <?php if (!empty($page_load_error_message)): ?>
        <div class="admin-message error"><?php echo $page_load_error_message; ?></div>
    <?php endif; ?>

    <section class="admin-table-section">
        <h3>Current Trip Packages</h3>
        <?php if (empty($packages) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No trip packages found. <a href="trip_package_form.php">Add one now!</a></p>
        <?php elseif (!empty($packages)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Package Name</th>
                            <th>Destination</th>
                            <th>Dates (Start-End)</th>
                            <th>Price</th>
                            <th>Type</th>
                            <th>Hotel</th>
                            <th>Airline</th>
                            <th>Agreement #</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($package['packageId']); ?></td>
                            <td><?php echo htmlspecialchars($package['name']); ?></td>
                            <td><?php echo htmlspecialchars($package['destination']); ?></td>
                            <td>
                                <?php echo htmlspecialchars(date("M d, Y", strtotime($package['startDate']))); ?> - 
                                <?php echo htmlspecialchars(date("M d, Y", strtotime($package['endDate']))); ?>
                            </td>
                            <td>$<?php echo htmlspecialchars(number_format($package['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($package['type']); ?></td>
                            <td><?php echo htmlspecialchars($package['hotelName']); ?></td>
                            <td><?php echo htmlspecialchars($package['airlineName']); ?></td>
                            <td><?php echo htmlspecialchars($package['agreementNumber'] ?? 'N/A'); ?></td>
                            <td class="actions-cell">
                                <a href="trip_package_form.php?edit_id=<?php echo $package['packageId']; ?>" class="btn-action edit">
                                    ✏️ <span class="action-text">Update</span>
                                </a>
                                <a href="manage_trip_package.php?action=delete_package&id=<?php echo $package['packageId']; ?>" class="btn-action delete"
                                   onclick="return confirm('Are you sure you want to delete package: <?php echo htmlspecialchars(addslashes($package['name'])); ?>? This may fail if it has reservations.');">
                                    🗑️ <span class="action-text">Delete</span>
                                </a>
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