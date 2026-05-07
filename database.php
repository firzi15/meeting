<?php
// database.php
date_default_timezone_set('Asia/Jakarta');

// Database configuration from environment variables (Docker)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'meeting_db';
$user = getenv('DB_USER') ?: 'admin';
$pass = getenv('DB_PASS') ?: 'admin123';

try {
    // Connect to PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    
    // Set errormode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
