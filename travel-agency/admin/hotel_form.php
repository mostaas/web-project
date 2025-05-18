<?php
$current_admin_page = 'hotels'; // Keep 'hotels' active in sidebar
$page_title = "Hotel Form"; // Title will change based on mode
include_once '../includes/db.php'; // For database operations
include_once '../includes/header.php';
include_once 'admin_sidebar.php';

// --- Determine Mode: Add or Edit ---
$edit_mode = false;
$hotel_id_to_edit = null;
$hotel_data = [ // Default empty values for the form
    'name' => '',
    'location' => '',
    'amenities' => ''
];

if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $hotel_id_to_edit = (int)$_GET['edit_id'];
    $page_title = "Edit Hotel";

    // Fetch existing hotel data for editing
    try {
        $stmt = $pdo->prepare("SELECT name, location, amenities FROM Hotel WHERE hotelId = ?");
        $stmt->execute([$hotel_id_to_edit]);
        $fetched_hotel = $stmt->fetch();
        if ($fetched_hotel) {
            $hotel_data = $fetched_hotel;
        } else {
            $_SESSION['error_message'] = "Hotel with ID ".htmlspecialchars($hotel_id_to_edit)." not found.";
            header("Location: manage_hotels.php"); // Redirect if hotel not found
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching hotel for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Error fetching hotel data. Please try again.";
        header("Location: manage_hotels.php");
        exit;
    }
} else {
    $page_title = "Add New Hotel";
}

// --- Handle Form Submission (Add or Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token validation failed. Operation aborted.';
        header("Location: manage_hotels.php"); // Redirect on CSRF failure
        exit;
    }

    // Sanitize inputs
    $hotel_name = trim(htmlspecialchars($_POST['hotel_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $hotel_location = trim(htmlspecialchars($_POST['hotel_location'] ?? '', ENT_QUOTES, 'UTF-8'));
    $hotel_amenities = trim(htmlspecialchars($_POST['hotel_amenities'] ?? '', ENT_QUOTES, 'UTF-8'));
    $posted_hotel_id = filter_var($_POST['hotel_id'] ?? null, FILTER_VALIDATE_INT); // For update

    // Basic Validation
    if (empty($hotel_name) || empty($hotel_location)) {
        $_SESSION['error_message'] = "Hotel Name and Location are required.";
        // To retain form data on error, you might redirect back with query params or store in session temporarily.
        // For simplicity, current redirect goes to manage_hotels.php.
        if ($edit_mode && $posted_hotel_id) {
            header("Location: hotel_form.php?edit_id=" . $posted_hotel_id);
        } else {
            header("Location: hotel_form.php");
        }
        exit;
    }

    try {
        if ($edit_mode && $posted_hotel_id && $posted_hotel_id == $hotel_id_to_edit) {
            // --- Update Existing Hotel ---
            $stmt = $pdo->prepare("UPDATE Hotel SET name = ?, location = ?, amenities = ? WHERE hotelId = ?");
            $stmt->execute([$hotel_name, $hotel_location, $hotel_amenities, $posted_hotel_id]);
            $_SESSION['success_message'] = "Hotel \"".htmlspecialchars($hotel_name)."\" updated successfully!";
        } else {
            // --- Add New Hotel ---
            $stmt = $pdo->prepare("INSERT INTO Hotel (name, location, amenities) VALUES (?, ?, ?)");
            $stmt->execute([$hotel_name, $hotel_location, $hotel_amenities]);
            $_SESSION['success_message'] = "Hotel \"".htmlspecialchars($hotel_name)."\" added successfully!";
        }
        header("Location: manage_hotels.php"); // Redirect to the list page after success
        exit;

    } catch (PDOException $e) {
        error_log("Database error in hotel_form.php: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred: " . $e->getMessage();
        // Redirect back to the form, potentially with an error flag or retaining input
        if ($edit_mode && $hotel_id_to_edit) {
            header("Location: hotel_form.php?edit_id=" . $hotel_id_to_edit);
        } else {
            header("Location: hotel_form.php");
        }
        exit;
    }
}
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title"><?php echo $page_title; // Dynamic title ?></h2>
    </header>

    <!-- Display any session messages -->
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
        <form action="hotel_form.php<?php echo $edit_mode ? '?edit_id='.$hotel_id_to_edit : ''; ?>" method="POST" class="styled-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel_id_to_edit); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="hotel_name">Hotel Name <span class="required">*</span></label>
                <input type="text" id="hotel_name" name="hotel_name" required
                       value="<?php echo htmlspecialchars($hotel_data['name']); ?>">
            </div>
            <div class="form-group">
                <label for="hotel_location">Location <span class="required">*</span></label>
                <input type="text" id="hotel_location" name="hotel_location" required
                       value="<?php echo htmlspecialchars($hotel_data['location']); ?>">
            </div>
            <div class="form-group">
                <label for="hotel_amenities">Amenities (comma-separated or one per line)</label>
                <textarea id="hotel_amenities" name="hotel_amenities" rows="5"><?php echo htmlspecialchars($hotel_data['amenities']); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary-admin">
                    <?php echo $edit_mode ? 'Update Hotel' : 'Save Hotel'; ?>
                </button>
                <a href="manage_hotels.php" class="btn-secondary-admin">Cancel</a>
            </div>
        </form>
    </section>

</main>

<?php
include_once '../includes/footer.php';
?>