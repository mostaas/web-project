<?php
// File: public/index.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$browseUrl = !empty($_SESSION['user_id']) ? '../packages/list.php' : '../account/login.php';
?>

<main class="home-main">
  <section class="hero-section">
    <div class="hero-text">
      <h1>Explore the World</h1>
      <p>Unforgettable journeys to stunning destinations.</p>
      <a href="<?= htmlspecialchars($browseUrl) ?>" class="btn hero-btn">Browse Packages</a>
    </div>
  </section>

  <section class="highlights">
    <h2>Popular Destinations</h2>
    <div class="destination-grid">
      <div class="destination-card">
        <img src="images/singapore.jpg" alt="Singapore">
        <h3>Singapore</h3>
      </div>
      <div class="destination-card">
        <img src="images/dubai.jpg" alt="Dubai">
        <h3>Dubai</h3>
      </div>
      <div class="destination-card">
        <img src="images/beach2.jpg" alt="Beach Paradise">
        <h3>Beach Paradise</h3>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
