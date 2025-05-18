<?php
$current_admin_page = 'hotels'; // For active sidebar link
$page_title = "Manage Hotels";
include_once '../includes/db.php'; // PDO connection and session_start()

// --- Handle Delete Action (from GET request for simplicity on this page) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_hotel' && isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // CSRF check for GET delete (less common, but good if you're making state changes via GET)
    // For proper CSRF on GET, you'd need to append the token to the delete URL.
    // A simple check could be: if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) { ... }
    // For this example, we'll proceed without GET CSRF, but consider adding it for production.

    $delete_id = (int)$_GET['id'];
    try {
        // Optional: Check if hotel is used in TripPackage before deleting
        // Note: Your DB schema has ON DELETE RESTRICT for fk_TripPackage_Hotel,
        // so the DB will prevent deletion if it's in use. We can catch that PDOException.

        $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) as count FROM TripPackage WHERE hotelId = ?");
        $stmt_check_usage->execute([$delete_id]);
        if ($stmt_check_usage->fetchColumn() > 0) {
            $_SESSION['error_message'] = 'Cannot delete hotel: It is currently associated with one or more trip packages. Please remove its associations first or update the packages.';
        } else {
            // Get hotel name before deleting for the success message
            $stmt_get_name = $pdo->prepare("SELECT name FROM Hotel WHERE hotelId = ?");
            $stmt_get_name->execute([$delete_id]);
            $hotel_to_delete = $stmt_get_name->fetch();

            if ($hotel_to_delete) {
                $stmt_delete = $pdo->prepare("DELETE FROM Hotel WHERE hotelId = ?");
                $stmt_delete->execute([$delete_id]);

                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['success_message'] = "Hotel \"".htmlspecialchars($hotel_to_delete['name'])."\" deleted successfully!";
                } else {
                    // This case might occur if another admin deleted it just before this action
                    $_SESSION['error_message'] = "Could not delete hotel. It might have been already deleted or doesn't exist.";
                }
            } else {
                $_SESSION['error_message'] = "Hotel with ID ".htmlspecialchars($delete_id)." not found for deletion.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error deleting hotel (ID: $delete_id): " . $e->getMessage());
        // Check for specific foreign key constraint violation
        if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'foreign key constraint fails') !== false) {
             // A more specific message if possible by inspecting $e->errorInfo or the message string
            if (strpos(strtolower($e->getMessage()), 'fk_trippackage_hotel') !== false) {
                $_SESSION['error_message'] = 'Cannot delete hotel: It is currently used in existing trip packages. Please update or remove the associated packages first.';
            } else if (strpos(strtolower($e->getMessage()), 'fk_agreement_hotel') !== false) {
                 $_SESSION['error_message'] = 'Cannot delete hotel: It is currently referenced in existing agreements. Please update or remove the associated agreements first.';
            } else {
                 $_SESSION['error_message'] = "Database error: Could not delete hotel due to existing references.";
            }
        } else {
            $_SESSION['error_message'] = "A database error occurred while trying to delete the hotel.";
        }
    }
    header("Location: manage_hotels.php"); // Redirect to refresh the list and show message
    exit;
}

// --- Fetch all hotels for display ---
$hotels = [];
$page_load_error_message = ''; // For errors during initial data fetch
try {
    $stmt = $pdo->query("SELECT hotelId, name, location, amenities FROM Hotel ORDER BY name ASC");
    $hotels = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching hotels: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve hotels from the database. Please try again later.";
}

// --- Include Header and Sidebar (AFTER any potential redirects or exits) ---
include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Hotels</h2>
        <div class="header-actions">
            <a href="hotel_form.php" class="btn-primary-admin">
                <span class="btn-icon">➕</span> Add New Hotel
            </a>
        </div>
    </header>

    <!-- Display session messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="admin-message success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="admin-message error">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($page_load_error_message)): ?>
        <div class="admin-message error">
            <?php echo $page_load_error_message; ?>
        </div>
    <?php endif; ?>


    <!-- Hotels List Section -->
    <section class="admin-table-section">
        <h3>Current Hotels</h3>
        <?php if (empty($hotels) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No hotels found. <a href="hotel_form.php">Add one now!</a></p>
        <?php elseif (!empty($hotels)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Amenities</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotels as $hotel): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hotel['hotelId']); ?></td>
                            <td><?php echo htmlspecialchars($hotel['name']); ?></td>
                            <td><?php echo htmlspecialchars($hotel['location']); ?></td>
                            <td class="amenities-cell"><?php echo nl2br(htmlspecialchars($hotel['amenities'])); ?></td>
                            <td class="actions-cell">
                                <a href="hotel_form.php?edit_id=<?php echo $hotel['hotelId']; ?>" class="btn-action edit">
                                    ✏️ <span class="action-text">Update</span>
                                </a>
                                <?php
                                // For delete, include CSRF token in URL if you want to check it for GET.
                                // $delete_url = "manage_hotels.php?action=delete_hotel&id={$hotel['hotelId']}&token=" . urlencode($_SESSION['csrf_token']);
                                // For now, simple GET without token for delete, relying on JS confirm.
                                $delete_url = "manage_hotels.php?action=delete_hotel&id={$hotel['hotelId']}";
                                ?>
                                <a href="<?php echo $delete_url; ?>" class="btn-action delete"
                                   onclick="return confirm('Are you sure you want to delete hotel: <?php echo htmlspecialchars(addslashes($hotel['name'])); ?>? This action might fail if the hotel is in use.');">
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