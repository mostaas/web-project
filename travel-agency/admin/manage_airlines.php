<?php
$current_admin_page = 'airlines'; // For active sidebar link
$page_title = "Manage Airlines";
include_once '../includes/db.php';

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_airline' && isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Consider adding CSRF token to GET URL for delete for added security, or convert to POST.
    // if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    //     $_SESSION['error_message'] = 'CSRF token validation failed for delete.';
    //     header("Location: manage_airlines.php");
    //     exit;
    // }

    $delete_id = (int)$_GET['id'];
    try {
        // Check if airline is used in TripPackage or Agreement before deleting
        $stmt_check_trip = $pdo->prepare("SELECT COUNT(*) FROM TripPackage WHERE airlineId = ?");
        $stmt_check_trip->execute([$delete_id]);
        $in_trip_package = $stmt_check_trip->fetchColumn() > 0;

        $stmt_check_agreement = $pdo->prepare("SELECT COUNT(*) FROM Agreement WHERE airlineId = ?");
        $stmt_check_agreement->execute([$delete_id]);
        $in_agreement = $stmt_check_agreement->fetchColumn() > 0;

        if ($in_trip_package || $in_agreement) {
            $error_msg_parts = [];
            if ($in_trip_package) $error_msg_parts[] = "trip packages";
            if ($in_agreement) $error_msg_parts[] = "agreements";
            $_SESSION['error_message'] = 'Cannot delete airline: It is currently associated with ' . implode(' and ', $error_msg_parts) . '. Please update or remove these associations first.';
        } else {
            $stmt_get_name = $pdo->prepare("SELECT name FROM Airline WHERE airlineId = ?");
            $stmt_get_name->execute([$delete_id]);
            $airline_to_delete = $stmt_get_name->fetch();

            if ($airline_to_delete) {
                $stmt_delete = $pdo->prepare("DELETE FROM Airline WHERE airlineId = ?");
                $stmt_delete->execute([$delete_id]);

                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['success_message'] = "Airline \"".htmlspecialchars($airline_to_delete['name'])."\" deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Could not delete airline. It might have been already deleted or doesn't exist.";
                }
            } else {
                 $_SESSION['error_message'] = "Airline with ID ".htmlspecialchars($delete_id)." not found for deletion.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error deleting airline (ID: $delete_id): " . $e->getMessage());
        $error_message_detail = "A database error occurred. ";
        if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'foreign key constraint fails') !== false) {
             if (strpos(strtolower($e->getMessage()), 'fk_trippackage_airline') !== false) {
                $error_message_detail = 'Cannot delete airline: It is currently used in existing trip packages.';
            } else if (strpos(strtolower($e->getMessage()), 'fk_agreement_airline') !== false) {
                 $error_message_detail = 'Cannot delete airline: It is currently referenced in existing agreements.';
            }
        }
        $_SESSION['error_message'] = $error_message_detail;
    }
    header("Location: manage_airlines.php");
    exit;
}

// --- Fetch all airlines for display ---
$airlines = [];
$page_load_error_message = '';
try {
    $stmt = $pdo->query("SELECT airlineId, name FROM Airline ORDER BY name ASC");
    $airlines = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching airlines: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve airlines from the database. Please try again later.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Airlines</h2>
        <div class="header-actions">
            <a href="airline_form.php" class="btn-primary-admin">
                <span class="btn-icon">➕✈️</span> Add New Airline
            </a>
        </div>
    </header>

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


    <section class="admin-table-section">
        <h3>Current Airlines</h3>
        <?php if (empty($airlines) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No airlines found. <a href="airline_form.php">Add one now!</a></p>
        <?php elseif (!empty($airlines)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($airlines as $airline): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($airline['airlineId']); ?></td>
                            <td><?php echo htmlspecialchars($airline['name']); ?></td>
                            <td class="actions-cell">
                                <a href="airline_form.php?edit_id=<?php echo $airline['airlineId']; ?>" class="btn-action edit">
                                    ✏️ <span class="action-text">Update</span>
                                </a>
                                <?php
                                // $delete_url_airline = "manage_airlines.php?action=delete_airline&id={$airline['airlineId']}&token=" . urlencode($_SESSION['csrf_token']);
                                $delete_url_airline = "manage_airlines.php?action=delete_airline&id={$airline['airlineId']}";
                                ?>
                                <a href="<?php echo $delete_url_airline; ?>" class="btn-action delete"
                                   onclick="return confirm('Are you sure you want to delete airline: <?php echo htmlspecialchars(addslashes($airline['name'])); ?>? This might fail if the airline is in use.');">
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