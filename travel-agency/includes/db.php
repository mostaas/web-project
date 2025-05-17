<?php
// File: includes/db.php

// Display errors during development (disable or conditionally switch off in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session (for authentication & CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load DB credentials from environment or fall back to local defaults
$host     = getenv('DB_HOST')    ?: 'localhost';
$port     = getenv('DB_PORT')    ?: '3306';
$dbName   = getenv('DB_NAME')    ?: 'travel_agency';
$user     = getenv('DB_USER')    ?: 'root';
$password = getenv('DB_PASS')    ?: '';

// Build DSN with utf8mb4 charset
$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // Log full error server-side
    error_log('DB Connection Error: ' . $e->getMessage());
    // Show generic message to user
    http_response_code(500);
    echo "<h1>Internal Server Error</h1><p>Unable to connect to database.</p>";
    exit;
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
