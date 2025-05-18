<?php
$current_admin_page = 'agreements'; // For active sidebar link
$page_title = "Manage Agreements";
include_once '../includes/db.php';

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_agreement' && isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $delete_id = (int)$_GET['id'];
    try {
        // Check if agreement is used in TripPackage before deleting
        $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) FROM TripPackage WHERE agreementId = ?");
        $stmt_check_usage->execute([$delete_id]);
        if ($stmt_check_usage->fetchColumn() > 0) {
            $_SESSION['error_message'] = 'Cannot delete agreement: It is currently associated with one or more trip packages. Please update or remove these associations first.';
        } else {
            $stmt_get_num = $pdo->prepare("SELECT agreementNumber FROM Agreement WHERE agreementId = ?");
            $stmt_get_num->execute([$delete_id]);
            $agreement_to_delete = $stmt_get_num->fetch();

            if ($agreement_to_delete) {
                $stmt_delete = $pdo->prepare("DELETE FROM Agreement WHERE agreementId = ?");
                $stmt_delete->execute([$delete_id]);
                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['success_message'] = "Agreement \"".htmlspecialchars($agreement_to_delete['agreementNumber'])."\" deleted successfully!";
                } else {
                     $_SESSION['error_message'] = "Could not delete agreement. It might have been already deleted.";
                }
            } else {
                 $_SESSION['error_message'] = "Agreement with ID ".htmlspecialchars($delete_id)." not found.";
            }
        }
    } catch (PDOException $e) {
        error_log("Error deleting agreement (ID: $delete_id): " . $e->getMessage());
        $error_detail = "A database error occurred. ";
         if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'foreign key constraint fails') !== false) {
             if (strpos(strtolower($e->getMessage()), 'fk_trippackage_agreement') !== false) {
                $error_detail = 'Cannot delete agreement: It is currently used in existing trip packages.';
            }
        }
        $_SESSION['error_message'] = $error_detail;
    }
    header("Location: manage_agreements.php");
    exit;
}

// --- Fetch all agreements for display ---
$agreements = [];
$page_load_error_message = '';
try {
    // Joining with User, Hotel, Airline to display names
    $sql = "SELECT a.agreementId, a.agreementNumber, a.startDate, a.endDate, a.prices, 
                   u_admin.username AS adminUsername, 
                   h.name AS hotelName, 
                   al.name AS airlineName
            FROM Agreement a
            JOIN User u_admin ON a.adminId = u_admin.userId
            LEFT JOIN Hotel h ON a.hotelId = h.hotelId
            LEFT JOIN Airline al ON a.airlineId = al.airlineId
            ORDER BY a.startDate DESC, a.agreementNumber ASC";
    $stmt = $pdo->query($sql);
    $agreements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching agreements: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve agreements from the database. Please try again later.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Agreements</h2>
        <div class="header-actions">
            <a href="agreement_form.php" class="btn-primary-admin">
                <span class="btn-icon">➕📄</span> Add New Agreement
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
        <h3>Current Agreements</h3>
        <?php if (empty($agreements) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No agreements found. <a href="agreement_form.php">Add one now!</a></p>
        <?php elseif (!empty($agreements)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Price</th>
                            <th>Hotel</th>
                            <th>Airline</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agreements as $agreement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($agreement['agreementId']); ?></td>
                            <td><?php echo htmlspecialchars($agreement['agreementNumber']); ?></td>
                            <td><?php echo htmlspecialchars(date("M d, Y", strtotime($agreement['startDate']))); ?></td>
                            <td><?php echo htmlspecialchars(date("M d, Y", strtotime($agreement['endDate']))); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($agreement['prices'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($agreement['hotelName'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($agreement['airlineName'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($agreement['adminUsername']); ?></td>
                            <td class="actions-cell">
                                <a href="agreement_form.php?edit_id=<?php echo $agreement['agreementId']; ?>" class="btn-action edit">
                                    ✏️ <span class="action-text">Update</span>
                                </a>
                                <a href="manage_agreements.php?action=delete_agreement&id=<?php echo $agreement['agreementId']; ?>" class="btn-action delete"
                                   onclick="return confirm('Are you sure you want to delete agreement: <?php echo htmlspecialchars(addslashes($agreement['agreementNumber'])); ?>? This may fail if it is in use by trip packages.');">
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