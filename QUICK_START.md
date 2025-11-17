# ⚡ Quick Start Guide - Elara Space

> **Panduan Cepat untuk Demo/Presentasi di Laptop Baru**

---

## 📋 Checklist Instalasi (30 Menit)

### ☑️ STEP 1: Install Laragon (10 menit)

**Download & Install:**
```
1. Download: https://laragon.org/download/
   - Pilih: Laragon - Full (Windows)
   - Size: ±200MB
   - Include: PHP 8.1, MySQL 8.0, Apache 2.4

2. Install:
   - Jalankan installer
   - Install di C:\laragon (default)
   - Next → Next → Install
   - Tunggu ±5-10 menit

3. Start:
   - Buka Laragon dari Start Menu
   - Klik "Start All"
   - Tunggu Apache & MySQL HIJAU ✅
```

**Alternative: XAMPP**
```
Download: https://www.apachefriends.org/download.html
- Pilih PHP 8.1 atau 8.2
- Size: ±160MB
- Install di C:\xampp
- Start Apache & MySQL
```

---

### ☑️ STEP 2: Download Project (5 menit)

**OPSI A: Via Git (Recommended)**
```bash
# Install Git dulu (kalau belum ada):
https://git-scm.com/download/win

# Clone repository:
cd C:\laragon\www
git clone https://github.com/Muhamadafat/elara_space.git elara-space
```

**OPSI B: Download ZIP Manual**
```
1. Kunjungi: https://github.com/Muhamadafat/elara_space
2. Klik "Code" → "Download ZIP"
3. Extract file ZIP
4. Rename folder: elara_space-main → elara-space
5. Copy ke:
   - Laragon: C:\laragon\www\elara-space\
   - XAMPP: C:\xampp\htdocs\elara-space\
```

---

### ☑️ STEP 3: Setup Database (10 menit)

**Detail Steps:**
```
1. Pastikan Laragon/XAMPP running (Apache & MySQL hijau)

2. Buka phpMyAdmin:
   - URL: http://localhost/phpmyadmin
   - Login: root (password KOSONG)

3. Create Database:
   - Klik tab "Databases"
   - Database name: elara_space
   - Collation: utf8mb4_general_ci
   - Klik "Create"

4. Import Database:
   - Klik database "elara_space" (di sidebar kiri)
   - Klik tab "Import"
   - Choose File: C:\laragon\www\elara-space\database.sql
   - Klik "Go"
   - Tunggu ±10-30 detik
   - ✅ Success!

5. Verify:
   - Cek ada 10+ tables (users, books, borrowings, dll)
   - Klik table "users" → Browse → ada 1 admin
```

---

### ☑️ STEP 4: Jalankan Aplikasi (2 menit)

**Akses Aplikasi:**
```
1. Pastikan Laragon/XAMPP masih running

2. Buka browser, ketik URL:
   - Laragon: http://elara-space.test
   - XAMPP: http://localhost/elara-space

3. Login Admin:
   - Email: admin@elaraspace.com
   - Password: password

4. ✅ Harusnya masuk ke Dashboard Admin!
```

---

## 📥 Download Links (Semua Software)

| Software | Link | Size |
|----------|------|------|
| **Laragon Full** | https://laragon.org/download/ | 200MB |
| **XAMPP** | https://www.apachefriends.org/download.html | 160MB |
| **Git for Windows** | https://git-scm.com/download/win | 50MB |
| **Visual Studio Code** (Optional) | https://code.visualstudio.com/ | 80MB |
| **Chrome Browser** | https://www.google.com/chrome/ | 90MB |

---

## 🔧 Versi Software (Included)

| Component | Laragon | XAMPP |
|-----------|---------|-------|
| PHP | 8.1.10 | 8.2.12 |
| MySQL | 8.0.30 | 8.0.36 |
| Apache | 2.4.54 | 2.4.58 |
| phpMyAdmin | 5.2.0 | 5.2.1 |

✅ **Semua versi di atas sudah SUPPORT aplikasi Elara Space!**

---

## 🎯 Demo Flow (Untuk Presentasi)

### 🟦 DEMO 1: Fitur User (Mahasiswa)

```
1. REGISTRASI
   Homepage → Daftar → Isi form → Login

2. BROWSE BUKU
   Dashboard → Browse Buku → Filter kategori → Cari judul

3. PINJAM BUKU
   Pilih buku → Detail → Ajukan Peminjaman → Submit

4. REQUEST BUKU BARU
   Menu Request → Isi form (judul, penulis, penerbit) → Submit
```

---

### 🟥 DEMO 2: Fitur Admin

```
1. LOGIN ADMIN
   Logout user → Login: admin@elaraspace.com / password

2. TAMBAH BUKU
   Manajemen Buku → Tambah Baru → Isi form → Upload cover → Simpan

3. APPROVE PEMINJAMAN
   Peminjaman & Pengembalian → Pending → Review → Approve

4. PROSES PENGEMBALIAN
   Aktif → Pilih peminjaman → Proses Pengembalian → Auto-hitung denda

5. APPROVE REQUEST BUKU
   Request Buku → Pending → Review → Approve → Order

6. LIHAT LAPORAN
   Laporan & Analytics → Pilih periode → Lihat grafik → Export PDF
```

---

## 🚨 Troubleshooting Cepat

| Problem | Solution |
|---------|----------|
| Laragon tidak start | Run as Administrator |
| Database connection error | Cek MySQL hijau, restart Laragon |
| 404 Not Found | Cek folder di `C:\laragon\www\elara-space\` |
| Cannot login | Pastikan database sudah diimport |
| Upload error | Cek folder `uploads/` ada |

---

## 📱 URLs Penting

| Service | URL |
|---------|-----|
| **Aplikasi** | http://elara-space.test |
| **phpMyAdmin** | http://localhost/phpmyadmin |
| **Laragon Menu** | Klik kanan icon Laragon (system tray) |

---

## 🔑 Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@elaraspace.com | password |

---

## 📊 Data Demo (Sudah ada di database.sql)

- ✅ 5 Universitas (UPI, UNPAD, UIN, UMB, IKOPIN)
- ✅ 1 Admin account
- ✅ Sample books (50+ buku)
- ✅ Sample publishers (Gramedia, Erlangga, dll)
- ✅ Sample categories (Manajemen, Akuntansi, Ekonomi, dll)

---

## 🎬 Skenario Demo Lengkap (15 menit)

### Menit 1-3: Introduction
```
"Elara Space adalah sistem manajemen perpustakaan digital
untuk 5 universitas: UPI, UNPAD, UIN, UMB, dan IKOPIN.

Sistem ini memiliki 2 role utama:
1. User (Mahasiswa/Dosen/Staff) - bisa pinjam & request buku
2. Admin - kelola buku, approve peminjaman, tracking
```

### Menit 4-6: Demo User Flow
```
1. Show homepage → carousel & categories
2. Register sebagai mahasiswa
3. Login → browse buku → filter kategori
4. Pilih buku → ajukan peminjaman
5. Request buku baru (belum tersedia)
```

### Menit 7-12: Demo Admin Flow
```
1. Logout → Login sebagai admin
2. Dashboard: statistik real-time (peminjaman, buku, user)
3. Tambah buku baru (CRUD)
4. Approve peminjaman dari user tadi
5. Simulate pengembalian → auto-calculate denda
6. Approve request buku → update status
7. Lihat laporan & analytics → export PDF
```

### Menit 13-15: Highlight Features
```
1. Activity Logs: tracking semua aktivitas
2. Multi-university support
3. Publisher partnership (request marketplace)
4. Auto denda calculation
5. Notification system
6. Responsive design (mobile-friendly)
```

---

## 💡 Tips Presentasi

✅ **DO:**
- Prepare data dummy sebelumnya (tambah buku, user)
- Test semua flow sebelum demo
- Buka 2 browser (Chrome = User, Edge = Admin)
- Highlight unique features (request buku, multi-university)

❌ **DON'T:**
- Jangan restart Laragon saat presentasi
- Jangan ubah config/database saat demo
- Jangan skip login credentials (tunjukkan security)

---

## 📞 Emergency Contacts

| Issue | Action |
|-------|--------|
| Laragon crash | Restart laptop, Start All |
| Database corrupt | Re-import database.sql |
| Config error | Check config/database.php |
| Apache busy | Stop All → Start All |

---

**⏱️ Total Setup Time: ~30 menit**
**👥 Recommended Team: 2-3 orang (1 presenter, 1 backup/controller)**

**Good luck with your presentation! 🚀**
