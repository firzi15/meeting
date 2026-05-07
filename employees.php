<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Add Employee
if (isset($_POST['add_employee'])) {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($name && $username && $password) {
        try {
            // Check if username exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = "Gagal: Username '{$username}' sudah digunakan oleh orang lain.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt->execute([$name, $username, $hashed_password])) {
                    $success = "Karyawan berhasil ditambahkan!";
                }
            }
        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Edit Employee
if (isset($_POST['edit_employee'])) {
    $id = $_POST['employee_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($id && $name && $username) {
        try {
            // Check if username exists for other users
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $id]);
            if ($check->fetch()) {
                $error = "Gagal: Username '{$username}' sudah digunakan oleh orang lain.";
            } else {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, password = ? WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$name, $username, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$name, $username, $id]);
                }
                $success = "Data karyawan berhasil diperbarui!";
            }
        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Delete Employee
if (isset($_POST['delete_employee'])) {
    $id = $_POST['employee_id'] ?? '';
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$id]);
            $success = "Karyawan berhasil dihapus!";
        } catch (Exception $e) {
            $error = "Gagal menghapus karyawan.";
        }
    }
}

$employees = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Karyawan - Indoarsip</title>
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
                        <h1 class="page-title">Master Karyawan</h1>
                        <p class="page-subtitle">Kelola data login karyawan perusahaan</p>
                    </div>
                    <button class="btn-submit" onclick="document.getElementById('addModal').classList.add('active')">
                        <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Tambah Karyawan
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No.</th>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th style="width: 150px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($employees)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                                <i class="fa-solid fa-users-slash" style="display: block; font-size: 2rem; margin-bottom: 10px;"></i>
                                                Belum ada data tersedia
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $no = 1;
                                        foreach ($employees as $emp): 
                                        ?>
                                        <tr>
                                            <td style="color: #94a3b8; font-weight: 500;"><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($emp['name']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($emp['username']) ?></code></td>
                                            <td>
                                                <span class="badge badge-success">Aktif</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <button class="btn-action btn-view-blue" onclick="editEmployee(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($emp['username'], ENT_QUOTES) ?>')" title="Edit Karyawan">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Hapus karyawan ini?');" style="display: inline;">
                                                    <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                                    <button type="submit" name="delete_employee" class="btn-action btn-delete-red" title="Hapus Karyawan">
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
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-card" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--sidebar-bg), #2a2e42); color: white; padding: 20px;">
                <h3><i class="fa-solid fa-user-plus" style="margin-right: 10px;"></i> Tambah Karyawan Baru</h3>
                <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')" style="color: white; opacity: 0.7;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-signature" style="color: var(--primary-color); margin-right: 8px;"></i> Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required placeholder="Nama lengkap karyawan">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user-tag" style="color: var(--primary-color); margin-right: 8px;"></i> Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Username untuk login">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-key" style="color: var(--primary-color); margin-right: 8px;"></i> Password Default</label>
                        <input type="password" name="password" class="form-control" required placeholder="Password sementara">
                    </div>
                    <button type="submit" name="add_employee" class="btn-submit" style="width: 100%; padding: 12px; border-radius: 8px;">
                        <i class="fa-solid fa-save" style="margin-right: 8px;"></i> Simpan Karyawan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #3f51b5, #303f9f); color: white; padding: 20px;">
                <h3><i class="fa-solid fa-user-pen" style="margin-right: 10px;"></i> Edit Data Karyawan</h3>
                <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('active')" style="color: white; opacity: 0.7;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <form method="POST">
                    <input type="hidden" name="employee_id" id="edit_emp_id">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-signature" style="color: var(--primary-color); margin-right: 8px;"></i> Nama Lengkap</label>
                        <input type="text" name="name" id="edit_emp_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user-tag" style="color: var(--primary-color); margin-right: 8px;"></i> Username</label>
                        <input type="text" name="username" id="edit_emp_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-key" style="color: var(--primary-color); margin-right: 8px;"></i> Password Baru (Opsional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ganti">
                    </div>
                    <button type="submit" name="edit_employee" class="btn-submit" style="width: 100%; padding: 12px; border-radius: 8px;">
                        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editEmployee(id, name, username) {
            document.getElementById('edit_emp_id').value = id;
            document.getElementById('edit_emp_name').value = name;
            document.getElementById('edit_emp_username').value = username;
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
