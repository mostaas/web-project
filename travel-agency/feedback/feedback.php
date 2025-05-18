<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../account/login.php');
    exit;
}

$clientId = $_SESSION['user_id'];
$reservationId = isset($_GET['reservationId']) ? (int)$_GET['reservationId'] : 0;

// 1. Verify ownership and status = Done
$stmt = $pdo->prepare("SELECT * FROM Reservation WHERE reservationId = ? AND clientId = ? AND status = 'Done'");
$stmt->execute([$reservationId, $clientId]);
$res = $stmt->fetch();

if (!$res) {
    echo "<p class='error'>Invalid or unauthorized reservation.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelRating = (int)$_POST['hotelRating'];
    $hotelReview = trim($_POST['hotelReview']);
    $airlineRating = (int)$_POST['airlineRating'];
    $airlineReview = trim($_POST['airlineReview']);

    if ($hotelRating < 1 || $hotelRating > 5 || $airlineRating < 1 || $airlineRating > 5) {
        $errors[] = 'Ratings must be between 1 and 5.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO Feedback (reservationId, clientId, hotelRating, hotelReview, airlineRating, airlineReview)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $reservationId,
            $clientId,
            $hotelRating,
            $hotelReview,
            $airlineRating,
            $airlineReview
        ]);

        header('Location: ../account/reservations.php?feedback=1');
        exit;
    }
}
?>

<main class="packages-main">
  <h2>Leave Feedback</h2>

  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="feedback-form">
    <label>Hotel Rating (1–5):
      <input type="number" name="hotelRating" min="1" max="5" required>
    </label>

    <label>Hotel Review:
      <textarea name="hotelReview" required></textarea>
    </label>

    <label>Airline Rating (1–5):
      <input type="number" name="airlineRating" min="1" max="5" required>
    </label>

    <label>Airline Review:
      <textarea name="airlineReview" required></textarea>
    </label>

    <button type="submit" class="submit-btn">Submit Feedback</button>
  </form>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
