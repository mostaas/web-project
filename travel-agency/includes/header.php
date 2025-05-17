<?php
// File: includes/header.php
// Session started in db.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Travel Agency</title>
  <!-- CSRF token for JS if needed -->
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
  <link rel="stylesheet" href="../public/css/styles.css">
  <script src="/js/scripts.js" defer></script>
</head>
<body>
  <header class="navbar">
    <div class="logo"><a href="/public/index.php">Travel Agency</a></div>
    <nav>
      <ul class="nav-links">
        <li><a href="/public/index.php">Home</a></li>
        <li><a href="/public/packages/list.php">Packages</a></li>
        <li><a href="/public/feedback/form.php">Feedback</a></li>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li><a href="/public/account/reservations.php">My Reservations</a></li>
          <li><a href="/logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="../login.php">Login</a></li>
          <li><a href="../register.php">Register</a></li>
        <?php endif; ?>
        <li><a href="/public/contact.php">Contact</a></li>
      </ul>
    </nav>
  </header>
  <main>
