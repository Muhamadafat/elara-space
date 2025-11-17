<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle delete
if (isset($_POST['delete_user'])) {
    $userId = (int)$_POST['user_id'];

    // Check if user has active borrowings
    $activeBorrowings = $db->fetchOne(
        "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status IN ('borrowed', 'overdue')",
        [$userId]
    );

    if ($activeBorrowings['count'] > 0) {
        redirect(SITE_URL . '/admin/users/index.php', 'Tidak dapat menghapus user yang masih memiliki peminjaman aktif', 'error');
    }

    // Get user info for logging
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user) {
        redirect(SITE_URL . '/admin/users/index.php', 'User tidak ditemukan', 'error');
    }

    if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
        redirect(SITE_URL . '/admin/users/index.php', 'Tidak dapat menghapus user admin', 'error');
    }

    // Check unpaid fines
    $unpaidFines = $db->fetchOne(
        "SELECT COUNT(*) as count FROM fines WHERE user_id = ? AND status = 'unpaid'",
        [$userId]
    );

    if ($unpaidFines['count'] > 0) {
        redirect(SITE_URL . '/admin/users/index.php', 'Tidak dapat menghapus user yang masih memiliki denda belum lunas', 'error');
    }

    try {
        // Delete user
        if ($db->execute("DELETE FROM users WHERE id = ?", [$userId])) {
            logActivity($currentUser['id'], 'delete_user', 'users', 'Deleted user: ' . $user['name'] . ' (' . $user['user_code'] . ')');
            redirect(SITE_URL . '/admin/users/index.php', 'User berhasil dihapus', 'success');
        } else {
            redirect(SITE_URL . '/admin/users/index.php', 'Gagal menghapus user', 'error');
        }
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/users/index.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle suspend/activate
if (isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

    if ($user) {
        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
        $action = $newStatus === 'suspended' ? 'menangguhkan' : 'mengaktifkan';

        if ($db->execute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId])) {
            logActivity($currentUser['id'], 'toggle_user_status', 'users', ucfirst($action) . ' user: ' . $user['name']);
            redirect(SITE_URL . '/admin/users/index.php', 'Status user berhasil diubah menjadi ' . $newStatus, 'success');
        } else {
            redirect(SITE_URL . '/admin/users/index.php', 'Gagal mengubah status user', 'error');
        }
    }
}

// Filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$university = $_GET['university'] ?? '';

// Build query
$where = ["role IN ('mahasiswa', 'dosen', 'staff')"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR user_code LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

if (!empty($university)) {
    $where[] = "university_id = ?";
    $params[] = $university;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users $whereClause", $params)['count'];
$pagination = paginate($totalUsers, $page);

// Get users with statistics
$query = "SELECT u.*, uni.name as university_name, uni.code as university_code,
          (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id) as total_borrowings,
          (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status IN ('borrowed', 'overdue')) as active_borrowings
          FROM users u
          LEFT JOIN universities uni ON u.university_id = uni.id
          $whereClause
          ORDER BY u.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$users = $db->fetchAll($query, $params);

// Get universities for filter
$universities = $db->fetchAll("SELECT id, name, code FROM universities ORDER BY name");

$pageTitle = 'Manajemen Pengguna - ' . SITE_NAME;
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
    <style>
        .user-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: #3B82F6;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        .role-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Manajemen Pengguna</h1>
                    <p class="text-muted mb-0">Kelola pengguna perpustakaan</p>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah User Baru
                    </a>
                </div>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Cari nama, email, kode user..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="role">
                                <option value="">Semua Role</option>
                                <option value="mahasiswa" <?php echo $role == 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                <option value="dosen" <?php echo $role == 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                <option value="staff" <?php echo $role == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="university">
                                <option value="">Semua Universitas</option>
                                <?php foreach ($universities as $uni): ?>
                                    <option value="<?php echo $uni['id']; ?>" <?php echo $university == $uni['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uni['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Grid -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-people-fill me-2 text-primary"></i>
                    Daftar User <span class="badge bg-primary"><?php echo formatNumber($totalUsers); ?></span>
                </h5>
            </div>

            <?php if (!empty($users)): ?>
                <div class="row g-4 mb-4">
                    <?php foreach ($users as $user): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="user-card">
                                <!-- User Avatar -->
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>

                                <!-- User Info -->
                                <div class="text-center mb-3">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                    </p>
                                    <code class="small"><?php echo htmlspecialchars($user['user_code']); ?></code>
                                </div>

                                <!-- Role Badge -->
                                <div class="text-center mb-3">
                                    <?php
                                    $roleColors = [
                                        'mahasiswa' => 'primary',
                                        'dosen' => 'success',
                                        'staff' => 'info'
                                    ];
                                    $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                    ?>
                                    <span class="role-badge bg-<?php echo $roleColor; ?> text-white">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>

                                <!-- University -->
                                <?php if ($user['university_name']): ?>
                                    <div class="text-center mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-building me-1"></i>
                                            <?php echo htmlspecialchars($user['university_code']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <!-- Statistics -->
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <div class="fw-bold text-primary"><?php echo $user['total_borrowings']; ?></div>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-warning"><?php echo $user['active_borrowings']; ?></div>
                                        <small class="text-muted">Aktif</small>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="text-center mb-2">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                                    <?php elseif ($user['status'] === 'suspended'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Ditangguhkan</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($user['status']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="d-grid gap-2">
                                    <div class="btn-group">
                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit User">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'secondary' : 'success'; ?>"
                                                onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>', '<?php echo $user['status']; ?>')"
                                                title="<?php echo $user['status'] === 'active' ? 'Tangguhkan' : 'Aktifkan'; ?>">
                                            <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>')"
                                                title="Hapus User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php echo renderPagination($pagination, 'index.php'); ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Belum ada user</h4>
                    <p class="text-muted">Mulai dengan menambahkan user pertama</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah User Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                html: `Apakah Anda yakin ingin menghapus user <strong>${userName}</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '<i class="bi bi-trash me-2"></i>Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" value="${userId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function toggleStatus(userId, userName, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
            const action = newStatus === 'suspended' ? 'Tangguhkan' : 'Aktifkan';
            const icon = newStatus === 'suspended' ? 'warning' : 'question';
            const color = newStatus === 'suspended' ? '#F59E0B' : '#10B981';

            Swal.fire({
                title: `${action} User?`,
                html: `Apakah Anda yakin ingin ${action.toLowerCase()} user <strong>${userName}</strong>?`,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: '#6B7280',
                confirmButtonText: `Ya, ${action}`,
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="toggle_status" value="1">
                        <input type="hidden" name="user_id" value="${userId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

