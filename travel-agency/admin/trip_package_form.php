<?php
// --- Start Session (if not already done in db.php, db.php should be included first) ---
include_once '../includes/db.php'; // This already starts the session

// --- FORM PROCESSING LOGIC MOVED TO THE TOP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token validation failed. Operation aborted.';
        // IMPORTANT: Redirect from here. Do NOT proceed to include header/sidebar if there's an error.
        header("Location: manage_trip_package.php"); // Or back to the form itself if you want to show error there
        exit;
    }

    // Sanitize and retrieve all form data
    $package_name = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
    // ... (all other $_POST variable assignments and sanitization) ...
    $posted_package_id = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);

    // Validation
    $errors = [];
    if (empty($package_name)) $errors[] = "Package Name is required.";
    // ... (all other validation checks) ...

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        $_SESSION['form_data_package'] = $_POST;
        // Determine if it was an edit to redirect back correctly
        $redirect_url = "trip_package_form.php";
        if (isset($_POST['package_id']) && filter_var($_POST['package_id'], FILTER_VALIDATE_INT)) {
            $redirect_url .= "?edit_id=" . $_POST['package_id'];
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // Date validation
    if (strtotime($_POST['endDate']) < strtotime($_POST['startDate'])) {
        $_SESSION['error_message'] = "End Date cannot be before Start Date.";
        $_SESSION['form_data_package'] = $_POST;
        $redirect_url = "trip_package_form.php";
        if (isset($_POST['package_id']) && filter_var($_POST['package_id'], FILTER_VALIDATE_INT)) {
            $redirect_url .= "?edit_id=" . $_POST['package_id'];
        }
        header("Location: " . $redirect_url);
        exit;
    }
    
    // Re-assign sanitized values for DB operation
    $destination = trim(htmlspecialchars($_POST['destination'] ?? '', ENT_QUOTES, 'UTF-8'));
    $start_date = trim($_POST['startDate'] ?? '');
    $end_date = trim($_POST['endDate'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $duration = filter_var($_POST['duration'] ?? '', FILTER_VALIDATE_INT);
    $amenities = trim(htmlspecialchars($_POST['amenities'] ?? '', ENT_QUOTES, 'UTF-8'));
    $type = trim(htmlspecialchars($_POST['type'] ?? '', ENT_QUOTES, 'UTF-8'));
    $availability = trim(htmlspecialchars($_POST['availability'] ?? '', ENT_QUOTES, 'UTF-8'));
    $agreement_id = filter_var($_POST['agreementId'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $hotel_id = filter_var($_POST['hotelId'] ?? null, FILTER_VALIDATE_INT);
    $airline_id = filter_var($_POST['airlineId'] ?? null, FILTER_VALIDATE_INT);


    try {
        // Check if $posted_package_id is set and is numeric for edit mode check
        $is_edit_from_post = (isset($_POST['package_id']) && filter_var($_POST['package_id'], FILTER_VALIDATE_INT));

        if ($is_edit_from_post) {
            $sql = "UPDATE TripPackage SET name = ?, destination = ?, startDate = ?, endDate = ?, price = ?, duration = ?, amenities = ?, type = ?, availability = ?, agreementId = ?, hotelId = ?, airlineId = ? WHERE packageId = ?";
            $params = [$package_name, $destination, $start_date, $end_date, $price, $duration, $amenities, $type, $availability, $agreement_id, $hotel_id, $airline_id, $posted_package_id];
            $success_msg_action = "updated";
        } else {
            $sql = "INSERT INTO TripPackage (name, destination, startDate, endDate, price, duration, amenities, type, availability, agreementId, hotelId, airlineId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$package_name, $destination, $start_date, $end_date, $price, $duration, $amenities, $type, $availability, $agreement_id, $hotel_id, $airline_id];
            $success_msg_action = "added";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success_message'] = "Trip Package \"".htmlspecialchars($package_name)."\" ".$success_msg_action." successfully!";
        header("Location: manage_trip_package.php");
        exit;

    } catch (PDOException $e) {
        error_log("Database error in trip_package_form.php: " . $e->getMessage());
        // ... (your existing detailed PDOException error handling) ...
        $_SESSION['error_message'] = "Database error: " . $e->getMessage(); // Simplified for brevity
        $_SESSION['form_data_package'] = $_POST;
        $redirect_url = "trip_package_form.php";
        if (isset($_POST['package_id']) && filter_var($_POST['package_id'], FILTER_VALIDATE_INT)) {
            $redirect_url .= "?edit_id=" . $_POST['package_id'];
        }
        header("Location: " . $redirect_url);
        exit;
    }
}
// --- END OF FORM PROCESSING LOGIC ---


// --- Page Setup Variables (now after potential redirects) ---
$current_admin_page = 'packages';
$page_title = "Trip Package Form"; // Will be overridden if in edit mode below

// --- Fetch Hotels, Airlines, and Agreements for dropdowns (can stay here) ---
$hotels = [];
$airlines = [];
$agreements = [];
try {
    // ... (your existing fetch queries for $hotels, $airlines, $agreements) ...
    $stmt_hotels = $pdo->query("SELECT hotelId, name FROM Hotel ORDER BY name ASC");
    $hotels = $stmt_hotels->fetchAll();

    $stmt_airlines = $pdo->query("SELECT airlineId, name FROM Airline ORDER BY name ASC");
    $airlines = $stmt_airlines->fetchAll();

    $stmt_agreements = $pdo->query("
        SELECT agreementId, agreementNumber, 
               COALESCE(h.name, 'N/A') as hotelName, 
               COALESCE(al.name, 'N/A') as airlineName,
               a.startDate, a.endDate
        FROM Agreement a
        LEFT JOIN Hotel h ON a.hotelId = h.hotelId
        LEFT JOIN Airline al ON a.airlineId = al.airlineId
        ORDER BY a.agreementNumber ASC
    ");
    $agreements = $stmt_agreements->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching data for trip package form: " . $e->getMessage());
    $_SESSION['error_message'] = "Could not load necessary data (Hotels, Airlines, Agreements). Please try again.";
}


// --- Determine Mode: Add or Edit (can stay here, for displaying the form) ---
$edit_mode = false;
$package_id_to_edit = null;
$package_data = [ /* ... default values ... */
    'name' => '', 'destination' => '', 'startDate' => '', 'endDate' => '',
    'price' => '', 'duration' => '', 'amenities' => '', 'type' => '',
    'availability' => '', 'agreementId' => null, 'hotelId' => null, 'airlineId' => null,
];

if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT)) {
    // ... (your existing logic to fetch $package_data for edit mode) ...
    // This part only runs if it's NOT a POST request (i.e., initial load of edit form)
    $edit_mode = true;
    $package_id_to_edit = (int)$_GET['edit_id'];
    $page_title = "Edit Trip Package"; // Override page title

    try {
        $stmt = $pdo->prepare("SELECT * FROM TripPackage WHERE packageId = ?");
        $stmt->execute([$package_id_to_edit]);
        $fetched_package = $stmt->fetch();
        if ($fetched_package) {
            $package_data = $fetched_package;
        } else {
            // This error message will be shown on manage_trip_package.php after redirect
            $_SESSION['error_message'] = "Trip Package with ID ".htmlspecialchars($package_id_to_edit)." not found.";
            header("Location: manage_trip_package.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching package for edit: " . $e->getMessage());
        $_SESSION['error_message'] = "Error fetching trip package data. Please try again.";
        header("Location: manage_trip_package.php");
        exit;
    }
} else {
    $page_title = "Add New Trip Package"; // Default title for add mode
}

// Repopulate form with session data if an error occurred during a previous POST
if (isset($_SESSION['form_data_package'])) {
    $package_data = array_merge($package_data, $_SESSION['form_data_package']);
    unset($_SESSION['form_data_package']);
}

// --- NOW INCLUDE HEADERS AND START HTML OUTPUT ---
include_once '../includes/header.php';
include_once 'admin_sidebar.php';
?>

<main class="admin-main-content">
    <header class="main-content-header">
        <h2 class="content-title"><?php echo htmlspecialchars($page_title); ?></h2>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="admin-message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="admin-message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <section class="admin-form-section">
        <form action="trip_package_form.php<?php echo $edit_mode ? '?edit_id='.htmlspecialchars($package_id_to_edit) : ''; ?>" method="POST" class="styled-form">
            <!-- ... rest of your form HTML ... -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($package_id_to_edit); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Package Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($package_data['name']); ?>">
            </div>
            <div class="form-group">
                <label for="destination">Destination <span class="required">*</span></label>
                <input type="text" id="destination" name="destination" required value="<?php echo htmlspecialchars($package_data['destination']); ?>">
            </div>

            <div class="form-group-row">
                <div class="form-group">
                    <label for="startDate">Start Date <span class="required">*</span></label>
                    <input type="date" id="startDate" name="startDate" required value="<?php echo htmlspecialchars($package_data['startDate']); ?>">
                </div>
                <div class="form-group">
                    <label for="endDate">End Date <span class="required">*</span></label>
                    <input type="date" id="endDate" name="endDate" required value="<?php echo htmlspecialchars($package_data['endDate']); ?>">
                </div>
            </div>

            <div class="form-group-row">
                <div class="form-group">
                    <label for="price">Price <span class="required">*</span></label>
                    <div class="input-with-symbol">
                        <span class="input-symbol">$</span>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($package_data['price']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="duration">Duration (days) <span class="required">*</span></label>
                    <input type="number" id="duration" name="duration" min="1" required value="<?php echo htmlspecialchars($package_data['duration']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="hotelId">Select Hotel <span class="required">*</span></label>
                <select id="hotelId" name="hotelId" required>
                    <option value="">-- Select Hotel --</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?php echo htmlspecialchars($hotel['hotelId']); ?>"
                            <?php echo ($package_data['hotelId'] == $hotel['hotelId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($hotel['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="airlineId">Select Airline <span class="required">*</span></label>
                <select id="airlineId" name="airlineId" required>
                    <option value="">-- Select Airline --</option>
                    <?php foreach ($airlines as $airline): ?>
                        <option value="<?php echo htmlspecialchars($airline['airlineId']); ?>"
                            <?php echo ($package_data['airlineId'] == $airline['airlineId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($airline['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="agreementId">Select Agreement (Optional)</label>
                <select id="agreementId" name="agreementId">
                    <option value="">-- None --</option>
                    <?php foreach ($agreements as $agreement): ?>
                        <option value="<?php echo htmlspecialchars($agreement['agreementId']); ?>"
                            <?php echo ($package_data['agreementId'] == $agreement['agreementId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agreement['agreementNumber']); ?>
                            (H: <?php echo htmlspecialchars($agreement['hotelName']); ?>, A: <?php echo htmlspecialchars($agreement['airlineName']); ?>)
                            [<?php echo htmlspecialchars(date("M Y", strtotime($agreement['startDate']))); ?> - <?php echo htmlspecialchars(date("M Y", strtotime($agreement['endDate']))); ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="type">Package Type <span class="required">*</span> (e.g., Leisure, Adventure, Family)</label>
                <input type="text" id="type" name="type" required value="<?php echo htmlspecialchars($package_data['type']); ?>">
            </div>

            <div class="form-group">
                <label for="availability">Availability <span class="required">*</span> (e.g., Limited Seats, Available All Year)</label>
                <input type="text" id="availability" name="availability" required value="<?php echo htmlspecialchars($package_data['availability']); ?>">
            </div>
            
            <div class="form-group">
                <label for="amenities">Package Amenities (comma-separated or one per line)</label>
                <textarea id="amenities" name="amenities" rows="5"><?php echo htmlspecialchars($package_data['amenities']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-admin">
                    <?php echo $edit_mode ? 'Update Package' : 'Save Package'; ?>
                </button>
                <a href="manage_trip_package.php" class="btn-secondary-admin">Cancel</a>
            </div>
        </form>
    </section>
</main>

<?php
include_once '../includes/footer.php';
?>