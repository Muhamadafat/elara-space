<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();
$currentUser = getCurrentUser();

// Handle backup creation
if (isset($_POST['create_backup'])) {
    try {
        // Create backup directory if not exists
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "elara_space_backup_{$timestamp}.sql";
        $filepath = $backupDir . '/' . $filename;

        // Get database credentials from config
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;

        // Create backup using mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($dbname),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath)) {
            // Log activity
            $db->query("
                INSERT INTO activity_logs (user_id, action, module, description, ip_address)
                VALUES (?, 'create_backup', 'system', ?, ?)
            ", [
                $currentUser['id'],
                "Membuat backup database: {$filename}",
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            redirect(SITE_URL . '/admin/system/backup.php', 'Backup berhasil dibuat!', 'success');
        } else {
            throw new Exception("Backup gagal dibuat. Error code: $returnCode");
        }
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/system/backup.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle download backup
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/../../backups/' . $filename;

    if (file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);

        // Log activity
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'download_backup', 'system', ?, ?)
        ", [
            $currentUser['id'],
            "Download backup: {$filename}",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        exit;
    } else {
        redirect(SITE_URL . '/admin/system/backup.php', 'File backup tidak ditemukan!', 'error');
    }
}

// Handle delete backup
if (isset($_POST['delete_backup'])) {
    $filename = basename($_POST['filename']);
    $filepath = __DIR__ . '/../../backups/' . $filename;

    try {
        if (file_exists($filepath)) {
            unlink($filepath);

            // Log activity
            $db->query("
                INSERT INTO activity_logs (user_id, action, module, description, ip_address)
                VALUES (?, 'delete_backup', 'system', ?, ?)
            ", [
                $currentUser['id'],
                "Menghapus backup: {$filename}",
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            redirect(SITE_URL . '/admin/system/backup.php', 'Backup berhasil dihapus!', 'success');
        } else {
            throw new Exception("File tidak ditemukan!");
        }
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/system/backup.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get list of backups
$backupDir = __DIR__ . '/../../backups';
$backups = [];

if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'created_at' => filemtime($filepath)
            ];
        }
    }
}

// Get database stats
try {
    $dbStats = $db->fetchOne("
        SELECT
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM books) as total_books,
            (SELECT COUNT(*) FROM borrowings) as total_borrowings,
            (SELECT COUNT(*) FROM book_requests) as total_requests,
            (SELECT COUNT(*) FROM marketplace_orders) as total_orders,
            (SELECT COUNT(*) FROM notifications) as total_notifications,
            (SELECT COUNT(*) FROM activity_logs) as total_logs
    ");
} catch (Exception $e) {
    $dbStats = null;
}

$pageTitle = 'Database Backup - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Database Backup</h1>
                    <p class="text-muted mb-0">Backup dan restore database sistem</p>
                </div>
                <button class="btn btn-primary" onclick="confirmBackup()">
                    <i class="bi bi-database me-2"></i>Buat Backup Baru
                </button>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Database Statistics -->
            <?php if ($dbStats): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2"></i>Statistik Database</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-people text-primary me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <small class="text-muted">Users</small>
                                            <h5 class="mb-0"><?php echo number_format($dbStats['total_users']); ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-book text-success me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <small class="text-muted">Books</small>
                                            <h5 class="mb-0"><?php echo number_format($dbStats['total_books']); ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-arrow-left-right text-info me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <small class="text-muted">Borrowings</small>
                                            <h5 class="mb-0"><?php echo number_format($dbStats['total_borrowings']); ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-cart text-warning me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <small class="text-muted">Orders</small>
                                            <h5 class="mb-0"><?php echo number_format($dbStats['total_orders']); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Important Notice -->
            <div class="alert alert-info mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Penting!</h6>
                <ul class="mb-0 small">
                    <li>Backup database secara berkala untuk menghindari kehilangan data</li>
                    <li>Simpan file backup di lokasi yang aman dan terpisah dari server</li>
                    <li>Verifikasi backup dapat di-restore sebelum menghapus backup lama</li>
                    <li>Backup mencakup semua data: users, books, borrowings, orders, dll</li>
                </ul>
            </div>

            <!-- Backups List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-archive me-2"></i>Daftar Backup
                        <span class="badge bg-primary ms-2"><?php echo count($backups); ?> Files</span>
                    </h5>

                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Belum ada backup. Buat backup pertama Anda!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-file-earmark-zip text-primary me-2"></i>
                                                <code><?php echo htmlspecialchars($backup['filename']); ?></code>
                                            </td>
                                            <td><?php echo formatFileSize($backup['size']); ?></td>
                                            <td><?php echo date('d M Y H:i:s', $backup['created_at']); ?></td>
                                            <td>
                                                <a href="?download=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBackup('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function confirmBackup() {
            Swal.fire({
                title: 'Buat Backup Database?',
                html: `
                    <p>Proses backup akan membuat file SQL lengkap dari database.</p>
                    <small class="text-muted">Proses ini mungkin memakan waktu beberapa saat...</small>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Buat Backup',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="create_backup" value="1">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deleteBackup(filename) {
            Swal.fire({
                title: 'Hapus Backup?',
                html: `Anda yakin ingin menghapus backup:<br><code>${filename}</code>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="delete_backup" value="1">
                        <input type="hidden" name="filename" value="${filename}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>

<?php
// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
