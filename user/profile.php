<?php
require_once '../config/config.php';
requireLogin();

// Redirect admins to admin panel
if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/profile.php');
}

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$success = '';
$passwordError = '';
$passwordSuccess = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $user_code = clean($_POST['user_code'] ?? '');

    // Validation
    if (empty($name)) {
        $error = 'Nama harus diisi';
    } elseif (empty($email)) {
        $error = 'Email harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } elseif (empty($user_code)) {
        $error = 'User Code (NIM/NIP) harus diisi';
    } else {
        try {
            // Check if email exists for other users
            $checkEmail = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $currentUser['id']]);
            if ($checkEmail) {
                $error = 'Email sudah digunakan oleh user lain';
            } else {
                // Check if user_code exists for other users
                $checkCode = $db->fetchOne("SELECT id FROM users WHERE user_code = ? AND id != ?", [$user_code, $currentUser['id']]);
                if ($checkCode) {
                    $error = 'User Code (NIM/NIP) sudah digunakan oleh user lain';
                } else {
                    // Update user profile
                    $query = "UPDATE users SET name = ?, email = ?, phone = ?, user_code = ?, updated_at = NOW() WHERE id = ?";
                    $params = [$name, $email, $phone, $user_code, $currentUser['id']];

                    if ($db->execute($query, $params)) {
                        // Update session
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_code'] = $user_code;

                        // Log activity
                        try {
                            logActivity($currentUser['id'], 'update_profile', 'user', 'User updated profile');
                        } catch (Exception $e) {
                            // Ignore
                        }

                        $success = 'Profil berhasil diperbarui!';
                        $currentUser = getCurrentUser(); // Refresh data
                    } else {
                        $error = 'Gagal memperbarui profil';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $passwordError = 'Password saat ini harus diisi';
    } elseif (empty($new_password)) {
        $passwordError = 'Password baru harus diisi';
    } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        $passwordError = 'Password baru minimal ' . PASSWORD_MIN_LENGTH . ' karakter';
    } elseif ($new_password !== $confirm_password) {
        $passwordError = 'Konfirmasi password tidak cocok';
    } else {
        try {
            // Verify current password
            $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$currentUser['id']]);
            if ($user && verifyPassword($current_password, $user['password'])) {
                // Update password
                $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $params = [hashPassword($new_password), $currentUser['id']];

                if ($db->execute($query, $params)) {
                    // Log activity
                    try {
                        logActivity($currentUser['id'], 'change_password', 'user', 'User changed password');
                    } catch (Exception $e) {
                        // Ignore
                    }

                    $passwordSuccess = 'Password berhasil diubah!';
                } else {
                    $passwordError = 'Gagal mengubah password';
                }
            } else {
                $passwordError = 'Password saat ini salah';
            }
        } catch (Exception $e) {
            $passwordError = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get user's university info
$university = null;
if ($currentUser['university_id']) {
    $university = $db->fetchOne("SELECT * FROM universities WHERE id = ?", [$currentUser['university_id']]);
}

$pageTitle = 'Profil Saya - ' . SITE_NAME;
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
    <style>
        .profile-header {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #3B82F6;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .info-item {
            padding: 1rem 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #1F2937;
            font-size: 1rem;
        }
        .badge-role {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="text-center">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($currentUser['name']); ?></h2>
                    <p class="mb-2"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    <span class="badge-role text-uppercase">
                        <i class="bi bi-shield-check me-1"></i><?php echo htmlspecialchars($currentUser['role']); ?>
                    </span>
                </div>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-4 mb-4">
                    <div class="info-card">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-person-badge me-2 text-primary"></i>Informasi Akun
                        </h5>

                        <div class="info-item">
                            <div class="info-label">User Code (NIM/NIP)</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['user_code']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Nama Lengkap</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">No. Telepon</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser['phone'] ?? '-'); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Universitas</div>
                            <div class="info-value">
                                <?php if ($university): ?>
                                    <strong><?php echo htmlspecialchars($university['code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($university['name']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Role</div>
                            <div class="info-value text-uppercase fw-bold text-primary">
                                <?php echo htmlspecialchars($currentUser['role']); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="col-lg-8">
                    <!-- Update Profile -->
                    <div class="info-card mb-4">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profil
                        </h5>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        User Code (NIM/NIP) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="user_code" value="<?php echo htmlspecialchars($currentUser['user_code']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">No. Telepon</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" placeholder="08123456789">
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>Ubah Password
                        </h5>

                        <?php if (!empty($passwordSuccess)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i><?php echo $passwordSuccess; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($passwordError)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $passwordError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Password Saat Ini <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Password Baru <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="new_password" required>
                                <small class="text-muted">Minimal <?php echo PASSWORD_MIN_LENGTH; ?> karakter</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Konfirmasi Password Baru <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="change_password" class="btn btn-warning btn-lg">
                                    <i class="bi bi-key me-2"></i>Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
