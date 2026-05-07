<?php
session_start();
require_once 'database.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Link absensi tidak valid.");
}

// Cek apakah token valid
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE token = ?");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die("Meeting tidak ditemukan atau link sudah kadaluarsa.");
}

// Jika belum login, simpan token ke session dan redirect ke login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = "attendance.php?token=" . $token;
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah user diundang ke meeting ini (Opsional, tapi bagus untuk keamanan)
$stmt_check_invite = $pdo->prepare("SELECT * FROM meeting_participants WHERE meeting_id = ? AND user_id = ?");
$stmt_check_invite->execute([$meeting['id'], $user_id]);
$is_not_invited = false;
if (!$stmt_check_invite->fetch() && $_SESSION['role'] !== 'admin') {
    $is_not_invited = true;
}

// Cek apakah waktu meeting sudah berakhir atau belum dimulai
$start_time = strtotime($meeting['scheduled_time']);
$end_time = strtotime($meeting['end_time']);
$current_time = time();

$is_expired = ($current_time > $end_time);
$is_early = ($current_time < $start_time);

// Cek apakah sudah absen sebelumnya
$stmt_check_absen = $pdo->prepare("SELECT * FROM attendances WHERE meeting_id = ? AND user_id = ?");
$stmt_check_absen->execute([$meeting['id'], $user_id]);
if ($stmt_check_absen->fetch()) {
    $already_absent = true;
} else {
    $already_absent = false;
    
    // Proses Absensi jika disubmit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen'])) {
        $scheduled_time = strtotime($meeting['scheduled_time']);
        $late_tolerance_minutes = isset($meeting['late_tolerance']) ? (int)$meeting['late_tolerance'] : 15;
        $check_in_time = time(); 
        
        $batas_telat = $scheduled_time + ($late_tolerance_minutes * 60);
        $status = ($check_in_time > $batas_telat) ? "Telat" : "Tepat Waktu";
        
        $waktu_absen_db = date('Y-m-d H:i:s', $check_in_time);
        $insert_stmt = $pdo->prepare("INSERT INTO attendances (meeting_id, user_id, check_in_time, status) VALUES (?, ?, ?, ?)");
        $insert_stmt->execute([$meeting['id'], $user_id, $waktu_absen_db, $status]);
        
        $already_absent = true;
        $absen_status = $status;
    }
}

// Check if the current user is the PIC for THIS specific meeting
$is_pic = ($meeting['pic_id'] == $user_id);

// Check if feedback already submitted
$has_feedback = false;
if ($is_pic) {
    $stmt_fb = $pdo->prepare("SELECT id FROM meeting_feedbacks WHERE meeting_id = ? AND user_id = ?");
    $stmt_fb->execute([$meeting['id'], $user_id]);
    if ($stmt_fb->fetch()) {
        $has_feedback = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Meeting - Indoarsip</title>
    <link rel="icon" type="image/png" href="logo_login.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background: #f1f5f9; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .attendance-container {
            max-width: 500px;
            width: 100%;
        }
        .attendance-card { 
            background: white; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.05); 
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .card-header i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }
        .card-header h2 {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.025em;
        }
        .card-body {
            padding: 35px;
        }
        .user-welcome {
            text-align: center;
            margin-bottom: 30px;
        }
        .user-welcome p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .user-welcome strong {
            color: #1e293b;
            font-size: 1.1rem;
        }
        .meeting-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
        }
        .info-row:last-child { margin-bottom: 0; }
        .info-icon {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            flex-shrink: 0;
        }
        .info-content p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin: 0;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .info-content h4 {
            font-size: 0.95rem;
            color: #1e293b;
            margin: 0;
            font-weight: 600;
        }
        .btn-absen {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }
        .btn-absen:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(79, 70, 229, 0.3);
        }
        .status-alert {
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 20px;
        }
        .status-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .status-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .btn-back:hover { color: #1e293b; }

        @media (max-width: 480px) {
            .card-body { padding: 25px; }
            .card-header { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="attendance-container">
        <div class="attendance-card">
            <div class="card-header" style="position: relative;">
                <a href="index.php" style="position: absolute; left: 20px; top: 20px; color: white; text-decoration: none; font-size: 0.75rem; font-weight: 600; background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(4px);">
                    Kembali
                </a>
                <i class="fa-solid fa-id-card-clip"></i>
                <h2>Form Kehadiran</h2>
            </div>
            
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="user-avatar" style="display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #6366f1; background: white; width: 50px; height: 50px; border-radius: 50%; box-shadow: var(--shadow-sm); flex-shrink: 0;">
                        <i class="fa-solid <?= $is_pic ? 'fa-user-tie' : 'fa-user' ?>"></i>
                    </div>
                    <div class="user-welcome" style="margin-bottom: 0;">
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b; font-weight: 500;">Selamat Datang,</p>
                        <strong style="font-size: 1.1rem; color: #1e293b;"><?= htmlspecialchars($_SESSION['name']) ?></strong>
                    </div>
                </div>

                <div class="meeting-info" <?= $is_not_invited ? 'style="pointer-events: none; user-select: none;"' : '' ?>>
                    <div class="info-row">
                        <div class="info-icon"><i class="fa-solid fa-font"></i></div>
                        <div class="info-content">
                            <p>Judul Meeting</p>
                            <h4><?= $is_not_invited ? '••••••••••••' : htmlspecialchars($meeting['title']) ?></h4>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-content">
                            <p>Lokasi / Ruangan</p>
                            <h4><?= $is_not_invited ? '••••••••' : htmlspecialchars($meeting['room']) ?></h4>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="info-content">
                            <p>Waktu Pelaksanaan</p>
                            <h4><?= $is_not_invited ? '•••• ••• ••••' : date('d M Y, H:i', strtotime($meeting['scheduled_time'])) ?></h4>
                        </div>
                    </div>
                </div>

                <?php if ($is_not_invited): ?>
                    <div class="status-alert status-error">
                        <i class="fa-solid fa-user-shield" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin-bottom: 5px;">Akses Ditolak</h4>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Mohon maaf, Anda tidak termasuk dalam daftar peserta yang diundang ke meeting ini.</p>
                    </div>
                    <div style="text-align: center;">
                        <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
                    </div>
                <?php elseif ($already_absent): ?>
                    <div class="status-alert status-success">
                        <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin-bottom: 5px;">Presensi Berhasil!</h4>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Anda sudah tercatat hadir untuk meeting ini.</p>
                        <?php if(isset($absen_status)): ?>
                            <div style="margin-top:10px; font-weight:700; background:rgba(255,255,255,0.5); padding:5px; border-radius:8px;">Status: <?= $absen_status ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_pic): ?>
                        <div style="margin-top: 20px;">
                            <?php if ($has_feedback): ?>
                                <button class="btn-absen" disabled style="background:#cbd5e1; color:#64748b; cursor:not-allowed; box-shadow:none;">
                                    <i class="fa-solid fa-check-double"></i> Feedback Terkirim
                                </button>
                            <?php elseif ($current_time < $end_time): ?>
                                <button class="btn-absen" disabled style="background:#cbd5e1; color:#64748b; cursor:not-allowed; box-shadow:none;">
                                    <i class="fa-solid fa-clock"></i> Feedback: Belum Waktunya
                                </button>
                            <?php else: ?>
                                <a href="feedback.php?id=<?= $meeting['id'] ?>&token=<?= $token ?>" class="btn-absen" style="text-decoration:none;">
                                    <i class="fa-solid fa-comment-medical"></i> Beri Feedback Meeting
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($is_expired && $_SESSION['role'] !== 'admin'): ?>
                    <div class="status-alert status-error">
                        <i class="fa-solid fa-circle-xmark" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin-bottom: 5px;">Link Kadaluarsa</h4>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Waktu meeting sudah berakhir. Silakan hubungi admin jika ini kesalahan.</p>
                    </div>
                    <div style="text-align: center;">
                        <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                    </div>
                <?php elseif ($is_early && $_SESSION['role'] !== 'admin'): ?>
                    <div class="status-alert status-info">
                        <i class="fa-solid fa-hourglass-half" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin-bottom: 5px;">Belum Dimulai</h4>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Halaman akan otomatis aktif saat jam meeting tiba.</p>
                    </div>
                    <div style="text-align: center;">
                        <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                    </div>
                    <script>
                        const targetTime = <?= $start_time * 1000 ?>;
                        const checkStart = setInterval(() => {
                            if (Date.now() >= targetTime) {
                                clearInterval(checkStart);
                                location.reload();
                            }
                        }, 1000);
                    </script>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="absen" value="1">
                        <button type="submit" class="btn-absen">
                            <i class="fa-solid fa-signature"></i> Konfirmasi Kehadiran
                        </button>
                    </form>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="index.php" class="btn-back">Batal & Kembali</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <p style="text-align: center; margin-top: 30px; font-size: 0.8rem; color: #94a3b8; font-weight: 500;">&copy; <?= date('Y') ?> Indoarsip Meeting System. All rights reserved.</p>
    </div>
</body>
</html>
