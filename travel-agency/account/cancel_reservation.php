<?php
// File: account/cancel_reservation.php

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservationId'])) {
    $reservationId = (int) $_POST['reservationId'];
    $clientId = $_SESSION['user_id'] ?? 0;

    // Verify ownership
    $stmt = $pdo->prepare('SELECT * FROM Reservation WHERE reservationId = ? AND clientId = ?');
    $stmt->execute([$reservationId, $clientId]);
    $reservation = $stmt->fetch();

    if ($reservation && $reservation['status'] !== 'Cancelled') {
        $update = $pdo->prepare('UPDATE Reservation SET status = ? WHERE reservationId = ?');
        $update->execute(['Cancelled', $reservationId]);
    }
}

header('Location: reservations.php');
exit;
