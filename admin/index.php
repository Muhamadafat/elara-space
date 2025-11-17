<?php
require_once '../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stats = [
    'total_books' => $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'],
    'available_books' => $db->fetchOne("SELECT SUM(available_quantity) as count FROM books WHERE status = 'available'")['count'] ?? 0,
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role != 'admin' AND role != 'super_admin'")['count'],
    'active_borrowings' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'")['count'],
    'overdue_books' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'")['count'],
    'pending_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM book_requests WHERE status = 'pending'")['count'],
    'total_fines' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE status = 'unpaid'")['total'],
    'this_month_borrowings' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE MONTH(borrow_date) = MONTH(CURDATE()) AND YEAR(borrow_date) = YEAR(CURDATE())")['count']
];

// Recent borrowings
$recentBorrowings = $db->fetchAll(
    "SELECT b.*, u.name as user_name, u.user_code, bk.title as book_title, bk.author as book_author
     FROM borrowings b
     JOIN users u ON b.user_id = u.id
     JOIN books bk ON b.book_id = bk.id
     ORDER BY b.created_at DESC
     LIMIT 10"
);

// Overdue borrowings
$overdueBorrowings = $db->fetchAll(
    "SELECT b.*, u.name as user_name, u.user_code, u.email, u.phone, bk.title as book_title,
            DATEDIFF(CURDATE(), b.due_date) as days_overdue
     FROM borrowings b
     JOIN users u ON b.user_id = u.id
     JOIN books bk ON b.book_id = bk.id
     WHERE b.status = 'overdue'
     ORDER BY days_overdue DESC
     LIMIT 5"
);

// Pending book requests
$pendingRequests = $db->fetchAll(
    "SELECT br.*, u.name as user_name, u.user_code, uni.code as university_code
     FROM book_requests br
     JOIN users u ON br.user_id = u.id
     JOIN universities uni ON br.university_id = uni.id
     WHERE br.status = 'pending'
     ORDER BY br.created_at DESC
     LIMIT 5"
);

// Popular books
$popularBooks = $db->fetchAll(
    "SELECT b.id, b.title, b.author, b.category, COUNT(br.id) as borrow_count
     FROM books b
     LEFT JOIN borrowings br ON b.id = br.book_id
     GROUP BY b.id, b.title, b.author, b.category
     ORDER BY borrow_count DESC
     LIMIT 5"
);

$pageTitle = 'Dashboard Admin - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .stats-card-modern {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }
        .stats-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: #3B82F6;
        }
        .stats-icon-modern {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .gradient-blue { background: linear-gradient(135deg, #3B82F6, #2563EB); }
        .gradient-green { background: linear-gradient(135deg, #10B981, #059669); }
        .gradient-purple { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
        .gradient-orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .gradient-red { background: linear-gradient(135deg, #EF4444, #DC2626); }
        .gradient-cyan { background: linear-gradient(135deg, #06B6D4, #0891B2); }
        .gradient-pink { background: linear-gradient(135deg, #EC4899, #DB2777); }
        .gradient-indigo { background: linear-gradient(135deg, #6366F1, #4F46E5); }

        .info-card-modern {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }
        .info-card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .info-card-modern .card-header {
            background: none;
            border: none;
            padding: 0 0 1rem 0;
        }
        .info-card-modern .card-header h6 {
            font-weight: 700;
            color: #1F2937;
            margin: 0;
        }
        .info-card-modern .card-body {
            padding: 0;
        }
        .info-card-modern .table {
            margin: 0;
        }
        .info-card-modern .card-footer {
            background: none;
            border: none;
            padding: 1rem 0 0 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Dashboard Admin</h1>
                    <p class="text-muted mb-0">Selamat datang kembali, <?php echo htmlspecialchars($currentUser['name']); ?>! 👋</p>
                </div>
                <div class="text-muted">
                    <i class="bi bi-calendar3 me-2"></i><?php echo date('l, d F Y'); ?>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <a href="<?php echo SITE_URL; ?>/admin/books/index.php" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern gradient-blue text-white">
                                    <i class="bi bi-book-fill"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_books']); ?></h3>
                                    <p class="text-muted mb-0">Total Buku</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card-modern">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon-modern gradient-green text-white">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['available_books']); ?></h3>
                                <p class="text-muted mb-0">Buku Tersedia</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern gradient-cyan text-white">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['active_borrowings']); ?></h3>
                                    <p class="text-muted mb-0">Peminjaman Aktif</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php?status=overdue" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern <?php echo $stats['overdue_books'] > 0 ? 'gradient-red' : 'gradient-green'; ?> text-white">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['overdue_books']); ?></h3>
                                    <p class="text-muted mb-0">Buku Terlambat</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <a href="<?php echo SITE_URL; ?>/admin/requests/index.php" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern gradient-orange text-white">
                                    <i class="bi bi-cart-plus-fill"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['pending_requests']); ?></h3>
                                    <p class="text-muted mb-0">Permintaan Pending</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="<?php echo SITE_URL; ?>/admin/users/index.php" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern gradient-purple text-white">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_users']); ?></h3>
                                    <p class="text-muted mb-0">Total Pengguna</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card-modern">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon-modern gradient-indigo text-white">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['this_month_borrowings']); ?></h3>
                                <p class="text-muted mb-0">Peminjaman Bulan Ini</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card-modern">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon-modern gradient-pink text-white">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h3 class="fw-bold mb-0" style="font-size: 1.5rem;"><?php echo formatCurrency($stats['total_fines']); ?></h3>
                                <p class="text-muted mb-0">Denda Belum Dibayar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Recent Borrowings -->
                <div class="col-lg-8">
                    <div class="info-card-modern">
                        <div class="card-header">
                            <h6><i class="bi bi-clock-history me-2 text-primary"></i>Peminjaman Terbaru</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($recentBorrowings): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Pengguna</th>
                                                <th>Buku</th>
                                                <th>Tgl Pinjam</th>
                                                <th>Jatuh Tempo</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBorrowings as $borrow): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?php echo $borrow['user_name']; ?></div>
                                                        <small class="text-muted"><?php echo $borrow['user_code']; ?></small>
                                                    </td>
                                                    <td>
                                                        <div><?php echo truncate($borrow['book_title'], 30); ?></div>
                                                        <small class="text-muted"><?php echo $borrow['book_author']; ?></small>
                                                    </td>
                                                    <td><?php echo formatDate($borrow['borrow_date']); ?></td>
                                                    <td><?php echo formatDate($borrow['due_date']); ?></td>
                                                    <td><?php echo getStatusBadge($borrow['status']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3">Belum ada peminjaman</h5>
                                    <p class="text-muted">Transaksi peminjaman akan muncul di sini</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($recentBorrowings): ?>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php" class="btn btn-sm btn-primary">
                                Lihat Semua Peminjaman
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Popular Books -->
                <div class="col-lg-4">
                    <div class="info-card-modern">
                        <div class="card-header">
                            <h6><i class="bi bi-star-fill me-2 text-warning"></i>Buku Populer</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($popularBooks): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($popularBooks as $book): ?>
                                        <li class="list-group-item px-0 border-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-bold"><?php echo truncate($book['title'], 35); ?></div>
                                                    <small class="text-muted"><?php echo $book['author']; ?></small>
                                                </div>
                                                <span class="badge bg-primary rounded-pill"><?php echo $book['borrow_count']; ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-star text-muted" style="font-size: 2.5rem;"></i>
                                    <p class="text-muted mt-2">Belum ada data</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Overdue Borrowings Alert -->
                <?php if ($overdueBorrowings): ?>
                <div class="col-lg-6">
                    <div class="info-card-modern" style="border-left: 4px solid #EF4444;">
                        <div class="card-header">
                            <h6><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Peringatan Buku Terlambat</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pengguna</th>
                                            <th>Buku</th>
                                            <th>Terlambat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($overdueBorrowings as $overdue): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo $overdue['user_name']; ?></div>
                                                    <small class="text-muted"><?php echo $overdue['user_code']; ?></small>
                                                </td>
                                                <td><?php echo truncate($overdue['book_title'], 25); ?></td>
                                                <td><span class="badge bg-danger"><?php echo $overdue['days_overdue']; ?> hari</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pending Book Requests -->
                <?php if ($pendingRequests): ?>
                <div class="col-lg-6">
                    <div class="info-card-modern" style="border-left: 4px solid #F59E0B;">
                        <div class="card-header">
                            <h6><i class="bi bi-cart-plus-fill me-2 text-warning"></i>Permintaan Buku Pending</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($pendingRequests as $request): ?>
                                    <?php if (is_array($request)): ?>
                                    <li class="list-group-item px-0 border-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo truncate($request['title'] ?? 'N/A', 30); ?></div>
                                                <small class="text-muted">
                                                    oleh <?php echo htmlspecialchars($request['author'] ?? 'N/A'); ?> | Diminta oleh <?php echo htmlspecialchars($request['user_name'] ?? 'N/A'); ?>
                                                </small>
                                            </div>
                                            <a href="<?php echo SITE_URL; ?>/admin/requests/view.php?id=<?php echo $request['id']; ?>"
                                               class="btn btn-sm btn-warning ms-2">Tinjau</a>
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/admin/requests/index.php" class="btn btn-sm btn-warning">
                                Lihat Semua Permintaan
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
