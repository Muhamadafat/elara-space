<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    redirect(SITE_URL . '/admin/users/manage.php', 'Invalid user ID', 'error');
}

// Get user details
$user = $db->fetchOne("
    SELECT u.*, uni.name as university_name, uni.code as university_code
    FROM users u
    JOIN universities uni ON u.university_id = uni.id
    WHERE u.id = ?
", [$userId]);

if (!$user) {
    redirect(SITE_URL . '/admin/users/manage.php', 'User not found', 'error');
}

// Get borrowing statistics
$borrowingStats = $db->fetchOne("
    SELECT
        COUNT(*) as total_borrowings,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as active_borrowings,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_borrowings,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_borrowings
    FROM borrowings
    WHERE user_id = ?
", [$userId]);

// Get fine statistics
$fineStats = $db->fetchOne("
    SELECT
        COUNT(*) as total_fines,
        SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as unpaid_amount,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
    FROM fines
    WHERE user_id = ?
", [$userId]);

// Get recent borrowings
$recentBorrowings = $db->fetchAll("
    SELECT b.*, bk.title, bk.author
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 10
", [$userId]);

// Get recent book requests
$recentRequests = $db->fetchAll("
    SELECT * FROM book_requests
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
", [$userId]);

$pageTitle = 'User Details - ' . SITE_NAME;
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
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        .info-value {
            color: #1f2937;
            text-align: right;
        }
        .stats-card-small {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stats-card-small h4 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        .stats-card-small p {
            color: #6b7280;
            margin: 0;
            font-size: 0.85rem;
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
                    <h1 class="h2 fw-bold mb-1">Detail Pengguna</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/users/manage.php">Users</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($user['name']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/users/manage.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <!-- User Info -->
                <div class="col-lg-4">
                    <div class="info-card text-center">
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?php echo SITE_URL . '/uploads/profiles/' . $user['profile_photo']; ?>"
                                 class="rounded-circle mb-3" width="120" height="120" alt="Profile">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                 style="width: 120px; height: 120px; font-size: 3rem;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['user_code']); ?></p>
                        <p class="mb-3">
                            <span class="badge bg-<?php echo $user['role'] == 'admin' || $user['role'] == 'super_admin' ? 'danger' : 'primary'; ?>">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                            <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo strtoupper($user['status']); ?>
                            </span>
                        </p>

                        <div class="d-grid gap-2">
                            <a href="mailto:<?php echo $user['email']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-envelope me-2"></i>Kirim Email
                            </a>
                            <?php if ($user['phone']): ?>
                            <a href="tel:<?php echo $user['phone']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-telephone me-2"></i>Hubungi
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi</h5>

                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo $user['email']; ?>"><?php echo htmlspecialchars($user['email']); ?></a>
                            </div>
                        </div>

                        <?php if ($user['phone']): ?>
                        <div class="info-row">
                            <div class="info-label">Telepon</div>
                            <div class="info-value">
                                <a href="tel:<?php echo $user['phone']; ?>"><?php echo htmlspecialchars($user['phone']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Universitas</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($user['university_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($user['university_code']); ?></small>
                            </div>
                        </div>

                        <?php if ($user['address']): ?>
                        <div class="info-row">
                            <div class="info-label">Alamat</div>
                            <div class="info-value" style="text-align: left; max-width: 60%;">
                                <?php echo nl2br(htmlspecialchars($user['address'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Terdaftar</div>
                            <div class="info-value"><?php echo indonesianDate($user['created_at']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Update Terakhir</div>
                            <div class="info-value"><?php echo indonesianDate($user['updated_at']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Stats & Activity -->
                <div class="col-lg-8">
                    <!-- Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="stats-card-small">
                                <h4 class="text-primary"><?php echo formatNumber($borrowingStats['total_borrowings'] ?? 0); ?></h4>
                                <p>Total Peminjaman</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card-small">
                                <h4 class="text-info"><?php echo formatNumber($borrowingStats['active_borrowings'] ?? 0); ?></h4>
                                <p>Sedang Dipinjam</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card-small">
                                <h4 class="text-danger"><?php echo formatNumber($borrowingStats['overdue_borrowings'] ?? 0); ?></h4>
                                <p>Terlambat</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card-small">
                                <h4 class="text-warning"><?php echo formatCurrency($fineStats['unpaid_amount'] ?? 0); ?></h4>
                                <p>Denda Belum Dibayar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Borrowings -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Peminjaman</h5>

                        <?php if (!empty($recentBorrowings)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Buku</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBorrowings as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($b['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($b['author']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($b['borrow_date'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($b['due_date'])); ?></td>
                                        <td><?php echo getStatusBadge($b['status']); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/borrowing/view.php?id=<?php echo $b['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Belum ada riwayat peminjaman</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Requests -->
                    <?php if (!empty($recentRequests)): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-cart-plus me-2 text-primary"></i>Permintaan Buku</h5>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Buku</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $req): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($req['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($req['author']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($req['created_at'])); ?></td>
                                        <td><?php echo getStatusBadge($req['status']); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/requests/view.php?id=<?php echo $req['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
