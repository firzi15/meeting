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
            $pdo->beginTransaction();
            
            // Delete child records first
            $stmt1 = $pdo->prepare("DELETE FROM attendances WHERE meeting_id = ?");
            $stmt1->execute([$meeting_id]);
            
            $stmt2 = $pdo->prepare("DELETE FROM meeting_participants WHERE meeting_id = ?");
            $stmt2->execute([$meeting_id]);
            
            // Delete parent record
            $stmt3 = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
            $stmt3->execute([$meeting_id]);
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Gagal menghapus jadwal: " . $e->getMessage());
        }
    }
}

// Redirect back to report page
header("Location: report.php");
exit;
?>
