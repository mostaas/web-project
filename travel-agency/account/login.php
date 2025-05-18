<!-- File: login.php (project root) -->
<?php
// It's generally better to put all PHP logic BEFORE any HTML output,
// especially if you're doing redirects.
// So, let's move the require_once for db.php to the very top.
require_once __DIR__ . '/../includes/db.php'; // This starts the session

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) { // Simplified check
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        // Fetch user data
        try {
            $stmt = $pdo->prepare('SELECT userId, username, passwordHash, role FROM `User` WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Username not found.';
            } elseif (!password_verify($password, $user['passwordHash'])) {
                $errors[] = 'Incorrect password.';
            } else {
                // --- SUCCESSFUL LOGIN ---
                // Regenerate session ID for security after login
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['userId'];
                $_SESSION['username'] = $user['username']; // Good to store username too
                $_SESSION['user_role'] = $user['role'];   // ***** ADD THIS LINE *****

                // Generate a new CSRF token after login for added security
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


                if ($user['role'] === 'Admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../public/index.php'); // Or user's dashboard if they have one
                }
                exit;
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors[] = "A database error occurred. Please try again.";
        }
    }
}

// Now include the header, as all potential redirects are done.
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
  <h2>Log In</h2>

  <?php if (!empty($errors)): // Check if $errors array is not empty ?>
    <div class="errors" style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">
      <p><strong>Please correct the following errors:</strong></p>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="login.php"> <!-- Assuming login.php is in the project root -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn">Log In</button>
  </form>

  <p class="switch-auth">
    Don’t have an account? <a href="register.php">Sign up here</a>.
  </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
