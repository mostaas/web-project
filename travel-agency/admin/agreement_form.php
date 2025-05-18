<?php
$current_admin_page = 'agreements'; // For active sidebar link
$page_title = "Agreement Form"; // Dynamic title
include_once '../includes/db.php';
include_once '../includes/header.php';
include_once 'admin_sidebar.php';

// --- Fetch Hotels and Airlines for dropdowns ---
$hotels = [];
$airlines = [];
try {
    $stmt_hotels = $pdo->query("SELECT hotelId, name FROM Hotel ORDER BY name ASC");
    $hotels = $stmt_hotels->fetchAll();

    $stmt_airlines = $pdo->query("SELECT airlineId, name FROM Airline ORDER BY name ASC");
    $airlines = $stmt_airlines->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching hotels/airlines for agreement form: " . $e->getMessage());
    // Handle error appropriately, maybe show a message on the form page
    $_SESSION['error_message'] = "Could not load necessary data for the form. Please try again.";
    // Potentially redirect or display error on form page itself
}


// --- Determine Mode: Add or Edit ---
$edit_mode = false;
$agreement_id_to_edit = null;
$agreement_data = [ // Default empty values
    'agreementNumber' => 'AGR-' . strtoupper(uniqid()), // Auto-generate a unique number
    'startDate' => '',
    'endDate' => '',
    'terms' => '',
    'prices' => '',
    'adminId' => $_SESSION['user_id'] ?? null, // Assuming admin's userId is in session
    'hotelId' => null,
    'airlineId' => null
];

if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    $edit_mode = true;
    $agreement_id_to_edit = (int)$_GET['edit_id'];
    $page_title = "Edit Agreement";

    try {
        $stmt = $pdo->prepare("SELECT agreementNumber, startDate, endDate, terms, prices, adminId, hotelId, airlineId FROM Agreement WHERE agreementId = ?");
        $stmt->execute([$agreement_id_to_edit]);
        $fetched_agreement = $stmt->fetch();
        if ($fetched_agreement) {
            $agreement_data = $fetched_agreement;
        } else {
            $_SESSION['error_message'] = "Agreement with ID ".htmlspecialchars($agreement_id_to_edit)." not found.";
            header("Location: manage_agreements.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching agreement for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Error fetching agreement data. Please try again.";
        header("Location: manage_agreements.php");
        exit;
    }
} else {
    $page_title = "Add New Agreement";
    // Ensure adminId is set for new agreements if not already (e.g., if admin isn't User table)
    // For this example, assuming adminId comes from a logged-in admin user's session.
    // If you don't have admin users in the User table, you might need a different way to set adminId
    // or make it nullable if agreements can be system-generated.
    // For now, we'll assume $_SESSION['user_id'] holds the admin's ID if logged in as admin.
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') { // Basic check
        $_SESSION['error_message'] = "Admin user not identified. Cannot create agreement.";
        // header("Location: some_login_page.php"); // Or handle appropriately
        // exit;
        // For now, let's allow proceeding but adminId might be null if not set
        $agreement_data['adminId'] = 1; // Placeholder if no admin session
        // You MUST ensure adminId is valid and refers to an Admin in the User table.
    }
}

// --- Handle Form Submission (Add or Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token validation failed. Operation aborted.';
        header("Location: manage_agreements.php");
        exit;
    }

    $agreement_number = trim(htmlspecialchars($_POST['agreementNumber'] ?? '', ENT_QUOTES, 'UTF-8'));
    $start_date = trim($_POST['startDate'] ?? '');
    $end_date = trim($_POST['endDate'] ?? '');
    $terms = trim(htmlspecialchars($_POST['terms'] ?? '', ENT_QUOTES, 'UTF-8'));
    $prices = filter_var($_POST['prices'] ?? '', FILTER_VALIDATE_FLOAT);
    $admin_id = filter_var($_POST['adminId'] ?? $agreement_data['adminId'], FILTER_VALIDATE_INT); // Get from form or pre-filled
    $hotel_id = filter_var($_POST['hotelId'] ?? null, FILTER_VALIDATE_INT);
    $airline_id = filter_var($_POST['airlineId'] ?? null, FILTER_VALIDATE_INT);
    $posted_agreement_id = filter_var($_POST['agreement_id'] ?? null, FILTER_VALIDATE_INT);

    // Validation
    $errors = [];
    if (empty($agreement_number)) $errors[] = "Agreement Number is required.";
    if (empty($start_date)) $errors[] = "Start Date is required.";
    if (empty($end_date)) $errors[] = "End Date is required.";
    if ($prices === false || $prices < 0) $errors[] = "A valid, non-negative Price is required.";
    if (!$admin_id) $errors[] = "Admin ID is missing or invalid."; // Crucial for fk_Agreement_Admin
    // hotelId and airlineId can be NULL according to schema, but you might want to require one or both.
    // For this example, we'll allow them to be optional as per DB schema.

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        // Retain input data for the form by re-assigning to $agreement_data
        $agreement_data = $_POST; // Be careful with this, ensure all keys match
        if ($edit_mode && $posted_agreement_id) {
            header("Location: agreement_form.php?edit_id=" . $posted_agreement_id);
        } else {
            header("Location: agreement_form.php");
        }
        exit;
    }

    // Validate dates: endDate should not be before startDate
    if (strtotime($end_date) < strtotime($start_date)) {
        $_SESSION['error_message'] = "End Date cannot be before Start Date.";
        if ($edit_mode && $posted_agreement_id) {
            header("Location: agreement_form.php?edit_id=" . $posted_agreement_id);
        } else {
            header("Location: agreement_form.php");
        }
        exit;
    }


    try {
        if ($edit_mode && $posted_agreement_id && $posted_agreement_id == $agreement_id_to_edit) {
            $sql = "UPDATE Agreement SET agreementNumber = ?, startDate = ?, endDate = ?, terms = ?, prices = ?, adminId = ?, hotelId = ?, airlineId = ? WHERE agreementId = ?";
            $params = [$agreement_number, $start_date, $end_date, $terms, $prices, $admin_id, $hotel_id ?: null, $airline_id ?: null, $posted_agreement_id];
            $success_msg_action = "updated";
        } else {
            $sql = "INSERT INTO Agreement (agreementNumber, startDate, endDate, terms, prices, adminId, hotelId, airlineId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$agreement_number, $start_date, $end_date, $terms, $prices, $admin_id, $hotel_id ?: null, $airline_id ?: null];
            $success_msg_action = "added";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success_message'] = "Agreement \"".htmlspecialchars($agreement_number)."\" ".$success_msg_action." successfully!";
        header("Location: manage_agreements.php");
        exit;

    } catch (PDOException $e) {
        error_log("Database error in agreement_form.php: " . $e->getMessage());
        $error_detail = $e->getMessage();
        if (strpos($e->getCode(), '23000') !== false) { // Integrity constraint violation
            if (strpos(strtolower($error_detail), 'unique') !== false && strpos(strtolower($error_detail), 'agreementnumber') !== false) {
                $error_detail = "Agreement Number '".htmlspecialchars($agreement_number)."' already exists.";
            } elseif (strpos(strtolower($error_detail), 'foreign key constraint fails') !== false) {
                 if (strpos(strtolower($error_detail), 'fk_agreement_admin') !== false) {
                    $error_detail = "Invalid Admin ID provided.";
                 } else if (strpos(strtolower($error_detail), 'fk_agreement_hotel') !== false) {
                    $error_detail = "Invalid Hotel ID selected.";
                 } else if (strpos(strtolower($error_detail), 'fk_agreement_airline') !== false) {
                    $error_detail = "Invalid Airline ID selected.";
                 } else {
                    $error_detail = "A related record (Admin, Hotel, or Airline) does not exist.";
                 }
            } else {
                $error_detail = "A database integrity error occurred.";
            }
        }
        $_SESSION['error_message'] = "Database error: " . $error_detail;

        // Retain input for form repopulation on error
        $_SESSION['form_data_agreement'] = $_POST;

        if ($edit_mode && $agreement_id_to_edit) {
            header("Location: agreement_form.php?edit_id=" . $agreement_id_to_edit);
        } else {
            header("Location: agreement_form.php");
        }
        exit;
    }
}

// Repopulate form with session data if an error occurred during POST
if (isset($_SESSION['form_data_agreement'])) {
    $agreement_data = array_merge($agreement_data, $_SESSION['form_data_agreement']);
    unset($_SESSION['form_data_agreement']);
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
        <form action="agreement_form.php<?php echo $edit_mode ? '?edit_id='.$agreement_id_to_edit : ''; ?>" method="POST" class="styled-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="agreement_id" value="<?php echo htmlspecialchars($agreement_id_to_edit); ?>">
            <?php endif; ?>
             <input type="hidden" name="adminId" value="<?php echo htmlspecialchars($agreement_data['adminId']); ?>">


            <div class="form-group">
                <label for="agreementNumber">Agreement Number <span class="required">*</span></label>
                <input type="text" id="agreementNumber" name="agreementNumber" required
                       value="<?php echo htmlspecialchars($agreement_data['agreementNumber']); ?>" <?php echo $edit_mode ? '' : ''; /* readonly if you want */ ?>>
            </div>

            <div class="form-group-row"> <!-- For side-by-side date inputs -->
                <div class="form-group">
                    <label for="startDate">Start Date <span class="required">*</span></label>
                    <input type="date" id="startDate" name="startDate" required
                           value="<?php echo htmlspecialchars($agreement_data['startDate']); ?>">
                </div>
                <div class="form-group">
                    <label for="endDate">End Date <span class="required">*</span></label>
                    <input type="date" id="endDate" name="endDate" required
                           value="<?php echo htmlspecialchars($agreement_data['endDate']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="hotelId">Select Hotel (Optional)</label>
                <select id="hotelId" name="hotelId">
                    <option value="">-- None --</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?php echo htmlspecialchars($hotel['hotelId']); ?>"
                            <?php echo ($agreement_data['hotelId'] == $hotel['hotelId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($hotel['name']); ?> (ID: <?php echo htmlspecialchars($hotel['hotelId']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="airlineId">Select Airline (Optional)</label>
                <select id="airlineId" name="airlineId">
                    <option value="">-- None --</option>
                    <?php foreach ($airlines as $airline): ?>
                        <option value="<?php echo htmlspecialchars($airline['airlineId']); ?>"
                            <?php echo ($agreement_data['airlineId'] == $airline['airlineId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($airline['name']); ?> (ID: <?php echo htmlspecialchars($airline['airlineId']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="prices">Price/Rate <span class="required">*</span></label>
                <input type="number" id="prices" name="prices" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($agreement_data['prices']); ?>">
            </div>

            <div class="form-group">
                <label for="terms">Terms & Conditions</label>
                <textarea id="terms" name="terms" rows="6"><?php echo htmlspecialchars($agreement_data['terms']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-admin">
                    <?php echo $edit_mode ? 'Update Agreement' : 'Save Agreement'; ?>
                </button>
                <a href="manage_agreements.php" class="btn-secondary-admin">Cancel</a>
            </div>
        </form>
    </section>
</main>

<?php
include_once '../includes/footer.php';
?>