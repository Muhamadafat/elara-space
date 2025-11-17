# 📄 Elara Space - Demo Cheat Sheet

> **Print ini dan taruh di samping laptop saat presentasi!**

---

## ⚡ INSTALASI CEPAT

```bash
1. Install Laragon → Start All
2. Download/Clone project ke C:\laragon\www\elara-space\
3. phpMyAdmin → Create DB "elara_space" → Import database.sql
4. Buka: http://elara-space.test
```

---

## 🔑 LOGIN

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@elaraspace.com | password |
| **User** | (Register dulu) | - |

---

## 🎯 DEMO SCRIPT (15 MENIT)

### 👤 USER FLOW (5 menit)

**1. Registrasi & Login**
```
Homepage → Daftar → [Nama, Email, Pass, Universitas, Role] → Login
```

**2. Browse & Pinjam Buku**
```
Dashboard → Browse Buku → Pilih kategori → Detail buku → Ajukan Peminjaman
```

**3. Request Buku Baru**
```
Menu "Request Buku" → [Judul, Penulis, Penerbit, Alasan] → Submit
```

**4. Cek Riwayat**
```
Riwayat Peminjaman → Lihat status (Pending/Approved)
```

---

### 👨‍💼 ADMIN FLOW (8 menit)

**1. Dashboard Overview**
```
Login admin → Lihat stats (Total buku, Peminjaman aktif, User)
```

**2. Manajemen Buku (CRUD)**
```
Manajemen Buku → Tambah Baru → [Judul, Penulis, Kategori, Stok, Cover] → Simpan
Edit: Klik Edit → Ubah data → Update
Hapus: Klik Hapus → Konfirmasi
```

**3. Approve Peminjaman**
```
Peminjaman & Pengembalian → Tab "Pending" → Review → Approve/Reject
```

**4. Proses Pengembalian**
```
Tab "Aktif" → Pilih peminjaman → Proses Pengembalian → Auto-hitung denda
```

**5. Manage Request Buku**
```
Request Buku → Pending → Detail → Approve → Status: Ordered → Received
```

**6. Laporan & Analytics**
```
Laporan → Pilih periode → Lihat grafik → Export PDF/Excel
```

**7. Activity Logs**
```
Activity Logs → Filter user/tanggal → Audit trail
```

---

## 🌟 HIGHLIGHT FEATURES (2 menit)

✅ **Multi-University Support** (5 universitas)
✅ **Publisher Partnership** (Gramedia, Erlangga, dll)
✅ **Book Request Marketplace** (User bisa request buku baru)
✅ **Auto Fine Calculation** (Denda otomatis Rp 2.000/hari)
✅ **Real-time Analytics** (Dashboard stats)
✅ **Activity Audit Trail** (Track semua aktivitas)
✅ **Role-based Access** (Admin, Mahasiswa, Dosen, Staff)
✅ **Responsive Design** (Mobile-friendly)

---

## 🚨 TROUBLESHOOTING

| Problem | Quick Fix |
|---------|-----------|
| Laragon not start | Run as Admin |
| DB error | Restart Laragon |
| 404 | Check folder location |
| Can't login | Re-import database.sql |

---

## 📱 QUICK URLS

```
App:        http://elara-space.test
phpMyAdmin: http://localhost/phpmyadmin
```

---

## 💡 PRESENTATION TIPS

1. **Buka 2 browser:** Chrome (User) + Edge (Admin)
2. **Prepare dummy data** sebelum demo
3. **Highlight unique:** Request marketplace, multi-university
4. **Show mobile:** Buka DevTools → Toggle device toolbar
5. **Emphasize security:** Password hash, SQL injection prevention

---

## 📊 PRE-LOADED DATA

- 5 Universities ✅
- 50+ Sample Books ✅
- 5 Publishers ✅
- 1 Admin Account ✅
- Multiple Categories ✅

---

## ⏱️ TIMING

| Section | Time |
|---------|------|
| Intro | 1-2 min |
| User Demo | 5 min |
| Admin Demo | 8 min |
| Q&A | 2 min |
| **TOTAL** | **15-17 min** |

---

## 🎬 OPENING LINE

> "Selamat pagi/siang. Saya akan mempresentasikan Elara Space,
> sistem manajemen perpustakaan digital yang dirancang khusus
> untuk fakultas ekonomi di 5 universitas: UPI, UNPAD, UIN, UMB,
> dan IKOPIN. Sistem ini memiliki fitur kolaborasi dengan penerbit
> seperti Gramedia dan Erlangga melalui sistem request buku."

---

## 🏁 CLOSING LINE

> "Terima kasih. Elara Space menawarkan solusi modern untuk
> manajemen perpustakaan dengan fitur request buku dari penerbit,
> sistem denda otomatis, dan tracking lengkap. Sistem ini dapat
> di-scale untuk universitas lain dan dikembangkan lebih lanjut.
> Apakah ada pertanyaan?"

---

**Good Luck! 🚀**
