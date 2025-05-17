<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    // Gather & validate inputs
    $username = trim($_POST['username']  ?? '');
    $fullName = trim($_POST['fullName']  ?? '');
    $address  = trim($_POST['address']   ?? '');
    $phone    = trim($_POST['phone']     ?? '');
    $password =             $_POST['password'] ?? '';

    if (!$username || !$fullName || !$address || !$phone || !$password) {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        // Check duplicate username
        $stmt = $pdo->prepare('SELECT userId FROM `User` WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already taken.';
        } else {
            // Insert new user with default role Client
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare(
                'INSERT INTO `User` (username, passwordHash, role) VALUES (?, ?, ?)'
            );
            $ins->execute([$username, $hash, 'Client']);
            $newUserId = $pdo->lastInsertId();

            // Insert profile data
            $prof = $pdo->prepare(
                'INSERT INTO `Profile` (userId, fullName, address, phone) VALUES (?, ?, ?, ?)'
            );
            $prof->execute([$newUserId, $fullName, $address, $phone]);

            // Log in user
            $_SESSION['user_id'] = $newUserId;
            header('Location: /public/index.php');
            exit;
        }
    }
}
?>

<div class="auth-container">
  <h2>Create Account</h2>

  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="/register.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <label>Username
      <input type="text" name="username" required>
    </label>
    <label>Full Name
      <input type="text" name="fullName" required>
    </label>
    <label>Address
      <input name="address" name="address" required></input>
    </label>
    <label>Phone
      <input type="tel" name="phone" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit">Sign Up</button>
  </form>

  <p class="switch-auth">
    Already have an account? <a href="/login.php">Log in here</a>.
  </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
