<!-- File: packages/detail.php -->
<?php
include '../includes/db.php';
include '../includes/header.php';

// Get package ID and fetch details
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $mysqli->prepare("SELECT * FROM TripPackage WHERE packageId = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();
if (!$package) {
    echo "<p class=\"error\">Package not found.</p>";
    include '../includes/footer.php';
    exit;
}
?>

<!-- Hero Section -->
<section class="hero-detail" style="background-image: url('<?= htmlspecialchars($package['image_url'] ?? '/images/default.jpg') ?>')">
  <div class="overlay">
    <h1><?= htmlspecialchars($package['name']) ?></h1>
    <p><?= htmlspecialchars($package['destination']) ?></p>
  </div>
</section>

<main class="detail-container">
  <div class="details">
    <h2>Trip Details</h2>
    <ul class="info-list">
      <li><strong>Duration:</strong> <?= $package['duration'] ?> days</li>
      <li><strong>Start Date:</strong> <?= $package['startDate'] ?></li>
      <li><strong>End Date:</strong> <?= $package['endDate'] ?></li>
      <li><strong>Price:</strong> $<?= number_format($package['price'],2) ?></li>
      <li><strong>Amenities:</strong> <?= nl2br(htmlspecialchars($package['amenities'])) ?></li>
      <li><strong>Type:</strong> <?= htmlspecialchars($package['type']) ?></li>
    </ul>
  </div>

  <div class="booking-form">
    <h2>Book This Package</h2>
    <form action="book.php" method="post">
      <input type="hidden" name="packageId" value="<?= $package['packageId'] ?>">
      <label for="people">Number of People</label>
      <input type="number" name="numberOfPeople" id="people" min="1" value="1" required>

      <label for="date">Travel Date</label>
      <input type="date" name="travelDate" id="date" required>

      <button type="submit" class="btn">Book Now</button>
    </form>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
