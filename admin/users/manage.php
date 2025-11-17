<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();
$currentUser = getCurrentUser();

// Handle update user role/status
if (isset($_POST['update_user'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Prevent self-demotion for super_admin
    if ($userId == $currentUser['id'] && $currentUser['role'] === 'super_admin') {
        redirect(SITE_URL . '/admin/users/manage.php', 'Anda tidak bisa mengubah role/status sendiri!', 'error');
    }

    try {
        $db->query("
            UPDATE users
            SET role = ?, status = ?
            WHERE id = ?
        ", [$role, $status, $userId]);

        // Log activity
        $user = $db->fetchOne("SELECT name FROM users WHERE id = ?", [$userId]);
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'update_user_access', 'users', ?, ?)
        ", [
            $currentUser['id'],
            "Mengubah role/status user: {$user['name']}",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        redirect(SITE_URL . '/admin/users/manage.php', 'User berhasil diupdate!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/users/manage.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle delete user
if (isset($_POST['delete_user'])) {
    $userId = (int)$_POST['user_id'];

    // Prevent self-deletion
    if ($userId == $currentUser['id']) {
        redirect(SITE_URL . '/admin/users/manage.php', 'Anda tidak bisa menghapus akun sendiri!', 'error');
    }

    try {
        $user = $db->fetchOne("SELECT name FROM users WHERE id = ?", [$userId]);

        $db->query("DELETE FROM users WHERE id = ?", [$userId]);

        // Log activity
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'delete_user', 'users', ?, ?)
        ", [
            $currentUser['id'],
            "Menghapus user: {$user['name']}",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        redirect(SITE_URL . '/admin/users/manage.php', 'User berhasil dihapus!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/users/manage.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get filters
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$universityFilter = $_GET['university'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if ($roleFilter !== 'all') {
    $whereClause .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== 'all') {
    $whereClause .= " AND u.status = ?";
    $params[] = $statusFilter;
}

if ($universityFilter !== 'all') {
    $whereClause .= " AND u.university_id = ?";
    $params[] = $universityFilter;
}

if (!empty($search)) {
    $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.user_code LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Get users
try {
    $users = $db->fetchAll("
        SELECT u.*, uni.name AS university_name, uni.code AS university_code
        FROM users u
        JOIN universities uni ON u.university_id = uni.id
        $whereClause
        ORDER BY u.created_at DESC
    ", $params);
} catch (Exception $e) {
    $users = [];
    $error = $e->getMessage();
}

// Get universities for filter
try {
    $universities = $db->fetchAll("SELECT id, name, code FROM universities ORDER BY name");
} catch (Exception $e) {
    $universities = [];
}

// Get statistics
try {
    $stats = $db->fetchOne("
        SELECT
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
            SUM(CASE WHEN role = 'mahasiswa' THEN 1 ELSE 0 END) as total_mahasiswa,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users
        FROM users
    ");
} catch (Exception $e) {
    $stats = [
        'total_users' => 0,
        'total_admins' => 0,
        'total_mahasiswa' => 0,
        'active_users' => 0,
        'inactive_users' => 0,
        'suspended_users' => 0
    ];
}

$pageTitle = 'User Management - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">User Management</h1>
                    <p class="text-muted mb-0">Kelola akses dan role pengguna</p>
                </div>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Total Users</h6>
                            <h3 class="fw-bold"><?php echo $stats['total_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Mahasiswa</h6>
                            <h3 class="fw-bold"><?php echo $stats['total_mahasiswa']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Admin</h6>
                            <h3 class="fw-bold"><?php echo $stats['total_admins']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Active</h6>
                            <h3 class="fw-bold"><?php echo $stats['active_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Inactive</h6>
                            <h3 class="fw-bold"><?php echo $stats['inactive_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Suspended</h6>
                            <h3 class="fw-bold"><?php echo $stats['suspended_users']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>Semua Role</option>
                                <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="dosen" <?php echo $roleFilter === 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                <option value="mahasiswa" <?php echo $roleFilter === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                <option value="staff" <?php echo $roleFilter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Universitas</label>
                            <select name="university" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $universityFilter === 'all' ? 'selected' : ''; ?>>Semua Universitas</option>
                                <?php foreach ($universities as $uni): ?>
                                    <option value="<?php echo $uni['id']; ?>" <?php echo $universityFilter == $uni['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uni['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Nama, email, atau user code..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin/users/manage.php" class="btn btn-secondary">
                                        <i class="bi bi-x"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User Code</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Universitas</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Terdaftar</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada user ditemukan</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($user['user_code']); ?></code></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <?php if ($user['id'] == $currentUser['id']): ?>
                                                    <span class="badge bg-info ms-1">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <small><?php echo htmlspecialchars($user['university_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $roleBadges = [
                                                    'super_admin' => 'danger',
                                                    'admin' => 'dark',
                                                    'dosen' => 'primary',
                                                    'mahasiswa' => 'info',
                                                    'staff' => 'secondary'
                                                ];
                                                $badge = $roleBadges[$user['role']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>">
                                                    <?php echo strtoupper($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'active' => 'success',
                                                    'inactive' => 'secondary',
                                                    'suspended' => 'danger'
                                                ];
                                                $badge = $statusBadges[$user['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>">
                                                    <?php echo strtoupper($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo indonesianDate($user['created_at']); ?></td>
                                            <td>
                                                <?php if ($user['id'] != $currentUser['id']): ?>
                                                    <button class="btn btn-sm btn-warning" onclick='editUser(<?php echo json_encode($user, JSON_HEX_APOS); ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function editUser(user) {
            Swal.fire({
                title: 'Edit User Access',
                html: `
                    <div class="text-start">
                        <p><strong>Nama:</strong> ${user.name}</p>
                        <p><strong>Email:</strong> ${user.email}</p>
                        <hr>
                        <form id="userForm">
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="mahasiswa" ${user.role === 'mahasiswa' ? 'selected' : ''}>Mahasiswa</option>
                                    <option value="dosen" ${user.role === 'dosen' ? 'selected' : ''}>Dosen</option>
                                    <option value="staff" ${user.role === 'staff' ? 'selected' : ''}>Staff</option>
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                    <option value="super_admin" ${user.role === 'super_admin' ? 'selected' : ''}>Super Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="active" ${user.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${user.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                    <option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                                </select>
                            </div>
                        </form>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return {
                        role: document.querySelector('[name="role"]').value,
                        status: document.querySelector('[name="status"]').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="update_user" value="1">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <input type="hidden" name="role" value="${result.value.role}">
                        <input type="hidden" name="status" value="${result.value.status}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deleteUser(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                html: `Anda yakin ingin menghapus user:<br><strong>${userName}</strong>?<br><br><small class="text-danger">Data peminjaman dan history user akan ikut terhapus!</small>`,
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
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" value="${userId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
