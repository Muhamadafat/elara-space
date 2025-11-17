<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => clean($_POST['site_name'] ?? ''),
        'site_description' => clean($_POST['site_description'] ?? ''),
        'borrow_duration_days' => (int)($_POST['borrow_duration_days'] ?? 14),
        'max_borrow_books' => (int)($_POST['max_borrow_books'] ?? 3),
        'max_extend_times' => (int)($_POST['max_extend_times'] ?? 2),
        'fine_per_day' => (int)($_POST['fine_per_day'] ?? 2000),
        'allow_self_registration' => isset($_POST['allow_self_registration']) ? 1 : 0,
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
    ];

    foreach ($settings as $key => $value) {
        // Check if setting exists
        $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);

        if ($existing) {
            $db->execute("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            $db->execute("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
    }

    logActivity($currentUser['id'], 'update_settings', 'system', 'Updated system settings');
    setFlashMessage('success', 'Pengaturan berhasil disimpan!');
    redirect(SITE_URL . '/admin/settings/index.php');
}

// Get current settings
$currentSettings = [
    'site_name' => getSetting('site_name', 'Elara Space'),
    'site_description' => getSetting('site_description', 'Library Management System'),
    'borrow_duration_days' => getSetting('borrow_duration_days', 14),
    'max_borrow_books' => getSetting('max_borrow_books', 3),
    'max_extend_times' => getSetting('max_extend_times', 2),
    'fine_per_day' => getSetting('fine_per_day', 2000),
    'allow_self_registration' => getSetting('allow_self_registration', 1),
    'maintenance_mode' => getSetting('maintenance_mode', 0)
];

$pageTitle = 'System Settings - ' . SITE_NAME;
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
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">System Settings</h1>
                    <p class="text-muted mb-0">Kelola pengaturan sistem perpustakaan</p>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <form method="POST">
                <div class="row">
                    <!-- General Settings -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Pengaturan Umum</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Website</label>
                                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($currentSettings['site_name']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($currentSettings['site_description']); ?></textarea>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="allow_self_registration" id="selfReg" <?php echo $currentSettings['allow_self_registration'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="selfReg">
                                        Izinkan Pendaftaran Mandiri
                                    </label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance">
                                        Mode Maintenance
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Borrowing Settings -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-book me-2"></i>Pengaturan Peminjaman</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Durasi Peminjaman (hari)</label>
                                    <input type="number" name="borrow_duration_days" class="form-control" value="<?php echo $currentSettings['borrow_duration_days']; ?>" min="1">
                                    <small class="text-muted">Default: 14 hari</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Maksimal Buku Dipinjam</label>
                                    <input type="number" name="max_borrow_books" class="form-control" value="<?php echo $currentSettings['max_borrow_books']; ?>" min="1">
                                    <small class="text-muted">Default: 3 buku per user</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Maksimal Perpanjangan</label>
                                    <input type="number" name="max_extend_times" class="form-control" value="<?php echo $currentSettings['max_extend_times']; ?>" min="0">
                                    <small class="text-muted">Default: 2 kali</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Denda Per Hari (Rp)</label>
                                    <input type="number" name="fine_per_day" class="form-control" value="<?php echo $currentSettings['fine_per_day']; ?>" min="0">
                                    <small class="text-muted">Default: Rp 2.000/hari</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Sistem</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="text-muted small">PHP Version</label>
                                    <div class="fw-bold"><?php echo phpversion(); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="text-muted small">Database</label>
                                    <div class="fw-bold">MySQL</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="text-muted small">Server</label>
                                    <div class="fw-bold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="text-muted small">Upload Max Size</label>
                                    <div class="fw-bold"><?php echo ini_get('upload_max_filesize'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
