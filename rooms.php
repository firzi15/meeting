<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Add Room
if (isset($_POST['add_room'])) {
    $name = $_POST['name'] ?? '';
    if ($name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "Ruangan berhasil ditambahkan!";
        } catch (Exception $e) {
            $error = "Gagal menambahkan ruangan: " . $e->getMessage();
        }
    }
}

// Edit Room
if (isset($_POST['edit_room'])) {
    $id = $_POST['room_id'] ?? '';
    $name = $_POST['name'] ?? '';
    if ($id && $name) {
        try {
            $stmt = $pdo->prepare("UPDATE rooms SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $success = "Ruangan berhasil diperbarui!";
        } catch (Exception $e) {
            $error = "Gagal memperbarui ruangan.";
        }
    }
}

// Delete Room
if (isset($_POST['delete_room'])) {
    $id = $_POST['room_id'] ?? '';
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Ruangan berhasil dihapus!";
        } catch (Exception $e) {
            $error = "Gagal menghapus ruangan.";
        }
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include 'topbar.php'; ?>
            <main class="content">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h1 class="page-title">Master Ruangan</h1>
                        <p class="page-subtitle">Kelola daftar ruangan meeting perusahaan</p>
                    </div>
                    <button class="btn-submit" onclick="document.getElementById('addModal').classList.add('active')">
                        <i class="fa-solid fa-plus" style="margin-right: 8px;"></i> Tambah Ruangan
                    </button>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">No.</th>
                                    <th>Nama Ruangan</th>
                                    <th style="width: 150px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rooms)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8;">
                                            <i class="fa-solid fa-folder-open" style="display: block; font-size: 2rem; margin-bottom: 10px;"></i>
                                            Belum ada data tersedia
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($rooms as $room): ?>
                                    <tr>
                                        <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;"><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($room['name']) ?></strong></td>
                                        <td style="text-align: center;">
                                            <button class="btn-action btn-view-blue" onclick="editRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')" title="Edit Ruangan">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Hapus ruangan ini?');" style="display: inline;">
                                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                                <button type="submit" name="delete_room" class="btn-action btn-delete-red" title="Hapus Ruangan">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-card" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--sidebar-bg), #2a2e42); color: white; padding: 20px;">
                <h3><i class="fa-solid fa-plus-circle" style="margin-right: 10px;"></i> Tambah Ruangan Baru</h3>
                <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')" style="color: white; opacity: 0.7;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-door-open" style="color: var(--primary-color); margin-right: 8px;"></i> Nama Ruangan</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Ruang Mezanine">
                    </div>
                    <button type="submit" name="add_room" class="btn-submit" style="width: 100%; padding: 12px; border-radius: 8px;">
                        <i class="fa-solid fa-save" style="margin-right: 8px;"></i> Simpan Ruangan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #3f51b5, #303f9f); color: white; padding: 20px;">
                <h3><i class="fa-solid fa-pen-to-square" style="margin-right: 10px;"></i> Edit Data Ruangan</h3>
                <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('active')" style="color: white; opacity: 0.7;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <form method="POST">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-door-open" style="color: var(--primary-color); margin-right: 8px;"></i> Nama Ruangan</label>
                        <input type="text" name="name" id="edit_room_name" class="form-control" required>
                    </div>
                    <button type="submit" name="edit_room" class="btn-submit" style="width: 100%; padding: 12px; border-radius: 8px;">
                        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editRoom(id, name) {
            document.getElementById('edit_room_id').value = id;
            document.getElementById('edit_room_name').value = name;
            document.getElementById('editModal').classList.add('active');
        }
    </script>

    <?php if ($success): ?>
    <script>
        Toast.fire({ icon: 'success', title: '<?= $success ?>' });
    </script>
    <?php endif; ?>
    <?php if ($error): ?>
    <script>
        Toast.fire({ icon: 'error', title: '<?= $error ?>' });
    </script>
    <?php endif; ?>
</body>
</html>
