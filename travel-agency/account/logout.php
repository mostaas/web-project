<!-- File: logout.php (project root) -->
<?php
require_once __DIR__ . '/../includes/db.php';
// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();
header('Location: ../public/index.php');
exit;
?>
