<?php
// File: packages/detail.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get package ID and fetch details
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM TripPackage WHERE packageId = ?');
$stmt->execute([$id]);
$package = $stmt->fetch();

if (!$package) {
    echo '<p class="error">Package not found.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Determine image URL
$imageFile = htmlspecialchars($package['image'] ?? 'amesterdam.jpg');
$imageUrl  = "../public/images/{$imageFile}";
?>

<main class="detail-container">
  <!-- IMAGE -->
  <div class="package-photo">
    <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($package['name']) ?>">
  </div>

  <!-- DETAILS -->
  <div class="details">
    <h2>Trip Details</h2>
    <ul class="info-list">
      <li><strong>Destination:</strong> <?= htmlspecialchars($package['destination']) ?></li>
      <li><strong>Type:</strong> <?= htmlspecialchars($package['type']) ?></li>
      <li><strong>Duration:</strong> <?= (int)$package['duration'] ?> days</li>
      <li><strong>Start Date:</strong> <?= htmlspecialchars($package['startDate']) ?></li>
      <li><strong>End Date:</strong> <?= htmlspecialchars($package['endDate']) ?></li>
      <li><strong>Price:</strong> $<?= number_format($package['price'], 2) ?></li>
      <li><strong>Availability:</strong> <?= htmlspecialchars($package['availability'] ?? 'N/A') ?></li>
      <li><strong>Amenities:</strong><br><?= nl2br(htmlspecialchars($package['amenities'] ?? 'None')) ?></li>
    </ul>
  </div>

  <!-- BOOKING -->
  <div class="booking-form">
    <h2>Ready to Book?</h2>
    <form action="book.php" method="post">
      <input type="hidden" name="packageId" value="<?= (int)$package['packageId'] ?>">

      <label for="people">Number of People</label>
      <input type="number" id="people" name="numberOfPeople" min="1" value="1" required>

      <label for="date">Travel Date</label>
      <input type="date" id="date" name="travelDate" required>

      <button type="submit" class="btn booking-btn">Book Now</button>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
