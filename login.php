<?php
// login.php
session_start();
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        // If there's a redirect set (e.g., from an attendance link)
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect);
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Indoarsip</title>
    <link rel="icon" type="image/png" href="logo_login.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: var(--bg-color);
        }
        .login-card {
            background: white;
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
        }
        .login-card .form-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 20px;
        }
        .alert {
            padding: 10px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 50px;">
            <img src="logo_login.png" alt="Logo" style="height: 120px; width: auto; object-fit: contain;" onerror="this.outerHTML='<h1 style=\'font-weight:900; font-size: 2.25rem; letter-spacing: -1.5px; color: #1e293b; margin: 0;\'>INDO<span style=\'color: #e11d48;\'>A</span>RSIP</h1>'">
            <p style="margin-top: 10px; color: #64748b; font-size: 0.875rem;">Sistem Presensi Meeting Karyawan</p>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>

        <?php if ($error): ?>
            <script>
                Toast.fire({ icon: 'error', title: '<?= $error ?>' });
            </script>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn-submit" style="width: 100%; padding: 12px; margin-top: 10px;">
                <i class="fa-solid fa-right-to-bracket" style="margin-right: 8px;"></i> Masuk Sekarang
            </button>
        </form>
    </div>
</body>
</html>
