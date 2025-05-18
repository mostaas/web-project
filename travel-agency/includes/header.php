<?php
// File: includes/header.php
// Session started in db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <div class="logo"><a href="../public/index.php">Travel Agency</a></div>
    <nav>
      <ul class="nav-links">
      
      <?php if (!empty($_SESSION['user_id'])): ?>
        <li><a href="../public/index.php">Home</a></li>
        <li><a href="../packages/list.php">Packages</a></li>
        <li><a href="../feedback/form.php">Feedback</a></li>
        <li><a href="../account/reservations.php">My Reservations</a></li>
        <li><a href="../public/contact.php">Contact</a></li>
        <li><a href="../account/logout.php">Logout</a></li>
      <?php endif; ?>
      <?php if (empty($_SESSION['user_id'])): ?>
        <li><a href="../public/index.php">Home</a></li>
        <li><a href="../account/login.php">Login</a></li>
        <li><a href="../account/register.php">Register</a></li>
        <li><a href="/public/contact.php">Contact</a></li>
      <?php endif; ?>
          
        
        
      </ul>
    </nav>
  </header>
  <main>
