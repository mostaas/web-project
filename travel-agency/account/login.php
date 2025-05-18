<!-- File: login.php (project root) -->
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        // Fetch user data
        $stmt = $pdo->prepare('SELECT userId, passwordHash FROM `User` WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // Username not found
            $errors[] = 'Username not found.';
        } elseif (!password_verify($password, $user['passwordHash'])) {
            // Wrong password
            $errors[] = 'Incorrect password.';
        } else {
            // Successful login
            $_SESSION['user_id'] = $user['userId'];
            header('Location: ../public/index.php');
            exit;
        }
    }
}
?>

<div class="auth-container">
  <h2>Log In</h2>

  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <label>Username
      <input type="text" name="username" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit">Log In</button>
  </form>

  <p class="switch-auth">
    Don’t have an account? <a href="register.php">Sign up here</a>.
  </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
