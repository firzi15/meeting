<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="logo.png" alt="Logo" onerror="this.outerHTML='<span class=\'logo-text\'>INDO<span style=\'color:var(--red-brand)\'>A</span>RSIP</span>'">
        </div>
        <button class="toggle-btn" onclick="document.querySelector('.sidebar').classList.toggle('collapsed');">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">Utama</div>
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="calendar.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Kalender</span>
        </a>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="nav-section">Manajemen Meeting</div>
        <a href="report.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice"></i>
            <span>Laporan</span>
        </a>
        <div class="nav-section">Data Master</div>
        <a href="rooms.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-door-open"></i>
            <span>Master Ruangan</span>
        </a>
        <a href="employees.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>Master Karyawan</span>
        </a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'user'): ?>
        <div class="nav-section">Presensi</div>
        <a href="my_schedule.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'my_schedule.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat Absen</span>
        </a>
        <?php endif; ?>

        <div class="nav-section">Sistem</div>
        <a href="#" onclick="confirmLogout(event)" class="nav-item" style="color: #f87171;">
            <i class="fa-solid fa-power-off"></i>
            <span>Keluar</span>
        </a>
    </nav>
</aside>

<script>
function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Yakin ingin keluar?',
        text: "Anda akan mengakhiri sesi saat ini.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f87171',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Keluar!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}
</script>
