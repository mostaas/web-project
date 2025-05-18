<?php
// File: packages/list.php
// 1. Load DB connection and header
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 2. Fetch all packages via PDO
$stmt = $pdo->query('SELECT * FROM TripPackage');
$packages = $stmt->fetchAll();
?>

<main class="packages-main">
  <h2>Available Trip Packages</h2>
  <div class="packages-grid">
    <?php foreach ($packages as $pkg): ?>
  <div class="package-card">
    <!-- Replace this line: -->
    <!-- <div class="package-img" style="background-image: url('../public/images/paris.jpg') ?>');"></div> -->

    <!-- With this dynamic version: -->
    <div
      class="package-img"
      style="background-image: url('../public/images/<?= htmlspecialchars($pkg['image'] ?? 'paris.jpg') ?>');"
    ></div>

    <div class="package-info">
  <h3><?= htmlspecialchars($pkg['name']) ?></h3>
  <p><?= htmlspecialchars($pkg['destination']) ?></p>
  <p>
    <strong>From:</strong> <?= htmlspecialchars($pkg['startDate']) ?>
    <strong>To:</strong> <?= htmlspecialchars($pkg['endDate']) ?>
  </p>
  <p><strong>Price:</strong> $<?= number_format($pkg['price'], 2) ?></p>

  <div class="package-actions">
    <!-- See More Details -->
    <a
      href="detail.php?id=<?= (int)$pkg['packageId'] ?>"
      class="btn details-btn"
    >See More Details</a>

    <!-- Book Now -->
    <a
      href="detail.php?id=<?= (int)$pkg['packageId'] ?>"
      class="btn package-btn"
    >Book Now</a>
  </div>
</div>
  </div>
<?php endforeach; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
