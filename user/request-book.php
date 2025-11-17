<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$formData = [
    'isbn' => '',
    'title' => '',
    'author' => '',
    'publisher_name' => '',
    'category' => '',
    'reason' => '',
    'estimated_price' => '',
    'priority' => 'medium'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['isbn'] = clean($_POST['isbn'] ?? '');
    $formData['title'] = clean($_POST['title'] ?? '');
    $formData['author'] = clean($_POST['author'] ?? '');
    $formData['publisher_name'] = clean($_POST['publisher_name'] ?? '');
    $formData['category'] = clean($_POST['category'] ?? '');
    $formData['reason'] = clean($_POST['reason'] ?? '');
    $formData['estimated_price'] = clean($_POST['estimated_price'] ?? '');
    $formData['priority'] = clean($_POST['priority'] ?? 'medium');

    // Validation
    if (empty($formData['title']) || empty($formData['author'])) {
        $error = 'Judul dan Penulis harus diisi';
    } elseif (empty($formData['reason'])) {
        $error = 'Silakan berikan alasan untuk permintaan ini';
    } else {
        // Check if book already exists in library
        $existingBook = null;
        if (!empty($formData['isbn'])) {
            try {
                $existingBook = $db->fetchOne("SELECT id, title FROM books WHERE isbn = ?", [$formData['isbn']]);
            } catch (Exception $e) {
                // Ignore
            }
        }

        if ($existingBook) {
            $error = 'Buku ini (ISBN: ' . $formData['isbn'] . ') sudah tersedia di perpustakaan dengan judul "' . $existingBook['title'] . '". Anda dapat meminjamnya langsung.';
        } else {
            // Try to insert request
            try {
                $query = "INSERT INTO book_requests (user_id, university_id, isbn, title, author, publisher_name,
                          category, reason, estimated_price, request_type, priority, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new_book', ?, 'pending')";

                $params = [
                    $currentUser['id'],
                    $currentUser['university_id'],
                    $formData['isbn'] ?: null,
                    $formData['title'],
                    $formData['author'],
                    $formData['publisher_name'] ?: null,
                    $formData['category'] ?: null,
                    $formData['reason'],
                    $formData['estimated_price'] ?: null,
                    $formData['priority']
                ];

                if ($db->execute($query, $params)) {
                    $requestId = $db->lastInsertId();

                    // Create notification for admins
                    try {
                        $admins = $db->fetchAll("SELECT id FROM users WHERE role IN ('admin', 'super_admin')");
                        foreach ($admins as $admin) {
                            createNotification(
                                $admin['id'],
                                'Request Buku Baru',
                                $currentUser['name'] . ' merequest: "' . $formData['title'] . '"',
                                'info',
                                SITE_URL . '/admin/requests/view.php?id=' . $requestId
                            );
                        }
                    } catch (Exception $e) {
                        // Ignore notification error
                    }

                    try {
                        logActivity($currentUser['id'], 'request_book', 'requests', 'Requested book: ' . $formData['title']);
                    } catch (Exception $e) {
                        // Ignore
                    }

                    redirect(SITE_URL . '/user/requests.php', 'Permintaan buku Anda berhasil dikirim!', 'success');
                } else {
                    $error = 'Gagal mengirim permintaan. Silakan coba lagi.';
                }
            } catch (Exception $e) {
                $error = 'Fitur ini belum tersedia. Table book_requests belum dibuat di database.';
            }
        }
    }
}

// Get publishers for reference
try {
    $publishers = $db->fetchAll("SELECT name FROM publishers WHERE partnership_status = 'active' ORDER BY name");
} catch (Exception $e) {
    $publishers = [];
}

// Get categories for reference
try {
    $categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");
} catch (Exception $e) {
    $categories = [
        ['category' => 'Manajemen'],
        ['category' => 'Akuntansi'],
        ['category' => 'Ekonomi'],
        ['category' => 'Bisnis'],
        ['category' => 'Keuangan']
    ];
}

$pageTitle = 'Request Buku Baru - ' . SITE_NAME;
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Request Buku Baru</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Request Buku</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-cart-plus me-2"></i>Form Request Buku</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tidak dapat menemukan buku di perpustakaan kami?</strong> Request di sini! Kami bermitra dengan penerbit besar seperti Gramedia, Erlangga, dan lainnya untuk memenuhi permintaan Anda.
                            </div>

                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" value="<?php echo $formData['title']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Penulis <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="author" value="<?php echo $formData['author']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ISBN (jika tahu)</label>
                                        <input type="text" class="form-control" name="isbn" value="<?php echo $formData['isbn']; ?>" placeholder="978-0-123456-78-9">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Penerbit</label>
                                        <input type="text" class="form-control" name="publisher_name" value="<?php echo $formData['publisher_name']; ?>"
                                               list="publisherList" placeholder="contoh: Gramedia, Erlangga">
                                        <datalist id="publisherList">
                                            <?php foreach ($publishers as $pub): ?>
                                                <option value="<?php echo $pub['name']; ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kategori</label>
                                        <input type="text" class="form-control" name="category" value="<?php echo $formData['category']; ?>"
                                               list="categoryList" placeholder="contoh: Ekonomi, Manajemen">
                                        <datalist id="categoryList">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category']; ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estimasi Harga (Opsional)</label>
                                        <input type="number" class="form-control" name="estimated_price" value="<?php echo $formData['estimated_price']; ?>"
                                               min="0" step="1000" placeholder="dalam Rupiah">
                                        <small class="text-muted">Jika Anda tahu perkiraan harganya</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prioritas</label>
                                        <select class="form-select" name="priority">
                                            <option value="low" <?php echo $formData['priority'] == 'low' ? 'selected' : ''; ?>>Rendah - Bagus untuk dimiliki</option>
                                            <option value="medium" <?php echo $formData['priority'] == 'medium' ? 'selected' : ''; ?>>Sedang - Akan berguna</option>
                                            <option value="high" <?php echo $formData['priority'] == 'high' ? 'selected' : ''; ?>>Tinggi - Perlu untuk belajar</option>
                                            <option value="urgent" <?php echo $formData['priority'] == 'urgent' ? 'selected' : ''; ?>>Mendesak - Butuh segera</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Alasan Request <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="reason" rows="4" required placeholder="Mengapa Anda membutuhkan buku ini? Bagaimana ini akan membantu studi atau penelitian Anda?"><?php echo $formData['reason']; ?></textarea>
                                        <small class="text-muted">Mohon jelaskan mengapa Anda membutuhkan buku ini. Ini membantu kami memprioritaskan permintaan.</small>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo SITE_URL; ?>/user/requests.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Kirim Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle me-2"></i>Cara Kerja</h6>
                        </div>
                        <div class="card-body">
                            <ol class="ps-3">
                                <li class="mb-3">
                                    <strong>Kirim Request</strong>
                                    <p class="text-muted mb-0">Isi formulir dengan detail buku dan mengapa Anda membutuhkannya.</p>
                                </li>
                                <li class="mb-3">
                                    <strong>Review Admin</strong>
                                    <p class="text-muted mb-0">Tim kami akan meninjau permintaan Anda dan mengecek ketersediaan dengan penerbit.</p>
                                </li>
                                <li class="mb-3">
                                    <strong>Persetujuan & Pemesanan</strong>
                                    <p class="text-muted mb-0">Jika disetujui, kami akan memesan buku dari penerbit mitra kami.</p>
                                </li>
                                <li class="mb-3">
                                    <strong>Notifikasi</strong>
                                    <p class="text-muted mb-0">Anda akan diberi tahu ketika buku tiba dan siap dipinjam!</p>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-building me-2"></i>Penerbit Mitra</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Gramedia Pustaka Utama</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Erlangga</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Salemba Empat</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Andi Publisher</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Rajagrafindo Persada</li>
                                <li><i class="bi bi-plus-circle text-primary me-2"></i>Dan lainnya...</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-lightbulb me-2"></i>Tips</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li class="mb-2">Jelaskan secara spesifik mengapa Anda membutuhkan buku tersebut</li>
                                <li class="mb-2">Berikan ISBN jika Anda tahu untuk proses lebih cepat</li>
                                <li class="mb-2">Cek terlebih dahulu apakah buku sudah ada di perpustakaan kami</li>
                                <li>Tetapkan prioritas berdasarkan kebutuhan aktual Anda</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
