<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Indoarsip</title>
    <link rel="icon" type="image/png" href="logo_login.png">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Topbar -->
            <?php include 'topbar.php'; ?>

            <!-- Content Area -->
            <main class="content">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Kelola jadwal meeting dan absensi harian</p>
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button class="btn-submit" onclick="document.getElementById('scheduleModal').classList.add('active')">
                        <i class="fa-solid fa-calendar-plus" style="margin-right: 8px;"></i> Buat Jadwal
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="room-grid">
                    <?php
                    // Fetch active rooms from master data
                    $stmt_rooms = $pdo->query("SELECT name FROM rooms ORDER BY name ASC");
                    $rooms = $stmt_rooms->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (empty($rooms)) {
                        echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;'>
                                <i class='fa-solid fa-door-closed' style='font-size: 3rem; color: #cbd5e1; margin-bottom: 15px; display: block;'></i>
                                <p style='color: #64748b;'>Belum ada data ruangan di Master Ruangan.</p>
                              </div>";
                    }

                    $now = date('Y-m-d H:i:s');
                    foreach ($rooms as $room_name):
                        // Fetch meetings for this room
                        if ($_SESSION['role'] === 'admin') {
                            $stmt = $pdo->prepare("SELECT * FROM meetings WHERE room = ? AND end_time > ? ORDER BY scheduled_time ASC");
                            $stmt->execute([$room_name, $now]);
                        } else {
                            $stmt = $pdo->prepare("
                                SELECT m.* FROM meetings m 
                                JOIN meeting_participants mp ON m.id = mp.meeting_id 
                                WHERE m.room = ? AND mp.user_id = ? AND m.end_time > ?
                                ORDER BY m.scheduled_time ASC
                            ");
                            $stmt->execute([$room_name, $_SESSION['user_id'], $now]);
                        }
                        $room_meetings = $stmt->fetchAll();
                    ?>
                    <div class="room-card">
                        <div class="room-card-header">
                            <h3><i class="fa-solid fa-door-open" style="margin-right: 8px; color: var(--primary-color);"></i> <?= htmlspecialchars($room_name) ?></h3>
                        </div>
                        <div class="room-card-body">
                            <?php if (empty($room_meetings)): ?>
                                <div style="text-align: center; padding: 20px 0; color: #94a3b8;">
                                    <i class="fa-solid fa-calendar-xmark" style="display: block; font-size: 1.5rem; margin-bottom: 8px;"></i>
                                    <span style="font-size: 0.85rem;">Belum ada data tersedia</span>
                                </div>
                            <?php else: ?>
                                <?php 
                                $limit = 5;
                                $count = 0;
                                foreach ($room_meetings as $rm): 
                                    if ($count >= $limit) break;
                                    $count++;
                                ?>
                                    <div class="meeting-item">
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="meeting-time">
                                                <?= date('H:i', strtotime($rm['scheduled_time'])) ?> - <?= date('H:i', strtotime($rm['end_time'])) ?>
                                            </div>
                                            <span class="m-title"><?= htmlspecialchars($rm['title']) ?></span>
                                        </div>
                                        <div style="display:flex; gap:8px;">
                                            <?php if ($_SESSION['role'] === 'user'): ?>
                                                <a href="attendance.php?token=<?= $rm['token'] ?>" class="btn-action btn-view-blue" title="Absen Sekarang"><i class="fa-solid fa-user-check"></i></a>
                                                <?php
                                                    $stmt_att = $pdo->prepare("SELECT id FROM attendances WHERE meeting_id = ? AND user_id = ?");
                                                    $stmt_att->execute([$rm['id'], $_SESSION['user_id']]);
                                                    $has_attended = $stmt_att->fetch();

                                                    $stmt_fb = $pdo->prepare("SELECT id FROM meeting_feedbacks WHERE meeting_id = ? AND user_id = ?");
                                                    $stmt_fb->execute([$rm['id'], $_SESSION['user_id']]);
                                                    
                                                    if ($stmt_fb->fetch()):
                                                ?>
                                                    <button class="btn-action btn-disabled" disabled title="Feedback Terkirim" style="background:#e0e0e0; color:#888;"><i class="fa-solid fa-check"></i></button>
                                                <?php else: ?>
                                                    <button class="btn-action btn-view-blue" title="Beri Feedback" onclick="openFeedback(<?= $rm['id'] ?>, '<?= htmlspecialchars(addslashes($rm['title'])) ?>')" <?= (!$has_attended || time() < strtotime($rm['end_time'])) ? 'disabled style="background:#ccc; cursor:not-allowed;"' : '' ?>><i class="fa-solid fa-comment-dots"></i></button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="report.php?id=<?= $rm['id'] ?>" class="btn-action btn-view-blue" title="Lihat Rekapitulasi"><i class="fa-solid fa-chart-simple"></i></a>
                                                <button class="btn-action btn-copy" title="Salin Link Absensi" onclick="copyLinkModal('<?= 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/attendance.php?token=' . $rm['token'] ?>')"><i class="fa-solid fa-link"></i></button>
                                                <button class="btn-action btn-qr" title="Lihat QR Code" onclick="showQRModal('<?= $rm['title'] ?>', '<?= 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/attendance.php?token=' . $rm['token'] ?>')"><i class="fa-solid fa-qrcode"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($room_meetings) > $limit): ?>
                                    <div style="margin-top: 15px; text-align: center;">
                                        <?php 
                                        $more_link = ($_SESSION['role'] === 'admin') ? "report.php?room=" . urlencode($room_name) : "my_schedule.php?room=" . urlencode($room_name);
                                        ?>
                                        <a href="<?= $more_link ?>" class="btn-submit" style="width:100%; text-decoration: none; padding: 8px;">Lihat Selengkapnya &rarr;</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>

            <?php include 'footer.php'; ?>
        </div>
    </div>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <!-- Modal Buat Jadwal -->
    <div id="scheduleModal" class="modal-overlay">
        <div class="modal-card" style="max-width: 650px; border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--sidebar-bg), #2a2e42); color: white; padding: 24px;">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Buat Jadwal Meeting Baru</h3>
                    <p style="margin: 4px 0 0; font-size: 0.875rem; opacity: 0.8;">Isi detail meeting untuk mendapatkan link absensi otomatis</p>
                </div>
                <button class="modal-close" onclick="document.getElementById('scheduleModal').classList.remove('active')" style="color: white; opacity: 0.7;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 20px 25px; background: #fff;">
                <form id="scheduleForm">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label">Judul Meeting</label>
                        <input type="text" name="title" class="form-control" required placeholder="Contoh: Rapat Koordinasi Mingguan">
                    </div>

                    <div class="schedule-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Ruang Meeting</label>
                            <select name="room" class="form-control" required>
                                <option value="">-- Pilih Ruangan --</option>
                                <?php
                                $stmt_rooms_m = $pdo->query("SELECT name FROM rooms ORDER BY name ASC");
                                while($r = $stmt_rooms_m->fetch()) {
                                    echo "<option value=\"".htmlspecialchars($r['name'])."\">".htmlspecialchars($r['name'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Toleransi (Menit)</label>
                            <input type="number" name="late_tolerance" class="form-control" value="15" min="0" required>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 0.8rem;">Tanggal Pelaksanaan</label>
                            <input type="date" name="date" class="form-control" required style="padding: 8px 12px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.8rem;">Jam Mulai</label>
                                <input type="time" name="time" class="form-control" required style="padding: 8px 12px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="font-size: 0.8rem;">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-control" required style="padding: 8px 12px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label"><i class="fa-solid fa-user-tie" style="margin-right: 8px; color: var(--primary-color);"></i> PIC Meeting (Penanggung Jawab)</label>
                        <select name="pic_id" id="picSelect" required style="width: 100%;">
                            <option></option> <!-- Required for Select2 placeholder -->
                            <?php
                            $stmt_pic = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY name ASC");
                            while($u = $stmt_pic->fetch()) {
                                echo "<option value=\"{$u['id']}\">".htmlspecialchars($u['name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label"><i class="fa-solid fa-users" style="margin-right: 8px; color: var(--primary-color);"></i> Peserta Diundang (Anggota)</label>
                        <select name="participants[]" id="participantSelect" multiple="multiple" style="width: 100%;">
                            <?php
                            $stmt_u = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY name ASC");
                            while($u = $stmt_u->fetch()) {
                                echo "<option value=\"{$u['id']}\">".htmlspecialchars($u['name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" style="width: 100%; padding: 12px; font-size: 0.95rem;">
                        <i class="fa-solid fa-calendar-check" style="margin-right: 8px;"></i> Simpan Jadwal Meeting
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#picSelect').select2({
                placeholder: "Pilih PIC Meeting",
                allowClear: true,
                dropdownParent: $('#scheduleModal'),
                width: '100%'
            });

            $('#participantSelect').select2({
                placeholder: "Pilih Peserta Diundang",
                allowClear: true,
                dropdownParent: $('#scheduleModal'),
                width: '100%'
            });

            // Exclude PIC from Participants
            $('#picSelect').on('change', function() {
                const picId = $(this).val();
                const $participantSelect = $('#participantSelect');
                
                // Reset all options first
                $participantSelect.find('option').prop('disabled', false);
                
                if (picId) {
                    // Disable the PIC option in participants
                    $participantSelect.find(`option[value="${picId}"]`).prop('disabled', true);
                    
                    // If the PIC was already selected as a participant, remove them
                    const currentParticipants = $participantSelect.val() || [];
                    const newParticipants = currentParticipants.filter(id => id !== picId);
                    $participantSelect.val(newParticipants).trigger('change');
                }
                
                // Refresh Select2 to show disabled state
                $participantSelect.select2({
                    placeholder: "Pilih Peserta Diundang",
                    allowClear: true,
                    dropdownParent: $('#scheduleModal')
                });
            });

            // Auto-open if redirected from sidebar
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'create') {
                document.getElementById('scheduleModal').classList.add('active');
            }
        });

        function openFeedback(meetingId, title) {
            Swal.fire({
                title: 'Feedback Meeting',
                html: `<p style="margin-bottom:15px; font-size: 0.9rem;"><strong>${title}</strong></p>
                       <form id="feedbackForm">
                           <input type="hidden" name="meeting_id" value="${meetingId}">
                           <div style="margin-bottom: 15px; text-align: left;">
                               <label style="display:block; margin-bottom: 8px; font-weight:600; font-size:0.9rem;">Rating Kepuasan</label>
                               <select name="rating" class="form-control" required style="width:100%; border:1px solid #ccc; border-radius:5px; padding:8px;">
                                   <option value="5">⭐⭐⭐⭐⭐ (Sangat Baik)</option>
                                   <option value="4">⭐⭐⭐⭐ (Baik)</option>
                                   <option value="3">⭐⭐⭐ (Cukup)</option>
                                   <option value="2">⭐⭐ (Kurang)</option>
                                   <option value="1">⭐ (Sangat Kurang)</option>
                               </select>
                           </div>
                           <div style="margin-bottom: 15px; text-align: left;">
                               <label style="display:block; margin-bottom: 8px; font-weight:600; font-size:0.9rem;">Komentar / Masukan</label>
                               <textarea name="feedback_text" rows="4" class="form-control" required style="width:100%; border:1px solid #ccc; border-radius:5px; padding:8px; box-sizing:border-box;" placeholder="Tulis masukan Anda di sini..."></textarea>
                           </div>
                       </form>`,
                showCancelButton: true,
                confirmButtonText: 'Kirim Feedback',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3f51b5',
                preConfirm: () => {
                    const form = document.getElementById('feedbackForm');
                    const fd = new FormData(form);
                    if(!fd.get('feedback_text').trim()) {
                        Swal.showValidationMessage('Komentar tidak boleh kosong');
                        return false;
                    }
                    return fetch('submit_feedback.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Terjadi kesalahan sistem');
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terkirim!',
                        text: 'Terima kasih atas feedback Anda.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }
            });
        }

        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

            fetch('save_schedule.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save" style="margin-right: 8px;"></i> Simpan Jadwal & Generate Link';
                
                if (data.success) {
                    document.getElementById('scheduleModal').classList.remove('active');
                    this.reset();
                    $('#participantSelect').val(null).trigger('change');
                    
                    Swal.fire({
                        title: 'Jadwal Berhasil Dibuat!',
                        html: `
                            <p style="margin-bottom:15px;">Meeting <strong>${data.title}</strong> berhasil disimpan.</p>
                            <div style="background:#f1f5f9; border:1.5px dashed #cbd5e1; padding:15px; border-radius:8px; word-break:break-all; font-family:monospace; font-size: 0.85rem; margin-bottom:15px;">
                                ${data.link}
                            </div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(data.link)}" style="margin-bottom:15px; border:4px solid white; box-shadow: var(--shadow-md); border-radius:8px;">
                            <br>
                            <button class="btn-submit" onclick="copyLinkModal('${data.link}')" style="width:100%; margin-bottom:10px;">
                                <i class="fa-solid fa-copy" style="margin-right: 8px;"></i> Salin Link Absensi
                            </button>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Tutup & Segarkan',
                        confirmButtonColor: '#3f51b5'
                    }).then(() => location.reload());
                } else {
                    Toast.fire({ icon: 'error', title: data.message });
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save" style="margin-right: 8px;"></i> Simpan Jadwal & Generate Link';
                Toast.fire({ icon: 'error', title: 'Terjadi kesalahan sistem.' });
            });
        });

        function copyLinkModal(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Link Berhasil Disalin!',
                    text: 'Link absensi siap dibagikan.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }

        function showQRModal(title, link) {
            Swal.fire({
                title: 'QR Code Absensi',
                html: `
                    <p style="margin-bottom:15px;">Meeting: <strong>${title}</strong></p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(link)}" style="margin-bottom:15px; border:4px solid white; box-shadow: var(--shadow-md); border-radius:8px;">
                    <p style="font-size:0.8rem; color:#64748b; word-break:break-all;">${link}</p>
                `,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3f51b5'
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
