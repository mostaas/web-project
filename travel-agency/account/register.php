<!-- File: register.php (project root) -->
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token.';
    }

    // Gather & validate
    $username = trim($_POST['username']  ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
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
            $_SESSION['user_id'] = $pdo->lastInsertId();
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

