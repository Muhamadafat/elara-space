<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$universityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$universityId) {
    redirect(SITE_URL . '/admin/universities/index.php', 'Invalid university ID', 'error');
}

$university = $db->fetchOne("SELECT * FROM universities WHERE id = ?", [$universityId]);

if (!$university) {
    redirect(SITE_URL . '/admin/universities/index.php', 'University not found', 'error');
}

$error = '';
$formData = $university;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['code'] = strtoupper(clean($_POST['code'] ?? ''));
    $formData['name'] = clean($_POST['name'] ?? '');
    $formData['address'] = clean($_POST['address'] ?? '');
    $formData['phone'] = clean($_POST['phone'] ?? '');
    $formData['email'] = clean($_POST['email'] ?? '');

    if (empty($formData['code'])) {
        $error = 'Kode university wajib diisi';
    } elseif (empty($formData['name'])) {
        $error = 'Nama university wajib diisi';
    } elseif (!empty($formData['email']) && !isValidEmail($formData['email'])) {
        $error = 'Format email tidak valid';
    } else {
        // Check if code already exists (except current)
        $checkCode = $db->fetchOne("SELECT id FROM universities WHERE code = ? AND id != ?", [$formData['code'], $universityId]);
        if ($checkCode) {
            $error = 'Kode university sudah digunakan';
        } else {
            $query = "UPDATE universities SET
                      code = ?, name = ?, address = ?, phone = ?, email = ?
                      WHERE id = ?";

            $params = [
                $formData['code'],
                $formData['name'],
                $formData['address'] ?: null,
                $formData['phone'] ?: null,
                $formData['email'] ?: null,
                $universityId
            ];

            if ($db->execute($query, $params)) {
                logActivity($currentUser['id'], 'edit_university', 'universities', 'Updated university: ' . $formData['name']);
                redirect(SITE_URL . '/admin/universities/index.php', 'University berhasil diupdate!', 'success');
            } else {
                $error = 'Gagal mengupdate university. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = 'Edit University - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">Edit University</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/universities/index.php">Universities</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/universities/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode University <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($formData['code']); ?>" required maxlength="10" style="text-transform: uppercase;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama University <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="address" class="form-control" rows="5"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/universities/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update University
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
