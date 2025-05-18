<?php
$current_admin_page = 'clients'; // For active sidebar link
$page_title = "Manage Clients";
include_once '../includes/db.php'; // PDO connection and session_start()

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_client' && isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $delete_user_id = (int)$_GET['id'];
    try {
        $stmt_user_check = $pdo->prepare("SELECT username, role FROM User WHERE userId = ?");
        $stmt_user_check->execute([$delete_user_id]);
        $user_to_delete_details = $stmt_user_check->fetch();

        if (!$user_to_delete_details) {
            $_SESSION['error_message'] = "Client with ID ".htmlspecialchars($delete_user_id)." not found.";
        } elseif ($user_to_delete_details['role'] === 'Admin') {
            $_SESSION['error_message'] = "Admins cannot be deleted from this interface.";
        } else {
            $stmt_reservations = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE clientId = ? AND status NOT IN ('Done', 'Cancelled')");
            $stmt_reservations->execute([$delete_user_id]);
            if ($stmt_reservations->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Cannot delete client \"".htmlspecialchars($user_to_delete_details['username'])."\": They have active or pending reservations. Please resolve these first.";
            } else {
                $pdo->beginTransaction();
                $stmt_delete_user = $pdo->prepare("DELETE FROM User WHERE userId = ? AND role = 'Client'");
                $stmt_delete_user->execute([$delete_user_id]);

                if ($stmt_delete_user->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['success_message'] = "Client \"".htmlspecialchars($user_to_delete_details['username'])."\" and their associated data deleted successfully!";
                } else {
                    $pdo->rollBack();
                    $_SESSION['error_message'] = "Could not delete client. They might have already been deleted or an issue occurred.";
                }
            }
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error deleting client (ID: $delete_user_id): " . $e->getMessage());
        $error_detail = "A database error occurred. ";
        if (strpos($e->getCode(), '23000') !== false && strpos(strtolower($e->getMessage()), 'foreign key constraint fails') !== false) {
            if (strpos(strtolower($e->getMessage()), 'fk_reservation_client') !== false) {
                 $error_detail = 'Cannot delete client: They have existing reservations linked.';
            } else {
                 $error_detail = 'Cannot delete client due to existing related records.';
            }
        }
        $_SESSION['error_message'] = $error_detail;
    }
    header("Location: manage_clients.php");
    exit;
}


// --- Fetch all clients (Users with role 'Client') for display ---
$clients = [];
$page_load_error_message = '';
try {
    // Use Profile.address AS emailAddress
    $sql = "SELECT u.userId, u.username, u.role, 
                   p.fullName, p.address AS emailAddress, p.phone 
            FROM User u
            LEFT JOIN Profile p ON u.userId = p.userId 
            WHERE u.role = 'Client'
            ORDER BY u.username ASC";
    $stmt = $pdo->query($sql);
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching clients: " . $e->getMessage());
    $page_load_error_message = "Could not retrieve client data from the database. Please try again later.";
}

include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title">Manage Clients</h2>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="admin-message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="admin-message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    <?php if (!empty($page_load_error_message)): ?>
        <div class="admin-message error"><?php echo $page_load_error_message; ?></div>
    <?php endif; ?>

    <section class="admin-table-section">
        <h3>Current Client Accounts</h3>
        <?php if (empty($clients) && empty($page_load_error_message)): ?>
            <p class="no-data-message">No clients found.</p>
        <?php elseif (!empty($clients)): ?>
            <div class="table-responsive-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email Address</th> <!-- Changed header text -->
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['userId']); ?></td>
                            <td><?php echo htmlspecialchars($client['username']); ?></td>
                            <td><?php echo htmlspecialchars($client['fullName'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($client['emailAddress'] ?? 'N/A'); ?></td> <!-- Display emailAddress -->
                            <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                            <td class="actions-cell">
                                <?php
                                $delete_client_url = "manage_clients.php?action=delete_client&id={$client['userId']}";
                                ?>
                                <a href="<?php echo $delete_client_url; ?>" class="btn-action delete"
                                   onclick="return confirm('Are you sure you want to delete client: <?php echo htmlspecialchars(addslashes($client['username'])); ?> (<?php echo htmlspecialchars(addslashes($client['fullName'] ?? '')); ?>)? This will also delete their profile and feedback. This action cannot be undone if they have no active reservations.');">
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