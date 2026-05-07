# Meeting & Feedback Management System

Aplikasi berbasis web ini dirancang untuk memudahkan manajemen jadwal meeting korporat, memonitor absensi karyawan dengan sistem *barcode/link*, serta mengumpulkan feedback kepuasan peserta secara terpusat. 

Aplikasi ini menggunakan infrastruktur modern berbasis kontainer yang siap digunakan untuk *development* maupun *production*.

---

## 🚀 Technology Stack

Aplikasi ini dibangun menggunakan arsitektur web klasik yang ringan, handal, dan cepat:
- **Backend & Logic**: PHP 8.2 (Vanilla OOP & Procedural hybrid)
- **Database**: PostgreSQL 15 (Relational Database)
- **Frontend / Styling**: Vanilla HTML5, CSS3, dan Vanilla JavaScript
- **Libraries**:
  - [SweetAlert2](https://sweetalert2.github.io/): Untuk pop-up (modal) interaktif yang cantik.
  - [Select2](https://select2.org/): Untuk elemen dropdown (select) dengan fitur pencarian data relasional.
  - [FontAwesome 6](https://fontawesome.com/): Sistem ikon vektor.
- **Environment & Deployment**: Docker & Docker Compose

---

## 🛠 Panduan Instalasi dengan Docker

Aplikasi ini sangat bergantung pada **Docker Compose** (`docker-compose.yml`) yang sudah diformat dengan baik dan memenuhi seluruh persyaratan infrastruktur (Web Server Apache + modul PHP PostgreSQL, dan Database Postgres yang dipasangkan pada *network* internal).

**Langkah-langkah Run:**

1. Pastikan **Docker** dan **Docker Compose** telah ter-install dan berjalan di mesin Anda.
2. Buka terminal di direktori utama proyek ini.
3. *Build* dan jalankan *container* secara di latar belakang (*detached mode*):
   ```bash
   docker compose up -d --build
   ```
4. Pastikan kedua *container* (`meeting_app` dan `meeting_db`) berjalan tanpa masalah:
   ```bash
   docker ps
   ```
5. Aplikasi Anda sekarang sudah *live* dan bisa diakses via browser di:
   **http://localhost:8080**

---

## 📱 Panduan Akses dari HP (Satu Jaringan WiFi)

Untuk mencoba fitur scan QR atau melihat tampilan mobile langsung di perangkat HP Android Anda, ikuti langkah berikut:

1. **Pastikan Laptop & HP Terhubung ke WiFi yang Sama.**
2. **Cari Tahu Alamat IP Laptop Anda:**
   - Tekan tombol `Windows + R` di keyboard, ketik `cmd`, lalu Enter.
   - Ketik perintah `ipconfig` lalu cari baris **IPv4 Address** (Contoh: `192.168.1.15`).
3. **Buka Browser di HP:**
   - Masukkan alamat IP tersebut diikuti dengan port `:8080` (Contoh: `http://192.168.1.15:8080`).
4. **Catatan (Troubleshooting):**
   - Jika tidak terhubung, pastikan pengaturan **Windows Firewall** di laptop Anda mengizinkan koneksi masuk untuk aplikasi Docker/Apache, atau matikan Firewall sementara untuk pengujian.

---

## 🗄 Langkah-langkah Migrasi Database

Aplikasi memerlukan struktur tabel agar bisa berfungsi. Lakukan langkah migrasi berikut secara berurutan saat **pertama kali** menjalankan aplikasi, atau setelah melakukan *pull* fitur terbaru:

### 1. Inisialisasi Database Utama (First Time Setup)
Langkah ini akan **mereset ulang** tabel dan membuat skema dasar (Users, Rooms, Meetings, Participants, dan Attendances) serta memasukkan *Dummy Data*.
Buka *link* berikut di browser Anda:
👉 **http://localhost:8080/setup_db.php**

*(Peringatan: Script ini akan melakukan DROP pada tabel yang sudah ada. Hanya gunakan untuk inisialisasi awal).*

### 2. Menjalankan Migrasi Fitur Feedback (Update)
Baru-baru ini sistem telah diperbarui dengan fitur penilaian/feedback. Anda wajib membuat tabel `meeting_feedbacks`. Jalankan migrasi khusus ini tanpa merusak data sebelumnya dengan membuka *link* berikut di browser:
👉 **http://localhost:8080/add_feedback_table.php**

---

## 👤 Akun Login Default

Setelah **Langkah 1** (Migrasi Utama) dijalankan, Anda dapat login ke dalam sistem menggunakan beberapa akses peran (*role*) berikut:

| Nama Karyawan | Role | Username | Password |
| --- | --- | --- | --- |
| Information Technology | **Admin** | `admin` | `admin` |
| John Doe | **User** | `johndoe` | `password123` |
| Jane Smith | **User** | `janesmith` | `password123` |

---

## ✨ Fitur Utama
1. **Multi-Role Login**: Dashboard spesifik sesuai Role (Admin/User).
2. **Dynamic Room Scheduling**: Manajemen ruang meeting terpusat, lengkap dengan validasi jam bentrok (conflict check).
3. **Smart QR Attendance**: Scan QR atau klik Link dinamis per sesi untuk absensi, ditambah logika *Late Tolerance*.
4. **Post-Meeting Feedback**: Peserta dapat memberikan bintang (Rating Kepuasan) dan komentar setelah admin menutup/mengakhiri sesi.
5. **Real-Time Validations**: Tombol dan akses dinonaktifkan secara otomatis berdasarkan kronologi waktu.
