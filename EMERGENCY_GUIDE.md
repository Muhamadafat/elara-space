# 🆘 Emergency Guide - Elara Space

> **Backup plan kalau ada masalah pas presentasi/demo!**

---

## 🚨 BEFORE PRESENTATION CHECKLIST

### 1 Hari Sebelum Presentasi:

```
☑️ Install Laragon di laptop yang akan digunakan
☑️ Clone/Download project
☑️ Import database & test semua fitur
☑️ Tambah 5-10 buku dummy (dengan cover menarik)
☑️ Bikin 2-3 akun user dummy (mahasiswa)
☑️ Test login admin & user
☑️ Screenshot semua halaman penting (backup kalau demo gagal)
☑️ Export database (backup.sql)
☑️ Charge laptop full battery
☑️ Print DEMO_CHEATSHEET.md
```

### 30 Menit Sebelum Presentasi:

```
☑️ Restart laptop (fresh start)
☑️ Close semua aplikasi tidak penting
☑️ Start Laragon → tunggu hijau
☑️ Test akses: http://elara-space.test
☑️ Buka 2 browser:
   - Chrome: Login sebagai user
   - Edge/Firefox: Login sebagai admin
☑️ Test internet connection (untuk load Bootstrap CDN)
☑️ Set brightness laptop ke 80-100%
☑️ Turn off notifications (Focus Assist)
☑️ Close Telegram, WhatsApp, Email
```

---

## 🔥 EMERGENCY SCENARIOS & SOLUTIONS

### ❌ SCENARIO 1: Laragon Tidak Mau Start

**Symptoms:**
- Apache/MySQL tidak hijau
- Error "Port already in use"

**Solutions:**
1. **Quick Fix (30 detik):**
   ```
   - Stop Laragon
   - Task Manager → End task: httpd.exe, mysqld.exe
   - Start Laragon lagi
   ```

2. **If still not working (2 menit):**
   ```
   - Restart laptop
   - Run Laragon as Administrator
   - Start All
   ```

3. **Nuclear Option (5 menit):**
   ```
   - Uninstall Laragon
   - Reinstall Laragon - Full
   - Copy backup folder elara-space ke C:\laragon\www\
   - Import backup.sql ke phpMyAdmin
   ```

**Backup Plan:**
> Gunakan screenshot + explain secara verbal sambil tunjukkan code

---

### ❌ SCENARIO 2: Database Connection Failed

**Symptoms:**
- Error "Connection failed"
- Halaman blank/error 500

**Solutions:**
1. **Check MySQL (30 detik):**
   ```
   Laragon → MySQL harus hijau
   Kalau merah: Stop All → Start All
   ```

2. **Check Config (1 menit):**
   ```
   Buka: config/database.php
   Pastikan:
     $host = "localhost";
     $username = "root";
     $password = ""; // HARUS KOSONG!
   ```

3. **Re-import Database (3 menit):**
   ```
   phpMyAdmin → Drop database elara_space
   Create database baru: elara_space
   Import: backup.sql atau database.sql
   ```

**Backup Plan:**
> Show phpMyAdmin dengan data yang sudah ada, explain flow secara manual

---

### ❌ SCENARIO 3: Aplikasi 404 Not Found

**Symptoms:**
- Browser show "404 Not Found"
- "This site can't be reached"

**Solutions:**
1. **Check URL (10 detik):**
   ```
   Pastikan pakai:
   ✅ http://elara-space.test
   atau
   ✅ http://localhost/elara-space

   BUKAN:
   ❌ https://elara-space.test (https = error)
   ❌ http://github.com/... (ini GitHub, bukan app!)
   ```

2. **Check Folder (1 menit):**
   ```
   Windows Explorer → C:\laragon\www\
   Pastikan ada folder: elara-space
   Dan di dalamnya ada: index.php
   ```

3. **Restart Apache (30 detik):**
   ```
   Laragon → Stop All → Start All
   ```

**Backup Plan:**
> Gunakan localhost/elara-space instead of virtual host

---

### ❌ SCENARIO 4: Cannot Login (Admin/User)

**Symptoms:**
- Login form submit tapi balik lagi ke login
- Error "Invalid credentials"

**Solutions:**
1. **Check Credentials (10 detik):**
   ```
   Email: admin@elaraspace.com
   Password: password
   (case-sensitive!)
   ```

2. **Check Database (1 menit):**
   ```
   phpMyAdmin → elara_space → users table
   Pastikan ada row dengan email: admin@elaraspace.com
   ```

3. **Reset Admin Password (2 menit):**
   ```sql
   phpMyAdmin → SQL tab → Run:

   UPDATE users
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE email = 'admin@elaraspace.com';

   -- Password jadi: password
   ```

**Backup Plan:**
> Explain flow dengan screenshot, skip demo login

---

### ❌ SCENARIO 5: Upload Cover Buku Gagal

**Symptoms:**
- Error saat upload gambar
- "Failed to upload file"

**Solutions:**
1. **Check Folder (1 menit):**
   ```
   C:\laragon\www\elara-space\uploads\book_covers\
   Pastikan folder ini ADA dan KOSONG (atau ada file)

   Kalau tidak ada:
   Buat folder manual: uploads\book_covers\
   ```

2. **Check File Size (10 detik):**
   ```
   Gunakan gambar kecil (< 2MB)
   Format: JPG atau PNG
   ```

**Backup Plan:**
> Tambah buku tanpa cover (skip upload), atau gunakan URL image online

---

### ❌ SCENARIO 6: Internet Mati (Bootstrap CSS tidak load)

**Symptoms:**
- Tampilan jelek/rusak
- Tidak ada styling
- Icon tidak muncul

**Solutions:**
1. **Check Internet (10 detik):**
   ```
   Buka google.com di tab baru
   Kalau tidak bisa: pakai hotspot HP
   ```

2. **Use Offline Assets (NOT RECOMMENDED - needs prep):**
   ```
   Download Bootstrap & Icons sebelumnya
   Simpan ke assets/css/
   Edit index.php → ganti CDN dengan local path
   ```

**Backup Plan:**
> Tetap demo (tampilan rusak tapi functional), explain "normally styled with Bootstrap"

---

### ❌ SCENARIO 7: Laptop Hang/Freeze

**Symptoms:**
- Laptop tidak respon
- Mouse/keyboard tidak jalan

**Solutions:**
1. **Force Restart (1 menit):**
   ```
   Tahan tombol Power 10 detik
   Restart laptop
   Start Laragon → Start All
   ```

**Backup Plan:**
> Switch ke laptop backup (siapkan 2 laptop kalau penting!)
> Atau: lanjut dengan slide PowerPoint + screenshot

---

### ❌ SCENARIO 8: Lupa Demo Script

**Symptoms:**
- Blank, tidak tahu harus demo apa
- Skip fitur penting

**Solutions:**
1. **Lihat DEMO_CHEATSHEET.md (yang sudah diprint)**
2. **Follow flow ini:**
   ```
   1. Show homepage (introduce app)
   2. Register user → browse → pinjam buku
   3. Login admin → lihat dashboard
   4. Approve peminjaman tadi
   5. Tambah buku baru
   6. Lihat laporan
   7. Highlight: request buku feature
   8. Done!
   ```

---

## 🎯 ULTIMATE BACKUP PLAN

**Kalau SEMUA fail:**

1. **Gunakan Screenshot Gallery**
   ```
   - Screenshot semua halaman penting SEBELUM presentasi
   - Simpan di PowerPoint/Google Slides
   - Present dengan screenshot + explain
   ```

2. **Video Recording**
   ```
   - Record demo sukses sebelumnya (pakai OBS/Screen Recorder)
   - Play video kalau live demo gagal
   ```

3. **GitHub Readme**
   ```
   - Open README.md di GitHub (formatted nicely)
   - Show screenshots & user flow dari README
   ```

4. **Verbal Explanation**
   ```
   - Explain architecture & flow pakai whiteboard
   - Show ERD (database schema)
   - Show code snippets
   ```

---

## 📱 EMERGENCY CONTACTS

| Who | When | How |
|-----|------|-----|
| IT Support | Laragon/tech issue | Call/WA before presentation |
| Teammate | Backup presenter | Stand by di ruangan |
| Instructor | Izin restart/delay | Inform immediately |

---

## 🔋 BATTERY BACKUP

```
1. Charge laptop FULL sebelum presentasi
2. Bawa charger + extension cord
3. Test stop kontak di ruang presentasi
4. Set battery mode: "Best Performance"
5. Close heavy apps (Photoshop, etc)
```

---

## 📋 FINAL CHECKLIST (5 MIN BEFORE)

```
☑️ Laragon running & hijau
☑️ http://elara-space.test accessible
☑️ Login admin berhasil
☑️ Login user berhasil
☑️ 2 browser terbuka & ready
☑️ Demo cheatsheet di samping laptop
☑️ Notifications OFF
☑️ Brightness 80-100%
☑️ Battery > 50% or plugged in
☑️ Internet connected
☑️ Backup screenshot ready
```

---

## 💡 PRO TIPS

✅ **Do:**
- Practice demo 3-5x sebelumnya
- Time yourself (target 15 menit)
- Prepare backup laptop (if possible)
- Arrive early (setup & test)
- Breathe & stay calm

❌ **Don't:**
- Update Windows sebelum presentasi
- Install software baru H-1
- Clear browser cache (mungkin login saved)
- Restart Laragon saat presentasi (kalau tidak perlu)
- Panic kalau ada error kecil

---

## 🎭 RECOVERY PHRASES

Kalau ada error pas demo, pakai kalimat ini:

> "Normally ini harusnya langsung muncul, tapi untuk saat ini saya akan
> explain prosesnya secara manual..."

> "Ini adalah technical issue yang jarang terjadi, tapi flow-nya adalah..."

> "Let me show you the screenshot of what this should look like..."

> "Sementara sistem loading, saya akan jelaskan fitur lainnya dulu..."

**NEVER SAY:**
- ❌ "Waduh error nih"
- ❌ "Kok ga jalan ya"
- ❌ "Kemarin masih bisa"
- ❌ "Rusak nih"

**ALWAYS STAY PROFESSIONAL!** 🎩

---

**Remember: Technical issues happen. How you handle it matters more than the issue itself!**

**Good luck! 🍀**
