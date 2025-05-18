<?php
// File: account/reservations.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 1) Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$clientId = $_SESSION['user_id'];

// 2) Handle filter
$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['Pending', 'Confirmed', 'Cancelled'];

$query = 'SELECT r.reservationId, r.numberOfPeople, r.status, r.bookingDate, r.travelDate,
                 p.name AS packageName, p.destination
            FROM Reservation AS r
       LEFT JOIN TripPackage AS p ON r.packageId = p.packageId
           WHERE r.clientId = ?';

$params = [$clientId];

if (in_array($statusFilter, $allowedStatuses)) {
    $query .= ' AND r.status = ?';
    $params[] = $statusFilter;
}

$query .= ' ORDER BY r.bookingDate DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
?>

<main class="packages-main">
  <h2>My Reservations</h2>

  <!-- Filter Dropdown -->
  <form method="get" class="reservation-filter">
    <label for="status">Filter by Status:</label>
    <select name="status" id="status" onchange="this.form.submit()">
      <option value="">All</option>
      <?php foreach ($allowedStatuses as $status): ?>
        <option value="<?= $status ?>" <?= $status === $statusFilter ? 'selected' : '' ?>>
          <?= $status ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if (empty($reservations)): ?>
    <p>You have no reservations<?= $statusFilter ? ' with status "' . htmlspecialchars($statusFilter) . '"' : '' ?>.
       <a href="../packages/list.php">Browse packages</a>.
    </p>
  <?php else: ?>
    <div class="reservations-grid">
      <?php foreach ($reservations as $res): ?>
        <div class="reservation-card">
          <h3><?= htmlspecialchars($res['packageName']) ?></h3>
          <p><strong>Destination:</strong> <?= htmlspecialchars($res['destination']) ?></p>
          <p><strong>Booked On:</strong> <?= htmlspecialchars($res['bookingDate']) ?></p>
          <p><strong>Travel Date:</strong> <?= htmlspecialchars($res['travelDate']) ?></p>
          <p><strong>People:</strong> <?= (int)$res['numberOfPeople'] ?></p>
          <p><strong>Status:</strong> <?= htmlspecialchars($res['status']) ?></p>

          <div class="reservation-actions">
            <a href="../packages/detail.php?id=<?= (int)$res['reservationId'] ?>" class="btn details-btn">See Details</a>

            <?php if ($res['status'] !== 'Cancelled'): ?>
              <form method="post" action="cancel_reservation.php" class="cancel-form">
                <input type="hidden" name="reservationId" value="<?= (int)$res['reservationId'] ?>">
                <button type="submit" class="btn cancel-btn">Cancel</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
