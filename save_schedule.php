<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $room = $_POST['room'] ?? '';
    $late_tolerance = $_POST['late_tolerance'] ?? 15;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $pic_id = $_POST['pic_id'] ?? '';
    $participants = $_POST['participants'] ?? [];

    if (!$title || !$room || !$date || !$time || !$end_time || !$pic_id || !is_numeric($late_tolerance)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    $scheduled_time = $date . ' ' . $time . ':00';
    $scheduled_end_time = $date . ' ' . $end_time . ':00';
    $token = bin2hex(random_bytes(16));

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO meetings (title, room, scheduled_time, end_time, late_tolerance, token, created_by, pic_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $room, $scheduled_time, $scheduled_end_time, $late_tolerance, $token, $_SESSION['user_id'], $pic_id]);
        
        $meeting_id = $pdo->lastInsertId();

        // Add PIC as participant automatically
        $stmt_part = $pdo->prepare("INSERT INTO meeting_participants (meeting_id, user_id) VALUES (?, ?)");
        $stmt_part->execute([$meeting_id, $pic_id]);

        if (!empty($participants)) {
            foreach ($participants as $uid) {
                // Ensure we don't duplicate PIC if they were somehow selected
                if ($uid != $pic_id) {
                    $stmt_part->execute([$meeting_id, $uid]);
                }
            }
        }

        $pdo->commit();
        
        $attendance_link = "http://" . $_SERVER['HTTP_HOST'] . "/attendance.php?token=" . $token;
        
        echo json_encode([
            'success' => true, 
            'link' => $attendance_link,
            'title' => $title,
            'room' => $room
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
