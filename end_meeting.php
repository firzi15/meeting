<?php
session_start();
require_once 'database.php';

// Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_id = $_POST['meeting_id'] ?? null;
    
    if ($meeting_id) {
        try {
            // Set end_time to now
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE meetings SET end_time = ? WHERE id = ?");
            $stmt->execute([$now, $meeting_id]);
        } catch (Exception $e) {
            die("Gagal mengakhiri meeting: " . $e->getMessage());
        }
    }
}

// Redirect back to report page
header("Location: report.php");
exit;
?>
