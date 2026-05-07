<!-- topbar.php -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
        <span id="current-datetime"><?= date('d M Y H:i:s') ?></span>
    </div>
    <div class="topbar-right">
        <div class="profile-info">
            <div class="profile-avatar" style="display: flex; align-items: center; justify-content: center; background: #e0e7ff; color: #4f46e5; font-size: 1.2rem; border: none;">
                <i class="fa-solid fa-user"></i>
            </div>
            <?= htmlspecialchars($_SESSION['name']) ?></strong></span>
        </div>
    </div>
</header>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }
</script>

<script>
    function updateClock() {
        const now = new Date();
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const day = String(now.getDate()).padStart(2, '0');
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        const time = now.toLocaleTimeString('id-ID', { hour12: false });
        
        const display = document.getElementById('current-datetime');
        if (display) {
            display.textContent = `${day} ${month} ${year} ${time}`;
        }
    }
    // Run immediately and then every second
    updateClock();
    setInterval(updateClock, 1000);
</script>
