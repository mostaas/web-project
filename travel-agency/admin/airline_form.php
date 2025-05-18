<?php
$current_admin_page = 'airlines'; // For active sidebar link
$page_title = "Airline Form"; // Dynamic title
include_once '../includes/db.php';
include_once '../includes/header.php';
include_once 'admin_sidebar.php';

// --- Determine Mode: Add or Edit ---
$edit_mode = false;
$airline_id_to_edit = null;
$airline_data = ['name' => '']; // Default empty values

if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $airline_id_to_edit = (int)$_GET['edit_id'];
    $page_title = "Edit Airline";

    try {
        $stmt = $pdo->prepare("SELECT name FROM Airline WHERE airlineId = ?");
        $stmt->execute([$airline_id_to_edit]);
        $fetched_airline = $stmt->fetch();
        if ($fetched_airline) {
            $airline_data = $fetched_airline;
        } else {
            $_SESSION['error_message'] = "Airline with ID ".htmlspecialchars($airline_id_to_edit)." not found.";
            header("Location: manage_airlines.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching airline for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Error fetching airline data. Please try again.";
        header("Location: manage_airlines.php");
        exit;
    }
} else {
    $page_title = "Add New Airline";
}

// --- Handle Form Submission (Add or Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token validation failed. Operation aborted.';
        header("Location: manage_airlines.php");
        exit;
    }

    $airline_name = trim(htmlspecialchars($_POST['airline_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $posted_airline_id = filter_var($_POST['airline_id'] ?? null, FILTER_VALIDATE_INT);

    if (empty($airline_name)) {
        $_SESSION['error_message'] = "Airline Name is required.";
        if ($edit_mode && $posted_airline_id) {
            header("Location: airline_form.php?edit_id=" . $posted_airline_id);
        } else {
            header("Location: airline_form.php");
        }
        exit;
    }

    try {
        if ($edit_mode && $posted_airline_id && $posted_airline_id == $airline_id_to_edit) {
            $stmt = $pdo->prepare("UPDATE Airline SET name = ? WHERE airlineId = ?");
            $stmt->execute([$airline_name, $posted_airline_id]);
            $_SESSION['success_message'] = "Airline \"".htmlspecialchars($airline_name)."\" updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Airline (name) VALUES (?)");
            $stmt->execute([$airline_name]);
            $_SESSION['success_message'] = "Airline \"".htmlspecialchars($airline_name)."\" added successfully!";
        }
        header("Location: manage_airlines.php");
        exit;

    } catch (PDOException $e) {
        error_log("Database error in airline_form.php: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred: " . $e->getMessage();
        if ($edit_mode && $airline_id_to_edit) {
            header("Location: airline_form.php?edit_id=" . $airline_id_to_edit);
        } else {
            header("Location: airline_form.php");
        }
        exit;
    }
}
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title"><?php echo $page_title; ?></h2>
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

    <section class="admin-form-section">
        <form action="airline_form.php<?php echo $edit_mode ? '?edit_id='.$airline_id_to_edit : ''; ?>" method="POST" class="styled-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="airline_id" value="<?php echo htmlspecialchars($airline_id_to_edit); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="airline_name">Airline Name <span class="required">*</span></label>
                <input type="text" id="airline_name" name="airline_name" required
                       value="<?php echo htmlspecialchars($airline_data['name']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-admin">
                    <?php echo $edit_mode ? 'Update Airline' : 'Save Airline'; ?>
                </button>
                <a href="manage_airlines.php" class="btn-secondary-admin">Cancel</a>
            </div>
        </form>
    </section>
</main>

<?php
include_once '../includes/footer.php';
?>