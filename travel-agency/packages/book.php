<?php
// File: packages/book.php

require_once __DIR__ . '/../includes/db.php';

// 1) Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$errors = [];

// 2) Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $packageId      = isset($_POST['packageId'])      ? (int)$_POST['packageId']      : 0;
    $numberOfPeople = isset($_POST['numberOfPeople']) ? (int)$_POST['numberOfPeople'] : 0;
    $travelDate     = trim($_POST['travelDate'] ?? '');

    if (!$packageId) {
        $errors[] = 'Invalid package selection.';
    }
    if ($numberOfPeople < 1) {
        $errors[] = 'You must book for at least one person.';
    }
    if (!$travelDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) {
        $errors[] = 'Please choose a valid travel date.';
    }

    // If no errors, insert reservation
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO Reservation
                    (clientId, packageId, numberOfPeople, status, travelDate)
                 VALUES
                    (?, ?, ?, ?, ?)'
            );
            $clientId = $_SESSION['user_id'];
            $stmt->execute([
                $clientId,
                $packageId,
                $numberOfPeople,
                'Pending',      // default status
                $travelDate
            ]);

            // Redirect to “My Reservations” or a confirmation
             header('Location: ../account/reservations.php');
            exit;
        } catch (PDOException $e) {
            error_log('Booking error: ' . $e->getMessage());
            $errors[] = 'Unable to complete booking. Please try again later.';
        }
    }
}

// If we fall through, something went wrong: display errors
require_once __DIR__ . '/../includes/header.php';
?>

<main class="packages-main">
  <h2>Booking Error</h2>
  <div class="errors">
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <p><a href="/packages/detail.php?id=<?= $packageId ?>">Return to package details</a></p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
