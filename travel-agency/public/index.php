<?php
// File: public/index.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// Determine URL for Browse Packages button based on login state
$browseUrl = !empty($_SESSION['user_id'])
    ? '/packages/list.php'
    : '/login.php';
?>

<main>
  <h2>Explore Our Travel Packages</h2>
  <p>Book amazing trips to your dream destinations with our agency.</p>
  <a href="<?= htmlspecialchars($browseUrl) ?>" class="btn">Browse Packages</a>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
