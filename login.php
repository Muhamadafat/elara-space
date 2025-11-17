<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin' || $role === 'super_admin') {
        redirect(SITE_URL . '/admin/index.php');
    } else {
        redirect(SITE_URL . '/user/index.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } else {
        try {
            $db = new Database();

            $query = "SELECT u.*, uni.name as university_name, uni.code as university_code
                      FROM users u
                      LEFT JOIN universities uni ON u.university_id = uni.id
                      WHERE u.email = ? AND u.status = 'active'";

            $user = $db->fetchOne($query, [$email]);

            if ($user && verifyPassword($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_code'] = $user['user_code'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['university_id'] = $user['university_id'];
                $_SESSION['university_name'] = $user['university_name'];
                $_SESSION['last_activity'] = time();

                // Log activity
                try {
                    logActivity($user['id'], 'login', 'auth', 'User logged in successfully');
                } catch (Exception $e) {
                    // Ignore if logging fails
                }

                // Redirect to landing page
                redirect(SITE_URL . '/index.php', 'Selamat datang kembali, ' . $user['name'] . '!', 'success');
            } else {
                $error = 'Email atau password salah';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Login - ' . SITE_NAME;
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
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="auth-logo mb-3">
                                <i class="bi bi-book-half text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h2 class="fw-bold"><?php echo SITE_NAME; ?></h2>
                            <p class="text-muted">Library Management System</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                                <i class="bi bi-<?php echo ($_SESSION['flash_type'] ?? 'info') === 'success' ? 'check-circle' : 'info-circle'; ?> me-2"></i>
                                <?php echo $_SESSION['flash_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php
                            unset($_SESSION['flash_message']);
                            unset($_SESSION['flash_type']);
                            ?>
                        <?php endif; ?>

                        <?php if (isset($_GET['timeout'])): ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="bi bi-clock me-2"></i>Sesi Anda telah berakhir. Silakan login kembali.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" value="<?php echo $email; ?>" placeholder="email@example.com" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? <a href="register.php" class="text-decoration-none fw-bold">Daftar di sini</a></p>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                For Faculty of Economics<br>
                                UPI | UNPAD | UIN | UMB | IKOPIN
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
