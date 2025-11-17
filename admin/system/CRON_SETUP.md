# Auto Fine Calculator - Cron Job Setup

## Tentang
Auto Fine Calculator adalah sistem yang secara otomatis menghitung denda untuk buku-buku yang terlambat dikembalikan.

## Cara Setup Cron Job

### Linux/Unix Server

1. Buka crontab editor:
```bash
crontab -e
```

2. Tambahkan baris berikut untuk menjalankan setiap hari pada jam 00:00 (tengah malam):
```bash
0 0 * * * /usr/bin/php /path/to/elara-space/admin/system/auto-fine-calculator.php >> /path/to/logs/auto-fine.log 2>&1
```

3. Atau untuk menjalankan setiap 6 jam:
```bash
0 */6 * * * /usr/bin/php /path/to/elara-space/admin/system/auto-fine-calculator.php >> /path/to/logs/auto-fine.log 2>&1
```

### Windows Server (Task Scheduler)

1. Buka Task Scheduler
2. Klik "Create Basic Task"
3. Name: "Elara Space Auto Fine Calculator"
4. Trigger: Daily at midnight (atau sesuai kebutuhan)
5. Action: Start a program
6. Program: `C:\php\php.exe` (sesuaikan path PHP Anda)
7. Arguments: `C:\laragon\www\elara-space\admin\system\auto-fine-calculator.php`
8. Finish

### Laragon (Development)

Untuk testing di Laragon:
1. Buka terminal/command prompt
2. Navigate ke folder project:
```bash
cd C:\laragon\www\elara-space
```
3. Jalankan manual:
```bash
php admin/system/auto-fine-calculator.php
```

## Manual Trigger (Alternatif)

Jika tidak bisa setup cron job, admin dapat menjalankan secara manual dari:
- URL: `/admin/system/auto-fine-calculator.php`
- Atau dari halaman: `/admin/system/fines.php` → klik tombol "Hitung Denda Otomatis"

## Apa yang Dilakukan Script Ini?

1. Mencari semua peminjaman yang melewati due date
2. Menghitung jumlah hari keterlambatan
3. Menghitung denda (hari terlambat × denda per hari)
4. Membuat atau update record denda di database
5. Update status borrowing menjadi 'overdue'
6. Mengirim notifikasi ke user yang terkena denda

## Settings

Denda per hari dapat diatur di:
- Table: `settings`
- Key: `fine_per_day`
- Default: 2000 (Rp 2.000 per hari)

## Log Activity

Semua aktivitas auto-fine akan tercatat di:
- Table: `activity_logs`
- Module: `system`
- Action: `auto_calculate_fines`
