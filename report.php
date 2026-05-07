<?php
session_start();
require_once 'database.php';

// Only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$room_filter = $_GET['room'] ?? '';
$detail_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi - Indoarsip</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); text-align: left; }
        .table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .table tr:hover { background: #fdfdfd; }
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .btn-view { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .btn-view:hover { text-decoration: underline; }
        .btn-excel { 
            background-color: #107c41; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 8px; 
            text-decoration: none !important; 
            font-weight: 600; 
            font-size: 0.9rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s; 
            border: none; 
            cursor: pointer; 
        }
        .btn-excel:hover { background-color: #0d6334; transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'topbar.php'; ?>

            <main class="content">
                <?php if (!$detail_id): ?>
                    <!-- LIST MEETINGS -->
                    <h1 class="page-title">Daftar Jadwal Meeting</h1>
                    <p class="page-subtitle">Klik judul meeting untuk melihat rekap absensi</p>
                    
                    <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3>Daftar Meeting</h3>
                        <div style="display:flex; gap:10px;">
                            <a href="export_excel.php?type=summary&room=<?= urlencode($room_filter) ?>" class="btn-excel">
                                <i class="fa-solid fa-file-excel"></i> Export Rekap
                            </a>
                            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                                <select name="room" id="roomFilter" class="form-control" style="width:250px;" onchange="this.form.submit()">
                                    <option value="">-- Semua Ruangan --</option>
                                    <?php
                                    $stmt_rooms = $pdo->query("SELECT name FROM rooms ORDER BY name ASC");
                                    while($r = $stmt_rooms->fetch()) {
                                        $sel = ($room_filter === $r['name']) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($r['name'])."\" $sel>".htmlspecialchars($r['name'])."</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No.</th>
                                    <th>Tanggal</th>
                                    <th>Judul Meeting</th>
                                    <th>Ruang</th>
                                    <th>Jam</th>
                                    <th>Akses Absen</th>
                                    <th>Aksi Laporan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $limit = 5;
                                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                $offset = ($page - 1) * $limit;

                                $where = "";
                                $params = [];
                                if ($room_filter) {
                                    $where = " WHERE room = ? ";
                                    $params[] = $room_filter;
                                }

                                // Count total
                                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM meetings" . $where);
                                $stmt_count->execute($params);
                                $total_rows = $stmt_count->fetchColumn();
                                $total_pages = ceil($total_rows / $limit);

                                $stmt = $pdo->prepare("SELECT * FROM meetings" . $where . " ORDER BY scheduled_time DESC LIMIT $limit OFFSET $offset");
                                $stmt->execute($params);
                                $meetings = $stmt->fetchAll();

                                if (count($meetings) === 0): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                            <i class="fa-solid fa-calendar-xmark" style="display: block; font-size: 2rem; margin-bottom: 10px;"></i>
                                            Belum ada data tersedia
                                        </td>
                                    </tr>
                                <?php else: 
                                    $no = $offset + 1;
                                    foreach ($meetings as $m):
                                        $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/attendance.php?token=" . $m['token'];
                                ?>
                                <?php 
                                    $now_ts = time();
                                    $end_ts = strtotime($m['end_time']);
                                ?>
                                <tr>
                                    <td style="color: #94a3b8; font-weight: 500;"><?= $no++ ?></td>
                                    <td><span style="white-space: nowrap; font-weight: 500; color: #475569;"><?= date('d M Y', strtotime($m['scheduled_time'])) ?></span></td>
                                    <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                    <td><?= htmlspecialchars($m['room']) ?></td>
                                    <td>
                                        <span style="white-space: nowrap;"><?= date('H:i', strtotime($m['scheduled_time'])) ?> - <?= date('H:i', strtotime($m['end_time'])) ?></span>
                                    </td>
                                    <td>
                                        <button class="btn-action-text btn-view-blue" onclick="showAccess('<?= $link ?>', '<?= htmlspecialchars(addslashes($m['title'])) ?>')" title="Lihat Akses Absen"><i class="fa-solid fa-eye" style="margin-right: 5px;"></i>Show</button>
                                    </td>
                                    <td style="display:flex; gap:8px; align-items:center;">
                                        <a href="report.php?id=<?= $m['id'] ?>" class="btn-action-text btn-view-blue"><i class="fa-solid fa-file-lines" style="margin-right: 5px;"></i>Rekap</a>
                                        
                                        <?php if ($now_ts < $end_ts): ?>
                                        <form id="end-form-<?= $m['id'] ?>" action="end_meeting.php" method="POST" style="margin:0; display:flex;">
                                            <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                                            <button type="button" class="btn-action-text btn-end-orange" onclick="confirmEnd(<?= $m['id'] ?>)"><i class="fa-solid fa-circle-stop" style="margin-right: 5px;"></i>Akhiri</button>
                                        </form>
                                        <?php endif; ?>

                                        <form id="delete-form-<?= $m['id'] ?>" action="delete_schedule.php" method="POST" style="margin:0; display:flex;">
                                            <input type="hidden" name="meeting_id" value="<?= $m['id'] ?>">
                                            <button type="button" class="btn-action-text btn-delete-red" onclick="confirmDelete(<?= $m['id'] ?>)"><i class="fa-solid fa-trash" style="margin-right: 5px;"></i>Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="report.php?page=<?= $i ?><?= $room_filter ? '&room='.urlencode($room_filter) : '' ?>" class="page-link <?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <!-- DETAIL MEETING -->
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
                    $stmt->execute([$detail_id]);
                    $meeting = $stmt->fetch();
                    if (!$meeting) die("Meeting tidak ditemukan.");
                    ?>
                    <h1 class="page-title">Laporan Absen: <?= htmlspecialchars($meeting['title']) ?></h1>
                    <p class="page-subtitle">Jadwal: <?= date('d M Y, H:i', strtotime($meeting['scheduled_time'])) ?> - <?= date('H:i', strtotime($meeting['end_time'])) ?></p>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                        <a href="report.php" style="display:inline-flex; align-items:center; padding: 8px 16px; background-color: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; border: 1px solid #e2e8f0;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Kembali ke Daftar
                        </a>
                        <a href="export_excel.php?type=detail&id=<?= $detail_id ?>" class="btn-excel">
                            <i class="fa-solid fa-file-excel"></i> Export Detail Excel
                        </a>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama Karyawan</th>
                                        <th>Status Absen</th>
                                        <th>Waktu Absen</th>
                                        <th>Feedback</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get all participants for this meeting
                                    $stmt_part = $pdo->prepare("SELECT users.id, users.name FROM meeting_participants JOIN users ON users.id = meeting_participants.user_id WHERE meeting_id = ?");
                                    $stmt_part->execute([$detail_id]);
                                    $participants = $stmt_part->fetchAll();

                                    foreach ($participants as $p):
                                        // Cek apakah dia absen
                                        $stmt_absen = $pdo->prepare("SELECT * FROM attendances WHERE meeting_id = ? AND user_id = ?");
                                        $stmt_absen->execute([$detail_id, $p['id']]);
                                        $absen = $stmt_absen->fetch();
                                        
                                        if ($absen) {
                                            $status = $absen['status'];
                                            $waktu = date('d M Y, H:i:s', strtotime($absen['check_in_time']));
                                            if ($status === 'Tepat Waktu') {
                                                $badge = '<span class="badge badge-success">Hadir (Tepat Waktu)</span>';
                                            } else {
                                                $badge = '<span class="badge badge-warning">Hadir (Telat)</span>';
                                            }
                                        } else {
                                            $badge = '<span class="badge badge-danger">Tidak Absen</span>';
                                            $waktu = '-';
                                        }

                                        // Cek feedback
                                        $stmt_fb = $pdo->prepare("SELECT * FROM meeting_feedbacks WHERE meeting_id = ? AND user_id = ?");
                                        $stmt_fb->execute([$detail_id, $p['id']]);
                                        $fb = $stmt_fb->fetch();
                                        
                                        if ($fb) {
                                            $safe_text = htmlspecialchars(addslashes($fb['feedback_text']));
                                            $safe_name = htmlspecialchars(addslashes($p['name']));
                                            $q1 = (int)$fb['q1_rating'];
                                            $q2 = (int)$fb['q2_rating'];
                                            $q3 = (int)$fb['q3_rating'];
                                            $q4 = (int)$fb['q4_rating'];
                                            
                                            $fb_html = "<button class='btn-action-text btn-view-blue' onclick=\"viewFeedback('{$safe_name}', '{$safe_text}', {$q1}, {$q2}, {$q3}, {$q4})\"><i class='fa-solid fa-comment-dots' style='margin-right:5px;'></i>Lihat Feedback</button>";
                                        } else {
                                            $fb_html = "<span style='color:#ccc;'>-</span>";
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td><?= $badge ?></td>
                                        <td><?= $waktu ?></td>
                                        <td><?= $fb_html ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script>
        function viewFeedback(name, text, q1, q2, q3, q4) {
            const getStars = (count) => {
                let s = '';
                for(let i=0; i<5; i++) s += i < count ? '⭐' : '☆';
                return s;
            };

            Swal.fire({
                title: 'Feedback dari ' + name,
                width: '600px',
                html: `<div style="text-align:left; font-size:0.9rem; color:#475569;">
                         <div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <p style="margin:0 0 5px 0; font-weight:600;">1. Kesesuaian Jadwal:</p>
                            <span style="font-size:1.1rem; letter-spacing:2px;">${getStars(q1)}</span>
                         </div>
                         <div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <p style="margin:0 0 5px 0; font-weight:600;">2. Kesesuaian Isi Notulen:</p>
                            <span style="font-size:1.1rem; letter-spacing:2px;">${getStars(q2)}</span>
                         </div>
                         <div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <p style="margin:0 0 5px 0; font-weight:600;">3. Efektivitas Tools:</p>
                            <span style="font-size:1.1rem; letter-spacing:2px;">${getStars(q3)}</span>
                         </div>
                         <div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <p style="margin:0 0 5px 0; font-weight:600;">4. Ketepatan Distribusi Notulen:</p>
                            <span style="font-size:1.1rem; letter-spacing:2px;">${getStars(q4)}</span>
                         </div>
                         <div>
                            <p style="margin:0 0 8px 0; font-weight:600;">5. Saran & Masukan:</p>
                            <div style="background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0; line-height:1.5;">
                                ${text.replace(/\n/g, '<br>')}
                            </div>
                         </div>
                       </div>`,
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3f51b5'
            });
        }

        $(document).ready(function() {
            $('#roomFilter').select2({
                placeholder: "-- Pilih Ruangan --",
                allowClear: true
            });
        });

        function copyLink(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Link Disalin',
                    text: 'Link absensi sudah tersalin ke clipboard Anda.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }

        function showAccess(url, title) {
            Swal.fire({
                title: 'Akses Absensi',
                html: `<p style="margin-bottom:15px; font-size:1rem;"><strong>${title}</strong></p>
                       <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}" style="border:1px solid #eee; padding:5px; border-radius:5px; margin-bottom:15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                       <div style="background:#f8fafc; border:1.5px dashed #cbd5e1; padding:12px; border-radius:8px; word-break:break-all; font-family:monospace; font-size:0.85rem; margin-bottom:15px; color:#334155;">
                           ${url}
                       </div>
                       <button class="btn-submit" onclick="copyLink('${url}')" style="width:100%; padding:10px;">
                           <i class="fa-solid fa-copy" style="margin-right: 8px;"></i> Salin Link
                       </button>`,
                showConfirmButton: false,
                showCloseButton: true
            });
        }

        function confirmEnd(id) {
            Swal.fire({
                title: 'Akhiri Meeting?',
                text: "Peserta tidak akan bisa absen lagi setelah ini.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f39c12',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Akhiri!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('end-form-' + id).submit();
                }
            });
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Jadwal?',
                text: "Data absen yang sudah ada juga akan terhapus permanen!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
</body>
</html>
