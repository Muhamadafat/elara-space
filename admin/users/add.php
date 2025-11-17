<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$formData = [
    'name' => '',
    'email' => '',
    'user_code' => '',
    'role' => 'mahasiswa',
    'university_id' => '',
    'phone' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = clean($_POST['name'] ?? '');
    $formData['email'] = clean($_POST['email'] ?? '');
    $formData['user_code'] = clean($_POST['user_code'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['role'] = clean($_POST['role'] ?? 'mahasiswa');
    $formData['university_id'] = (int)($_POST['university_id'] ?? 0);
    $formData['phone'] = clean($_POST['phone'] ?? '');
    $formData['address'] = clean($_POST['address'] ?? '');

    // Validation
    if (empty($formData['name'])) {
        $error = 'Nama wajib diisi';
    } elseif (empty($formData['email'])) {
        $error = 'Email wajib diisi';
    } elseif (!isValidEmail($formData['email'])) {
        $error = 'Format email tidak valid';
    } elseif (empty($formData['user_code'])) {
        $error = 'Kode user wajib diisi';
    } elseif (empty($formData['password'])) {
        $error = 'Password wajib diisi';
    } elseif (strlen($formData['password']) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif (!in_array($formData['role'], ['mahasiswa', 'dosen', 'staff'])) {
        $error = 'Role tidak valid';
    } else {
        // Check if email already exists
        $checkEmail = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$formData['email']]);
        if ($checkEmail) {
            $error = 'Email sudah terdaftar';
        } else {
            // Check if user_code already exists
            $checkCode = $db->fetchOne("SELECT id FROM users WHERE user_code = ?", [$formData['user_code']]);
            if ($checkCode) {
                $error = 'Kode user sudah digunakan';
            } else {
                $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);

                $query = "INSERT INTO users (name, email, user_code, password, role, university_id, phone, address, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $params = [
                    $formData['name'],
                    $formData['email'],
                    $formData['user_code'],
                    $hashedPassword,
                    $formData['role'],
                    $formData['university_id'] ?: null,
                    $formData['phone'] ?: null,
                    $formData['address'] ?: null
                ];

                if ($db->execute($query, $params)) {
                    logActivity($currentUser['id'], 'add_user', 'users', 'Added new user: ' . $formData['name']);
                    redirect(SITE_URL . '/admin/users/index.php', 'User berhasil ditambahkan!', 'success');
                } else {
                    $error = 'Gagal menambahkan user. Silakan coba lagi.';
                }
            }
        }
    }
}

// Get universities for dropdown
$universities = $db->fetchAll("SELECT id, name, code FROM universities ORDER BY name");

$pageTitle = 'Tambah Pengguna Baru - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">Tambah Pengguna Baru</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/users/index.php">Pengguna</a></li>
                            <li class="breadcrumb-item active">Tambah</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/users/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: '<?php echo addslashes($error); ?>',
                            confirmButtonColor: '#3B82F6'
                        });
                    });
                </script>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Kode User <span class="text-danger">*</span></label>
                                    <input type="text" name="user_code" class="form-control" value="<?php echo htmlspecialchars($formData['user_code']); ?>" required>
                                    <small class="text-muted">Contoh: USR001, MHS001, DSN001</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="mahasiswa" <?php echo $formData['role'] == 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                        <option value="dosen" <?php echo $formData['role'] == 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                        <option value="staff" <?php echo $formData['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Universitas</label>
                                    <select name="university_id" class="form-select">
                                        <option value="">Pilih Universitas</option>
                                        <?php foreach ($universities as $uni): ?>
                                            <option value="<?php echo $uni['id']; ?>" <?php echo $formData['university_id'] == $uni['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($uni['name']); ?> (<?php echo htmlspecialchars($uni['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/users/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Simpan User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</invoke>