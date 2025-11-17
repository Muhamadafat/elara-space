<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

// Check if self registration is allowed
$allowRegistration = getSetting('allow_self_registration', 1);
if (!$allowRegistration) {
    redirect(SITE_URL . '/login.php', 'Self registration is currently disabled. Please contact administrator.', 'warning');
}

$error = '';
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'user_code' => '',
    'phone' => '',
    'university_id' => '',
    'role' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = clean($_POST['name'] ?? '');
    $formData['email'] = clean($_POST['email'] ?? '');
    $formData['user_code'] = clean($_POST['user_code'] ?? '');
    $formData['phone'] = clean($_POST['phone'] ?? '');
    $formData['university_id'] = clean($_POST['university_id'] ?? '');
    $formData['role'] = clean($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($formData['name'])) {
        $error = 'Nama harus diisi';
    } elseif (empty($formData['email'])) {
        $error = 'Email harus diisi';
    } elseif (!isValidEmail($formData['email'])) {
        $error = 'Format email tidak valid';
    } elseif (empty($formData['user_code'])) {
        $error = 'User Code (NIM/NIP) harus diisi';
    } elseif (empty($formData['university_id'])) {
        $error = 'Pilih universitas terlebih dahulu';
    } elseif (empty($formData['role'])) {
        $error = 'Pilih role terlebih dahulu';
    } elseif (empty($password)) {
        $error = 'Password harus diisi';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok';
    } else {
        try {
            $db = new Database();

            // Check if email already exists
            $checkEmail = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$formData['email']]);
            if ($checkEmail) {
                $error = 'Email sudah terdaftar, silakan login atau gunakan email lain';
            } else {
                // Check if user code already exists
                $checkCode = $db->fetchOne("SELECT id FROM users WHERE user_code = ?", [$formData['user_code']]);
                if ($checkCode) {
                    $error = 'User Code (NIM/NIP) sudah terdaftar';
                } else {
                    // Insert new user
                    $query = "INSERT INTO users (university_id, user_code, name, email, password, role, phone, status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";

                    $params = [
                        $formData['university_id'],
                        $formData['user_code'],
                        $formData['name'],
                        $formData['email'],
                        hashPassword($password),
                        $formData['role'],
                        $formData['phone']
                    ];

                    if ($db->execute($query, $params)) {
                        $userId = $db->lastInsertId();

                        // Log activity
                        try {
                            logActivity($userId, 'register', 'auth', 'New user registered: ' . $formData['email']);
                        } catch (Exception $e) {
                            // Ignore if logging fails
                        }

                        // Success - redirect to login
                        $_SESSION['flash_message'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                        $_SESSION['flash_type'] = 'success';
                        redirect(SITE_URL . '/login.php');
                    } else {
                        // Get detailed error
                        $errorInfo = $db->conn->errorInfo();
                        $error = 'Registrasi gagal: ' . ($errorInfo[2] ?? 'Unknown error') . ' (Code: ' . ($errorInfo[1] ?? 'N/A') . ')';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get universities - ALWAYS USE HARDCODED LIST FOR STABILITY
$universities = [
    ['id' => 1, 'code' => 'UPI', 'name' => 'Universitas Pendidikan Indonesia'],
    ['id' => 2, 'code' => 'UNPAD', 'name' => 'Universitas Padjadjaran'],
    ['id' => 3, 'code' => 'UIN', 'name' => 'UIN Sunan Gunung Djati Bandung'],
    ['id' => 4, 'code' => 'UMB', 'name' => 'Universitas Mercubuana'],
    ['id' => 5, 'code' => 'IKOPIN', 'name' => 'Institut Koperasi Indonesia']
];

// Try to get from database if available
try {
    $db = new Database();
    $dbUnis = $db->fetchAll("SELECT id, code, name FROM universities ORDER BY name ASC");
    if (!empty($dbUnis)) {
        $universities = $dbUnis; // Use database if available
    }
} catch (Exception $e) {
    // Use hardcoded list as fallback (already set above)
}

$pageTitle = 'Register - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-md-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="auth-logo mb-3">
                                <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h2 class="fw-bold">Daftar Akun Baru</h2>
                            <p class="text-muted">Bergabung dengan <?php echo SITE_NAME; ?></p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $formData['name']; ?>" placeholder="Masukkan nama lengkap" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $formData['email']; ?>" placeholder="email@example.com" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo $formData['phone']; ?>" placeholder="08xxxxxxxxxx">
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Universitas <span class="text-danger">*</span></label>
                                    <select class="form-select" name="university_id" required>
                                        <option value="">Pilih Universitas</option>
                                        <?php foreach ($universities as $uni): ?>
                                            <option value="<?php echo $uni['id']; ?>" <?php echo $formData['university_id'] == $uni['id'] ? 'selected' : ''; ?>>
                                                <?php echo $uni['code'] . ' - ' . $uni['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">User Code (NIM/NIP) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="user_code" value="<?php echo $formData['user_code']; ?>" placeholder="Contoh: 2021001" required>
                                    <small class="text-muted">Nomor Induk Mahasiswa/NIP Anda</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="mahasiswa" <?php echo $formData['role'] == 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                        <option value="dosen" <?php echo $formData['role'] == 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                        <option value="staff" <?php echo $formData['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" placeholder="Minimal <?php echo PASSWORD_MIN_LENGTH; ?> karakter" required>
                                    <small class="text-muted">Minimal <?php echo PASSWORD_MIN_LENGTH; ?> karakter</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password" required>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Saya setuju dengan Syarat dan Ketentuan yang berlaku
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-check me-2"></i>Daftar Sekarang
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold">Login di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
