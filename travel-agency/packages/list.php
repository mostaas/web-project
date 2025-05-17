<?php
// packages/list.php

// 1. Load DB connection and header
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 2. Fetch all packages via PDO
$stmt = $pdo->query('SELECT * FROM TripPackage');
$packages = $stmt->fetchAll();
?>

<main>
  <h2>Available Trip Packages</h2>
  <div class="card-grid">
    <?php foreach ($packages as $pkg): ?>
      <div class="card">
        <h3><?= htmlspecialchars($pkg['name']) ?></h3>
        <p><strong>Destination:</strong> <?= htmlspecialchars($pkg['destination']) ?></p>
        <p>
          <strong>Start:</strong> <?= htmlspecialchars($pkg['startDate']) ?>
          &nbsp;|&nbsp;
          <strong>End:</strong> <?= htmlspecialchars($pkg['endDate']) ?>
        </p>
        <p><strong>Price:</strong> $<?= number_format($pkg['price'], 2) ?></p>
        <p><strong>Duration:</strong> <?= (int)$pkg['duration'] ?> days</p>
        <a href="/packages/detail.php?id=<?= (int)$pkg['packageId'] ?>" class="btn">
          View Details
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<?php
// 3. Include the footer
require_once __DIR__ . '/../includes/footer.php';
?>
