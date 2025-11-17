# Elara Space - Library Management System

![Elara Space Logo](https://via.placeholder.com/150?text=Elara+Space)

Sistem Manajemen Perpustakaan Digital untuk Fakultas Ekonomi di 5 Universitas: UPI, UNPAD, UIN, UMB, dan IKOPIN.

## 🖼️ Preview Aplikasi

### Homepage
![Homepage Preview](https://via.placeholder.com/1200x600/3B82F6/FFFFFF?text=Screenshot+Homepage+-+Upload+screenshot+kamu+disini)

### Dashboard Admin
![Admin Dashboard](https://via.placeholder.com/1200x600/3B82F6/FFFFFF?text=Screenshot+Admin+Dashboard+-+Upload+screenshot+kamu+disini)

### Katalog Buku
![Katalog Buku](https://via.placeholder.com/1200x600/3B82F6/FFFFFF?text=Screenshot+Katalog+Buku+-+Upload+screenshot+kamu+disini)

> **Note:** Untuk melihat aplikasi secara langsung, silakan ikuti [Langkah Instalasi](#-instalasi) di bawah ini.

## 📋 Deskripsi

Elara Space adalah sistem perpustakaan digital yang dirancang khusus untuk mengelola peminjaman buku, pengembalian, dan request buku dari penerbit untuk fakultas ekonomi di 5 universitas. Sistem ini memiliki fitur kolaborasi dengan penerbit seperti Gramedia, Erlangga, dan lainnya melalui sistem request buku.

### ✨ Fitur Utama

#### Untuk Admin
- **Dashboard Analytics** - Statistik real-time peminjaman, pengembalian, dan request
- **Manajemen Buku** - CRUD lengkap dengan upload cover buku
- **Peminjaman & Pengembalian** - Tracking status peminjaman otomatis
- **Approval Request Buku** - Review dan approve request dari user
- **Manajemen User** - Kelola mahasiswa, dosen, dan staff
- **Manajemen Penerbit** - Kelola partnership dengan penerbit
- **Sistem Denda** - Kalkulasi denda otomatis untuk keterlambatan
- **Laporan & Analytics** - Report lengkap untuk monitoring
- **Activity Logs** - Track semua aktivitas sistem

#### Untuk User (Mahasiswa, Dosen, Staff)
- **Browse Buku** - Pencarian dan filter buku yang tersedia
- **Riwayat Peminjaman** - Track buku yang dipinjam
- **Request Buku** - Request buku baru dari penerbit
- **Notifikasi** - Alert untuk due date dan update request
- **Profil Management** - Kelola profil dan password

#### Sistem Request Buku (Marketplace)
- User dapat request buku yang belum tersedia
- Admin review dan approve request
- Sistem tracking dari pending → approved → ordered → received
- Partnership dengan penerbit: Gramedia, Erlangga, Salemba Empat, Andi Publisher, Rajagrafindo

## 🛠️ Teknologi

- **Backend**: PHP 8.0+ (Pure PHP, No Framework)
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3, HTML5, CSS3, JavaScript
- **Icons**: Bootstrap Icons
- **Server**: Apache (Laragon/XAMPP/WAMP)

## 📦 Instalasi Lengkap (Dari Nol)

### 🖥️ Persyaratan Sistem

- **OS:** Windows 10/11, macOS, atau Linux
- **RAM:** Minimal 4GB (Recommended 8GB)
- **Storage:** Minimal 2GB free space
- **Browser:** Chrome 90+, Firefox 88+, atau Edge 90+ (versi terbaru recommended)
- **Internet:** Untuk download software & load CDN (Bootstrap, Icons)

### 📋 Software yang Dibutuhkan

| Software | Versi Minimum | Versi Recommended | Included in |
|----------|---------------|-------------------|-------------|
| **PHP** | 8.0 | 8.1 atau 8.2 | Laragon/XAMPP |
| **MySQL** | 5.7 | 8.0 | Laragon/XAMPP |
| **Apache** | 2.4 | 2.4+ | Laragon/XAMPP |
| **phpMyAdmin** | 5.0+ | Latest | Laragon/XAMPP |

**Extension PHP yang Wajib:**
- `pdo` (untuk database connection)
- `pdo_mysql` (untuk MySQL)
- `mbstring` (untuk string handling)
- `gd` (untuk image processing/upload)
- `json` (untuk JSON handling)
- `session` (untuk user sessions)

> **💡 Good News:** Semua extension ini sudah OTOMATIS aktif di Laragon & XAMPP!

---

## 🚀 Panduan Instalasi Step-by-Step

### STEP 1: Download & Install Web Server

**Pilih salah satu:** Laragon (Recommended) atau XAMPP

---

#### OPSI A: Laragon (Recommended - Modern & Mudah) ⭐

**Apa itu Laragon?**
Software all-in-one yang sudah include Apache, MySQL, PHP, phpMyAdmin dalam satu paket. Gampang banget & lightweight!

**Kenapa Laragon?**
- ✅ Auto create virtual host (http://elara-space.test)
- ✅ Lightweight (200MB)
- ✅ Modern UI
- ✅ Easy switch PHP version
- ✅ Built-in SSL support

**Download & Install:**

1. **Download Laragon Full:**
   - 🔗 **Link:** https://laragon.org/download/
   - Pilih: **Laragon - Full** (Windows)
   - Size: ±200MB
   - Include: Apache 2.4, MySQL 8.0, PHP 8.1

2. **Install Laragon:**
   - Jalankan file installer (`laragon-wamp.exe`)
   - Klik **Next** → **Next** → **Install**
   - **PENTING:** Install di `C:\laragon` (default - jangan diubah!)
   - Tunggu hingga selesai (±5-10 menit)
   - Klik **Finish**

3. **Jalankan Laragon:**
   - Buka **Laragon** dari Start Menu / Desktop
   - Klik tombol **"Start All"** (kanan bawah)
   - Tunggu sampai Apache & MySQL berwarna **HIJAU** ✅
   - Sekarang web server kamu sudah jalan!

4. **Verify Installation:**
   - Buka browser, ketik: `http://localhost`
   - Harusnya muncul halaman **Laragon Index**
   - Klik **phpMyAdmin** → harusnya bisa login

**Troubleshooting Laragon:**
- Jika Apache/MySQL tidak hijau: Klik kanan icon Laragon → Run as Administrator
- Jika port sudah dipakai: Settings → Port → Ubah Apache port ke 8080

---

#### OPSI B: XAMPP (Alternative - Lebih Terkenal)

**Apa itu XAMPP?**
Software classic untuk web development yang sudah terkenal sejak lama.

**Download & Install:**

1. **Download XAMPP:**
   - 🔗 **Link:** https://www.apachefriends.org/download.html
   - Pilih: **XAMPP for Windows** (PHP 8.1 atau 8.2)
   - Size: ±160MB

2. **Install XAMPP:**
   - Jalankan installer (`xampp-windows-x64-installer.exe`)
   - Select components:
     - ✅ Apache
     - ✅ MySQL
     - ✅ PHP
     - ✅ phpMyAdmin
   - Install location: `C:\xampp` (default)
   - Klik **Next** sampai selesai

3. **Jalankan XAMPP:**
   - Buka **XAMPP Control Panel**
   - Klik **Start** pada **Apache**
   - Klik **Start** pada **MySQL**
   - Tunggu sampai keduanya berwarna **HIJAU**

4. **Verify Installation:**
   - Buka browser: `http://localhost`
   - Harusnya muncul **XAMPP Dashboard**
   - Klik **phpMyAdmin** di menu

**PENTING untuk XAMPP:**
- Project harus diletakkan di: `C:\xampp\htdocs\elara-space\`
- URL akses: `http://localhost/elara-space`

---

### Versi yang Digunakan (Recommended):

| Component | Laragon Full | XAMPP 8.2 |
|-----------|--------------|-----------|
| PHP | 8.1.10 | 8.2.12 |
| MySQL | 8.0.30 | 8.0.36 (MariaDB) |
| Apache | 2.4.54 | 2.4.58 |
| phpMyAdmin | 5.2.0 | 5.2.1 |

> **Catatan:** Versi di atas bisa berbeda tergantung waktu download. Yang penting PHP >= 8.0 dan MySQL >= 5.7!

---

### STEP 2: Download Project Elara Space

Ada 2 cara: Via Git (recommended) atau Download ZIP manual.

---

#### OPSI A: Via Git (Recommended - Bisa Update Mudah) ⭐

**1. Install Git (kalau belum punya):**
   - 🔗 **Download Git:** https://git-scm.com/download/win
   - Pilih: **64-bit Git for Windows Setup** (±50MB)
   - Install dengan setting default (Next → Next → Install)
   - Verify: Buka CMD, ketik `git --version` → harusnya muncul versi

**2. Clone Repository:**

**Untuk Laragon:**
```bash
# Buka Terminal/CMD
cd C:\laragon\www

# Clone repository (ganti URL dengan URL repo kamu!)
git clone https://github.com/Muhamadafat/elara_space.git elara-space

# Masuk ke folder
cd elara-space
```

**Untuk XAMPP:**
```bash
# Buka Terminal/CMD
cd C:\xampp\htdocs

# Clone repository
git clone https://github.com/Muhamadafat/elara_space.git elara-space

# Masuk ke folder
cd elara-space
```

**Keuntungan pakai Git:**
- ✅ Mudah update code (git pull)
- ✅ Bisa lihat history changes
- ✅ Professional workflow

---

#### OPSI B: Download ZIP Manual (Gampang - Tanpa Git)

**1. Download Project:**
   - 🔗 **Kunjungi:** https://github.com/Muhamadafat/elara_space
   - Klik tombol **"<> Code"** (hijau, di kanan atas)
   - Pilih **"Download ZIP"**
   - Save file `elara_space-main.zip` (±5MB)

**2. Extract & Copy:**

**Untuk Laragon:**
   - Extract file ZIP (klik kanan → Extract All)
   - Rename folder dari `elara_space-main` jadi `elara-space`
   - Copy folder `elara-space` ke `C:\laragon\www\`
   - **Struktur akhir:** `C:\laragon\www\elara-space\index.php`

**Untuk XAMPP:**
   - Extract file ZIP
   - Rename folder dari `elara_space-main` jadi `elara-space`
   - Copy folder `elara-space` ke `C:\xampp\htdocs\`
   - **Struktur akhir:** `C:\xampp\htdocs\elara-space\index.php`

**3. Verify:**
   - Buka Windows Explorer
   - Pastikan ada file `index.php`, `README.md`, folder `config/`, dll di dalam folder `elara-space`

---

### STEP 3: Setup Database

**Waktu:** ±5-10 menit

---

**1. Pastikan Web Server Running:**

**Untuk Laragon:**
   - Buka aplikasi **Laragon**
   - Klik **"Start All"** (kanan bawah)
   - Tunggu sampai **Apache** dan **MySQL** berwarna **HIJAU** ✅
   - Jangan ditutup!

**Untuk XAMPP:**
   - Buka **XAMPP Control Panel**
   - Klik **Start** pada **Apache**
   - Klik **Start** pada **MySQL**
   - Tunggu sampai keduanya berwarna **HIJAU** ✅

---

**2. Buka phpMyAdmin:**

**Cara 1 (Via Browser):**
   - Buka browser (Chrome/Firefox/Edge)
   - Ketik URL: `http://localhost/phpmyadmin`
   - Tekan **Enter**

**Cara 2 (Via Laragon Menu):**
   - Klik kanan icon **Laragon** (di system tray)
   - Pilih **"MySQL"** → **"phpMyAdmin"**

**Cara 3 (Via XAMPP Dashboard):**
   - Buka `http://localhost`
   - Klik menu **"phpMyAdmin"**

---

**3. Login phpMyAdmin:**
   - **Username:** `root`
   - **Password:** *(kosongkan - langsung Enter)*
   - Klik **"Go"** atau **"Log in"**

   > **Note:** Password default untuk Laragon & XAMPP adalah KOSONG!

---

**4. Buat Database Baru:**

   **Step-by-step:**
   - Di halaman phpMyAdmin, klik tab **"Databases"** (di menu atas)
   - Lihat bagian **"Create database"**
   - Di kolom **"Database name"**, ketik: `elara_space`
   - Di dropdown **"Collation"**, pilih: `utf8mb4_general_ci`
   - Klik tombol **"Create"**
   - ✅ Database `elara_space` sekarang muncul di sidebar kiri

---

**5. Import Database Schema (Tables & Data):**

   **Step-by-step:**
   - Klik database **`elara_space`** di sidebar kiri (jadi highlight/aktif)
   - Klik tab **"Import"** di menu atas
   - Klik tombol **"Choose File"** atau **"Browse..."**

   **Pilih file database.sql:**
   - **Untuk Laragon:** `C:\laragon\www\elara-space\database.sql`
   - **Untuk XAMPP:** `C:\xampp\htdocs\elara-space\database.sql`

   - Klik **"Open"**
   - **Jangan ubah setting lain!** (biarkan default)
   - Scroll ke paling bawah
   - Klik tombol **"Go"** atau **"Import"**
   - ⏳ Tunggu proses import (±10-30 detik)
   - ✅ Harusnya muncul pesan: **"Import has been successfully finished, X queries executed."**

---

**6. Verify Database (Cek Isi Database):**

   - Klik database **`elara_space`** di sidebar kiri
   - Harusnya ada **10+ tables** yang muncul:
     - `activity_logs`
     - `book_requests`
     - `books`
     - `borrowings`
     - `fines`
     - `notifications`
     - `publishers`
     - `settings`
     - `universities`
     - `users`

   - Klik tabel **`users`** → klik **"Browse"**
   - ✅ Harusnya ada 1 row (admin account)
   - ✅ Klik tabel **`universities`** → harusnya ada 5 rows (UPI, UNPAD, UIN, UMB, IKOPIN)

**Kalau semua ada, berarti database sudah SUCCESS!** ✅

---

### STEP 4: Konfigurasi Aplikasi (Optional - Biasanya Sudah OK!)

**Waktu:** ±2 menit

**Kapan perlu cek config?**
- Kalau muncul error "Connection failed" saat buka aplikasi
- Kalau pakai MySQL dengan password (non-default)

---

**1. Cek File Konfigurasi Database:**

**Cara buka file:**
   - Buka Windows Explorer
   - **Laragon:** `C:\laragon\www\elara-space\config\database.php`
   - **XAMPP:** `C:\xampp\htdocs\elara-space\config\database.php`
   - Klik kanan → **Open with** → **Notepad** (atau VSCode/Notepad++ kalau ada)

**Setting yang benar:**
```php
<?php
class Database {
    private $host = "localhost";      // ← Jangan diubah
    private $db_name = "elara_space";  // ← Nama database (harus sama!)
    private $username = "root";        // ← Default Laragon/XAMPP
    private $password = "";            // ← KOSONG untuk default!
    // ...
}
```

**⚠️ PENTING:**
- Password harus **KOSONG** (`""`) untuk Laragon & XAMPP default
- Kalau kamu ubah password MySQL, sesuaikan di sini
- **Jangan ada spasi** di dalam quotes!

---

**2. Cek File Config Utama (Optional):**

   - File: `config/config.php`
   - Pastikan `SITE_URL` benar:

   ```php
   // Untuk Laragon:
   define('SITE_URL', 'http://elara-space.test');

   // Untuk XAMPP:
   define('SITE_URL', 'http://localhost/elara-space');
   ```

   > **Note:** Biasanya sudah otomatis benar, gak perlu diubah!

---

### STEP 5: Jalankan Aplikasi 🎉

**Waktu:** ±1 menit

---

**1. Pastikan Web Server Running:**

**Untuk Laragon:**
   - Buka aplikasi **Laragon**
   - Klik **"Start All"** (kanan bawah)
   - ✅ Apache & MySQL harus **HIJAU**

**Untuk XAMPP:**
   - Buka **XAMPP Control Panel**
   - ✅ Apache & MySQL harus **Running** (hijau)

---

**2. Akses Aplikasi di Browser:**

Buka browser (Chrome/Firefox/Edge) dan ketik URL berikut:

**Untuk Laragon (Auto Virtual Host):**
```
http://elara-space.test
```

**Untuk XAMPP (atau kalau Laragon virtual host error):**
```
http://localhost/elara-space
```

**Untuk Laragon dengan Port Custom (misal 8080):**
```
http://localhost:8080/elara-space
```

---

**3. Cek Tampilan Homepage:**

✅ **Kalau BERHASIL, harusnya muncul:**
   - Hero carousel "Selamat Datang di Elara Space"
   - Kategori buku (Manajemen, Akuntansi, Ekonomi, Bisnis, Keuangan)
   - Cards "Buku Baru", "Buku Populer", "E-Book Gratis"
   - Footer dengan info universitas

❌ **Kalau ERROR:**
   - **"This site can't be reached"** → Cek Laragon/XAMPP sudah Start All
   - **"Connection failed"** → Cek config/database.php, pastikan MySQL running
   - **"404 Not Found"** → Cek folder location, harus di www/elara-space atau htdocs/elara-space
   - **Halaman blank** → Cek error di `C:\laragon\www\elara-space\error.log` atau PHP error logs

---

**4. Login Pertama Kali:**

**Klik tombol "Masuk" atau langsung akses:**
```
http://elara-space.test/login.php
```

**Akun Super Admin (Default):**
   - **Email:** `admin@elaraspace.com`
   - **Password:** `password`

   > ⚠️ **PENTING:** Password ini case-sensitive! Huruf kecil semua!

**Setelah login berhasil:**
   - ✅ Kamu akan diarahkan ke **Dashboard Admin**
   - ✅ Lihat statistik: Total buku, Peminjaman aktif, Total user
   - ✅ Menu sidebar: Manajemen Buku, Peminjaman, User, dll

**5. Ganti Password Default (WAJIB!):**
```
Dashboard Admin → Klik nama (kanan atas) → Profile → Change Password
```

---

## 🎊 SELAMAT! Instalasi Selesai!

Aplikasi Elara Space sekarang sudah berjalan di laptop kamu! 🚀

**Next Steps:**
1. ✅ Explore semua fitur admin
2. ✅ Buat akun user dummy untuk testing
3. ✅ Tambah buku-buku dummy
4. ✅ Test flow peminjaman end-to-end
5. ✅ Baca **DEMO_CHEATSHEET.md** untuk persiapan presentasi

---

## 🎯 Cara Menggunakan Aplikasi (User Flow)

### 👤 Untuk Mahasiswa/Dosen/Staff

#### 1. **Registrasi Akun Baru**
```
Homepage → Klik "Daftar" → Isi Form:
- Nama Lengkap
- Email (.edu atau email kampus)
- Password
- Pilih Universitas (UPI/UNPAD/UIN/UMB/IKOPIN)
- Pilih Role (Mahasiswa/Dosen/Staff)
→ Klik "Daftar" → Login dengan akun baru
```

#### 2. **Browse & Cari Buku**
```
Dashboard User → Menu "Browse Buku" →
- Gunakan Search Bar untuk cari judul/penulis
- Filter berdasarkan Kategori (Manajemen, Akuntansi, dll)
- Lihat detail buku (klik card buku)
```

#### 3. **Pinjam Buku**
```
Detail Buku → Cek ketersediaan (Available: X) →
Klik "Ajukan Peminjaman" → Isi Formulir:
- Tanggal Pinjam: (otomatis hari ini)
- Tanggal Kembali: (max 14 hari)
→ Submit → Tunggu approval admin
```

#### 4. **Cek Status Peminjaman**
```
Dashboard → Menu "Riwayat Peminjaman" →
Lihat status: Pending / Approved / Rejected / Returned
```

#### 5. **Request Buku Baru (yang belum ada)**
```
Dashboard → Menu "Request Buku" →
Isi Form:
- Judul Buku
- Penulis
- Penerbit (pilih: Gramedia/Erlangga/dll)
- Alasan Request
→ Submit → Admin akan review
```

#### 6. **Perpanjang Peminjaman**
```
Riwayat Peminjaman → Pilih buku aktif →
Klik "Perpanjang" → Extend +7 hari → Submit
```

#### 7. **Lihat Denda (jika terlambat)**
```
Dashboard → Menu "Denda Saya" →
Lihat denda yang harus dibayar (Rp 2.000/hari)
```

---

### 👨‍💼 Untuk Admin

#### 1. **Login sebagai Admin**
```
Homepage → Login →
Email: admin@elaraspace.com
Password: password
→ Masuk ke Dashboard Admin
```

#### 2. **Kelola Buku (CRUD)**

**Tambah Buku Baru:**
```
Dashboard Admin → Menu "Manajemen Buku" →
Klik "Tambah Buku Baru" → Isi Form:
- Judul, Penulis, ISBN
- Kategori (dropdown)
- Penerbit
- Tahun Terbit
- Jumlah Stok
- Upload Cover (JPG/PNG, max 2MB)
→ Simpan
```

**Edit/Hapus Buku:**
```
Manajemen Buku → Cari buku →
Klik "Edit" (ubah data) atau "Hapus"
```

#### 3. **Approve/Reject Peminjaman**
```
Dashboard Admin → Menu "Peminjaman & Pengembalian" →
Tab "Pending Approval" →
Lihat detail request → Cek ketersediaan stok →
Klik "Approve" atau "Reject" (dengan alasan)
```

#### 4. **Proses Pengembalian Buku**
```
Peminjaman & Pengembalian → Tab "Aktif" →
Pilih peminjaman yang dikembalikan →
Klik "Proses Pengembalian" →
- Cek kondisi buku
- Sistem auto-calculate denda (jika telat)
→ Konfirmasi Pengembalian
```

#### 5. **Approve Request Buku Baru**
```
Dashboard Admin → Menu "Request Buku" →
Lihat pending requests → Klik detail →
Review alasan user →
Klik "Approve" → Pilih tindakan:
  - Order dari Penerbit
  - Reject (dengan alasan)
→ Update status: Approved → Ordered → Received
```

#### 6. **Kelola User (Mahasiswa/Dosen)**
```
Dashboard Admin → Menu "Manajemen User" →
- Lihat semua user
- Aktifkan/Nonaktifkan akun
- Hapus user (jika perlu)
- Export data user (CSV/Excel)
```

#### 7. **Lihat Laporan & Analytics**
```
Dashboard Admin → Menu "Laporan & Analytics" →
Pilih periode: Hari ini / Minggu ini / Bulan ini →
Lihat:
- Total peminjaman
- Buku terpopuler
- Top borrowers
- Revenue dari denda
- Grafik trend peminjaman
→ Export ke PDF/Excel
```

#### 8. **Kelola Penerbit Partner**
```
Dashboard Admin → Menu "Manajemen Penerbit" →
Tambah/Edit/Hapus penerbit:
- Nama Penerbit
- Kontak Person
- Email & Telepon
- Alamat
```

#### 9. **Activity Logs (Audit Trail)**
```
Dashboard Admin → Menu "Activity Logs" →
Lihat semua aktivitas sistem:
- User login/logout
- Buku ditambah/diedit/dihapus
- Peminjaman approved/rejected
- Filter berdasarkan user/tanggal
```

---

## 🔍 Troubleshooting

### ❌ Error: "Connection failed"
**Solusi:**
- Pastikan Laragon running (Start All)
- Cek MySQL sudah hijau
- Cek `config/database.php` → password harus kosong untuk Laragon

### ❌ Halaman Blank/Error 500
**Solusi:**
- Cek file `config/config.php` ada atau tidak
- Restart Laragon (Stop All → Start All)
- Cek error log di `C:\laragon\www\elara-space\logs\`

### ❌ Upload Cover Buku Gagal
**Solusi:**
- Pastikan folder `uploads/book_covers/` ada dan bisa ditulis
- Cek size gambar (max 2MB)
- Format harus JPG/PNG

### ❌ Tidak Bisa Login
**Solusi:**
- Pastikan database sudah diimport
- Cek tabel `users` ada data admin
- Reset password via phpMyAdmin jika lupa

---

## 📧 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | admin@elaraspace.com | password |

**⚠️ WAJIB:** Ganti password default setelah login pertama!

---

## 🎬 Video Tutorial (Coming Soon)

- [ ] Tutorial Instalasi Lengkap
- [ ] Demo Admin Panel
- [ ] Demo User Flow (Mahasiswa)
- [ ] Cara Request Buku Baru

## 🗂️ Struktur Folder

```
elara-space/
├── admin/                  # Admin panel
│   ├── books/             # Manajemen buku
│   ├── borrowing/         # Manajemen peminjaman
│   ├── requests/          # Manajemen request buku
│   ├── users/             # Manajemen user
│   ├── publishers/        # Manajemen penerbit
│   ├── reports/           # Laporan & analytics
│   └── includes/          # Sidebar & topbar admin
├── user/                   # User panel
│   ├── includes/          # Sidebar & topbar user
│   ├── books.php          # Browse buku
│   ├── borrowing.php      # Riwayat peminjaman
│   ├── request-book.php   # Form request buku
│   └── requests.php       # List request
├── assets/
│   ├── css/
│   │   └── style.css      # Custom stylesheet
│   └── images/
├── config/
│   ├── config.php         # Konfigurasi utama
│   └── database.php       # Koneksi database
├── includes/
│   └── functions.php      # Helper functions
├── uploads/               # Upload folder
│   ├── book_covers/       # Cover buku
│   └── profiles/          # Foto profil
├── database.sql           # Database schema
├── index.php             # Entry point
├── login.php             # Login page
├── register.php          # Register page
├── logout.php            # Logout handler
└── README.md             # Dokumentasi
```

## 👥 Role & Permissions

### Super Admin
- Full access ke semua fitur
- Manage settings dan universities
- Manage semua admin

### Admin
- Manage books, borrowing, requests
- Manage users di university-nya
- View reports & analytics

### Mahasiswa/Dosen/Staff
- Browse dan borrow buku
- Request buku baru
- View history dan fines
- Manage profil

## 🔧 Konfigurasi

### Settings (Super Admin Only)

Edit di `admin/settings/` atau langsung di database table `settings`:

```sql
-- Durasi peminjaman default (hari)
borrow_duration_days = 14

-- Maksimal buku yang bisa dipinjam
max_borrow_books = 3

-- Denda per hari (Rupiah)
fine_per_day = 2000

-- Maksimal perpanjangan
max_extension = 1

-- Durasi perpanjangan (hari)
extension_duration_days = 7
```

## 📊 Database Schema

### Tabel Utama

- **universities** - Data 5 universitas
- **users** - Semua user (admin, mahasiswa, dosen, staff)
- **books** - Data buku perpustakaan
- **publishers** - Penerbit partner
- **borrowings** - Transaksi peminjaman
- **book_requests** - Request buku dari user
- **fines** - Denda keterlambatan
- **notifications** - Notifikasi user
- **activity_logs** - Log aktivitas sistem
- **settings** - Pengaturan sistem

Lihat detail schema di file `database.sql`

## 🚀 Penggunaan

### Workflow Peminjaman

1. User browse buku yang tersedia
2. User mengajukan peminjaman (via admin)
3. Admin approve dan catat peminjaman
4. Sistem auto-calculate due date
5. Notifikasi reminder H-3 sebelum jatuh tempo
6. User mengembalikan buku
7. Sistem kalkulasi denda jika terlambat

### Workflow Request Buku

1. User request buku yang tidak tersedia
2. Admin review request
3. Admin approve/reject
4. Jika approved, admin order dari penerbit
5. Setelah buku diterima, admin update status
6. Buku ditambahkan ke inventory
7. User mendapat notifikasi

## 🎨 Customization

### Mengganti Logo/Nama

Edit di `config/config.php`:
```php
define('SITE_NAME', 'Elara Space');
define('SITE_URL', 'http://localhost/elara-space');
```

### Mengubah Warna Tema

Edit di `assets/css/style.css`:
```css
:root {
    --primary-color: #4e73df;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    /* ... */
}
```

## 🔒 Keamanan

- Password di-hash menggunakan PHP `password_hash()`
- Prepared statements untuk mencegah SQL injection
- Session timeout otomatis
- XSS protection dengan `htmlspecialchars()`
- CSRF protection (recommended untuk production)
- File upload validation

## 📱 Responsive Design

Sistem fully responsive dan mobile-friendly menggunakan Bootstrap 5. Bisa diakses dari:
- Desktop
- Tablet
- Mobile devices

## 🐛 Troubleshooting

### Error: Connection failed
- Cek konfigurasi database di `config/database.php`
- Pastikan MySQL service running
- Cek username dan password database

### Upload tidak berfungsi
- Pastikan folder `uploads/` memiliki permission write (777)
- Cek `MAX_FILE_SIZE` di `config/config.php`

### Session timeout terus
- Increase `SESSION_LIFETIME` di `config/config.php`
- Cek setting session di `php.ini`

## 📝 To-Do / Future Enhancement

- [ ] Export reports ke PDF/Excel
- [ ] Email notifications
- [ ] Barcode scanner untuk buku
- [ ] Mobile app (React Native)
- [ ] Integration dengan payment gateway
- [ ] QR Code untuk borrowing
- [ ] Advanced analytics & charts
- [ ] Multi-language support

## 👨‍💻 Developer Team

Sesuai struktur organisasi:

- **CEO & IT (HR)** - ALIFA
  - Strategic planning & IT oversight

- **IT/Sistem Informasi** - Tim IT
  - Maintain performance & security
  - Develop new features
  - Backup & updates

- **CEO** - Leadership
  - Vision & strategy
  - Operational oversight
  - Partnership management

- **Pemasaran & Keuangan** - TASYA
  - Content strategy
  - Financial management
  - Budgeting & reporting

- **Layanan & Logistik** - IQTAM
  - Handle book requests
  - Manage logistics
  - Digital archive management

## 📄 License

This project is for educational purposes.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📞 Support

Untuk bantuan dan support:
- Email: admin@elaraspace.com
- Create an issue di GitHub repository

---

**Made with ❤️ for Faculty of Economics - UPI, UNPAD, UIN, UMB, IKOPIN**
