<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$formData = [
    'name' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'website' => '',
    'partnership_status' => 'pending',
    'commission_rate' => 0.00
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = clean($_POST['name'] ?? '');
    $formData['contact_person'] = clean($_POST['contact_person'] ?? '');
    $formData['email'] = clean($_POST['email'] ?? '');
    $formData['phone'] = clean($_POST['phone'] ?? '');
    $formData['address'] = clean($_POST['address'] ?? '');
    $formData['website'] = clean($_POST['website'] ?? '');
    $formData['partnership_status'] = clean($_POST['partnership_status'] ?? 'pending');
    $formData['commission_rate'] = (float)($_POST['commission_rate'] ?? 0);

    // Validation
    if (empty($formData['name'])) {
        $error = 'Nama publisher wajib diisi';
    } elseif (!empty($formData['email']) && !isValidEmail($formData['email'])) {
        $error = 'Format email tidak valid';
    } else {
        $query = "INSERT INTO publishers (name, contact_person, email, phone, address, website, partnership_status, commission_rate)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $formData['name'],
            $formData['contact_person'] ?: null,
            $formData['email'] ?: null,
            $formData['phone'] ?: null,
            $formData['address'] ?: null,
            $formData['website'] ?: null,
            $formData['partnership_status'],
            $formData['commission_rate']
        ];

        if ($db->execute($query, $params)) {
            logActivity($currentUser['id'], 'add_publisher', 'publishers', 'Added publisher: ' . $formData['name']);
            redirect(SITE_URL . '/admin/publishers/index.php', 'Publisher berhasil ditambahkan!', 'success');
        } else {
            $error = 'Gagal menambahkan publisher. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Add Publisher - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">Tambah Publisher Baru</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/publishers/index.php">Publishers</a></li>
                            <li class="breadcrumb-item active">Tambah Baru</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/publishers/index.php" class="btn btn-outline-secondary">
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
                                    <label class="form-label">Nama Publisher <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($formData['contact_person']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($formData['website']); ?>" placeholder="https://">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status Partnership</label>
                                    <select name="partnership_status" class="form-select">
                                        <option value="pending" <?php echo $formData['partnership_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="active" <?php echo $formData['partnership_status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $formData['partnership_status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Commission Rate (%)</label>
                                    <input type="number" name="commission_rate" class="form-control" value="<?php echo $formData['commission_rate']; ?>" step="0.01" min="0" max="100">
                                    <small class="text-muted">Persentase komisi untuk publisher (0-100%)</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="address" class="form-control" rows="4"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/publishers/index.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Simpan Publisher
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
