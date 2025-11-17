<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $universityId = (int)$_GET['id'];

    // Check if university has users
    $hasUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE university_id = ?", [$universityId]);

    if ($hasUsers['count'] > 0) {
        setFlashMessage('error', 'Cannot delete university with existing users. Please reassign users first.');
    } else {
        $university = $db->fetchOne("SELECT name FROM universities WHERE id = ?", [$universityId]);

        if ($db->execute("DELETE FROM universities WHERE id = ?", [$universityId])) {
            logActivity($currentUser['id'], 'delete_university', 'universities', 'Deleted university: ' . $university['name']);
            setFlashMessage('success', 'University deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete university');
        }
    }

    redirect(SITE_URL . '/admin/universities/index.php');
}

// Get universities with user count
$universities = $db->fetchAll("
    SELECT u.*,
           (SELECT COUNT(*) FROM users WHERE university_id = u.id) as user_count
    FROM universities u
    ORDER BY u.name ASC
");

$pageTitle = 'Universities Management - ' . SITE_NAME;
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
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Universities Management</h1>
                    <p class="text-muted mb-0">Kelola universitas yang terdaftar</p>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/universities/add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah University
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Universities List -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($universities)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Universitas</th>
                                        <th>Kontak</th>
                                        <th>Lokasi</th>
                                        <th>Jumlah User</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($universities as $uni): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($uni['code']); ?></span></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($uni['name']); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($uni['email']): ?>
                                                    <div><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($uni['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($uni['phone']): ?>
                                                    <div><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($uni['phone']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($uni['address'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $uni['user_count']; ?> user</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo SITE_URL; ?>/admin/universities/edit.php?id=<?php echo $uni['id']; ?>"
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $uni['id']; ?>"
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Yakin ingin menghapus university ini?')"
                                                       title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada university terdaftar</p>
                            <a href="<?php echo SITE_URL; ?>/admin/universities/add.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah University Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
