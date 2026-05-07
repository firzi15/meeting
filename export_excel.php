<?php
session_start();
require_once 'database.php';

// Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$type = $_GET['type'] ?? 'summary';
$filename = "laporan_meeting_" . date('Ymd_His') . ".csv";

// Output headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'summary') {
    // Mode: Rekap Daftar Meeting
    $room_filter = $_GET['room'] ?? '';
    
    // Header CSV
    fputcsv($output, ['Tanggal', 'Judul Meeting', 'Ruangan', 'Jam Mulai', 'Jam Selesai', 'Token']);

    $where = "";
    $params = [];
    if ($room_filter) {
        $where = " WHERE room = ? ";
        $params[] = $room_filter;
    }

    $stmt = $pdo->prepare("SELECT * FROM meetings" . $where . " ORDER BY scheduled_time DESC");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            date('Y-m-d', strtotime($row['scheduled_time'])),
            $row['title'],
            $row['room'],
            date('H:i', strtotime($row['scheduled_time'])),
            date('H:i', strtotime($row['end_time'])),
            $row['token']
        ]);
    }

} elseif ($type === 'detail') {
    // Mode: Detail Absensi per Meeting
    $meeting_id = $_GET['id'] ?? null;
    if (!$meeting_id) die("Meeting ID tidak valid.");

    // Ambil data meeting
    $stmt_m = $pdo->prepare("SELECT title, scheduled_time FROM meetings WHERE id = ?");
    $stmt_m->execute([$meeting_id]);
    $meeting = $stmt_m->fetch();
    
    if (!$meeting) die("Meeting tidak ditemukan.");

    // Header Info Meeting di baris pertama
    fputcsv($output, ['Laporan Absensi Meeting:', $meeting['title']]);
    fputcsv($output, ['Jadwal:', date('d M Y, H:i', strtotime($meeting['scheduled_time']))]);
    fputcsv($output, []); // Baris kosong

    // Header Tabel
    fputcsv($output, ['Nama Karyawan', 'Status Absen', 'Waktu Absen', 'Q1 (Jadwal)', 'Q2 (Notulen)', 'Q3 (Tools)', 'Q4 (Waktu Distribusi)', 'Saran/Masukan']);

    // Ambil semua peserta
    $stmt_part = $pdo->prepare("SELECT users.id, users.name FROM meeting_participants JOIN users ON users.id = meeting_participants.user_id WHERE meeting_id = ?");
    $stmt_part->execute([$meeting_id]);
    $participants = $stmt_part->fetchAll();

    foreach ($participants as $p) {
        // Cek absensi
        $stmt_absen = $pdo->prepare("SELECT status, check_in_time FROM attendances WHERE meeting_id = ? AND user_id = ?");
        $stmt_absen->execute([$meeting_id, $p['id']]);
        $absen = $stmt_absen->fetch();

        $status = $absen ? $absen['status'] : 'Tidak Absen';
        $waktu = $absen ? date('Y-m-d H:i:s', strtotime($absen['check_in_time'])) : '-';

        // Cek feedback
        $stmt_fb = $pdo->prepare("SELECT * FROM meeting_feedbacks WHERE meeting_id = ? AND user_id = ?");
        $stmt_fb->execute([$meeting_id, $p['id']]);
        $fb = $stmt_fb->fetch();

        $q1 = $fb ? $fb['q1_rating'] : '-';
        $q2 = $fb ? $fb['q2_rating'] : '-';
        $q3 = $fb ? $fb['q3_rating'] : '-';
        $q4 = $fb ? $fb['q4_rating'] : '-';
        $feedback = $fb ? $fb['feedback_text'] : '-';

        fputcsv($output, [
            $p['name'],
            $status,
            $waktu,
            $q1,
            $q2,
            $q3,
            $q4,
            $feedback
        ]);
    }
}

fclose($output);
exit;
