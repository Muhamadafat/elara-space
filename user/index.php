<?php
require_once '../config/config.php';
requireLogin();

// Redirect admins to admin panel
if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Get simple stats
$totalBooks = $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'] ?? 0;
$availableBooks = $db->fetchOne("SELECT COUNT(*) as count FROM books WHERE status = 'available'")['count'] ?? 0;

// Get user borrowing stats
$userBorrowings = $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'", [$currentUser['id']])['count'] ?? 0;

// Get active borrowings for user
$activeBorrowings = $db->fetchAll(
    "SELECT b.*, bk.title, bk.author, bk.isbn,
            DATEDIFF(b.due_date, CURDATE()) as days_remaining
     FROM borrowings b
     JOIN books bk ON b.book_id = bk.id
     WHERE b.user_id = ? AND b.status = 'borrowed'
     ORDER BY b.due_date ASC
     LIMIT 5",
    [$currentUser['id']]
);

// Get overdue borrowings for alerts
$overdueBorrowings = $db->fetchAll(
    "SELECT b.*, bk.title, bk.author,
            DATEDIFF(CURDATE(), b.due_date) as days_overdue
     FROM borrowings b
     JOIN books bk ON b.book_id = bk.id
     WHERE b.user_id = ? AND b.status = 'overdue'
     ORDER BY days_overdue DESC",
    [$currentUser['id']]
);

// Get payment history for user
$paymentHistory = $db->fetchAll("
    SELECT f.*, b.title, b.author, bo.borrow_date, bo.return_date
    FROM fines f
    JOIN borrowings bo ON f.borrowing_id = bo.id
    JOIN books b ON bo.book_id = b.id
    WHERE f.user_id = ? AND f.status = 'paid'
    ORDER BY f.paid_at DESC
    LIMIT 5
", [$currentUser['id']]);

// Get payment statistics
$paymentStats = $db->fetchOne("
    SELECT
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as total_paid_count,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_paid_amount,
        COUNT(CASE WHEN status = 'unpaid' THEN 1 END) as unpaid_count,
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END), 0) as unpaid_amount
    FROM fines
    WHERE user_id = ?
", [$currentUser['id']]);

// Get recent books
$recentBooks = $db->fetchAll("SELECT * FROM books WHERE status = 'available' ORDER BY created_at DESC LIMIT 6");

// If no books, use dummy data
if (empty($recentBooks)) {
    $recentBooks = [
        ['id' => 1, 'title' => 'Manajemen Strategis', 'author' => 'Fred David', 'category' => 'Manajemen', 'available_quantity' => 5, 'status' => 'available'],
        ['id' => 2, 'title' => 'Akuntansi Biaya', 'author' => 'William Carter', 'category' => 'Akuntansi', 'available_quantity' => 8, 'status' => 'available'],
        ['id' => 3, 'title' => 'Ekonomi Makro', 'author' => 'Gregory Mankiw', 'category' => 'Ekonomi', 'available_quantity' => 10, 'status' => 'available'],
        ['id' => 4, 'title' => 'Pemasaran Modern', 'author' => 'Philip Kotler', 'category' => 'Manajemen', 'available_quantity' => 6, 'status' => 'available'],
        ['id' => 5, 'title' => 'Analisis Laporan Keuangan', 'author' => 'Kasmir', 'category' => 'Akuntansi', 'available_quantity' => 4, 'status' => 'available'],
        ['id' => 6, 'title' => 'Bisnis Internasional', 'author' => 'Charles Hill', 'category' => 'Bisnis', 'available_quantity' => 7, 'status' => 'available']
    ];
    $totalBooks = 12;
    $availableBooks = 12;
}

// Get categories
$categories = $db->fetchAll("SELECT category, COUNT(*) as count FROM books WHERE category IS NOT NULL GROUP BY category ORDER BY count DESC LIMIT 4");

// If no categories, use dummy categories
if (empty($categories)) {
    $categories = [
        ['category' => 'Manajemen', 'count' => 4],
        ['category' => 'Akuntansi', 'count' => 3],
        ['category' => 'Ekonomi', 'count' => 2],
        ['category' => 'Bisnis', 'count' => 2]
    ];
}

$pageTitle = 'Dashboard - ' . SITE_NAME;
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

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #E5E7EB;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .action-card:hover {
            transform: translateY(-5px);
            border-color: #3B82F6;
            box-shadow: 0 8px 25px rgba(59,130,246,0.15);
            color: #3B82F6;
        }
        .action-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #3B82F6;
        }

        .book-card-modern {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
        }
        .book-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .book-cover-modern {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3.5rem;
            position: relative;
        }
        .category-badge-modern {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        .category-badge-modern:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(59,130,246,0.3);
        }

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
                    <h1 class="h2 fw-bold mb-1">Dashboard</h1>
                    <p class="text-muted mb-0">Selamat datang kembali, <?php echo htmlspecialchars($currentUser['name']); ?>! 👋</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Jelajahi Buku
                </a>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stats-card-modern">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon-modern gradient-blue text-white">
                                <i class="bi bi-book-fill"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $totalBooks; ?></h3>
                                <p class="text-muted mb-0">Total Buku</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stats-card-modern">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon-modern gradient-green text-white">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $availableBooks; ?></h3>
                                <p class="text-muted mb-0">Buku Tersedia</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/user/borrowing.php" class="text-decoration-none">
                        <div class="stats-card-modern">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern gradient-orange text-white">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0"><?php echo $userBorrowings; ?></h3>
                                    <p class="text-muted mb-0">Sedang Dipinjam</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/user/fines.php" class="text-decoration-none">
                        <div class="stats-card-modern" style="<?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'border-color: #EF4444;' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon-modern <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'gradient-red' : 'gradient-purple'; ?> text-white">
                                    <i class="bi bi-<?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?>"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <h3 class="fw-bold mb-0 <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatCurrency($paymentStats['unpaid_amount'] ?? 0); ?>
                                    </h3>
                                    <p class="text-muted mb-0">
                                        <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'Denda Belum Dibayar' : 'Tidak Ada Denda'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Overdue Alert -->
            <?php if (!empty($overdueBorrowings)): ?>
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-2 me-3"></i>
                            <div class="flex-grow-1">
                                <h5 class="mb-1"><strong>Peringatan!</strong> Anda memiliki <?php echo count($overdueBorrowings); ?> buku yang terlambat dikembalikan</h5>
                                <p class="mb-2">Segera kembalikan untuk menghindari denda tambahan:</p>
                                <ul class="mb-0">
                                    <?php foreach ($overdueBorrowings as $overdue): ?>
                                        <li><strong><?php echo htmlspecialchars($overdue['title']); ?></strong> - Terlambat <?php echo $overdue['days_overdue']; ?> hari</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/user/borrowing.php" class="btn btn-light ms-3">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-primary"></i>Aksi Cepat</h5>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/user/books.php" class="action-card">
                        <div class="action-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Telusuri Buku</h6>
                        <p class="text-muted small mb-0">Cari buku favorit</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/browse.php" class="action-card">
                        <div class="action-icon">
                            <i class="bi bi-grid"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Katalog Lengkap</h6>
                        <p class="text-muted small mb-0">Lihat semua koleksi</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="action-card">
                        <div class="action-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Profil Saya</h6>
                        <p class="text-muted small mb-0">Ubah informasi</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo SITE_URL; ?>/" class="action-card">
                        <div class="action-icon">
                            <i class="bi bi-house"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Beranda</h6>
                        <p class="text-muted small mb-0">Kembali ke beranda</p>
                    </a>
                </div>
            </div>

            <!-- Active Borrowings -->
            <?php if (!empty($activeBorrowings)): ?>
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Peminjaman Aktif</h5>
                </div>
                <div class="col-12">
                    <div class="info-card-modern">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Buku</th>
                                            <th>ISBN</th>
                                            <th>Tgl Pinjam</th>
                                            <th>Jatuh Tempo</th>
                                            <th>Sisa Waktu</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeBorrowings as $borrowing): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($borrowing['title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($borrowing['author']); ?></small>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($borrowing['isbn']); ?></code></td>
                                                <td><?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></td>
                                                <td>
                                                    <?php if ($borrowing['days_remaining'] < 0): ?>
                                                        <span class="badge bg-danger">Terlambat <?php echo abs($borrowing['days_remaining']); ?> hari</span>
                                                    <?php elseif ($borrowing['days_remaining'] <= 3): ?>
                                                        <span class="badge bg-warning text-dark"><?php echo $borrowing['days_remaining']; ?> hari lagi</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?php echo $borrowing['days_remaining']; ?> hari lagi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-info">Dipinjam</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/user/borrowing.php" class="btn btn-sm btn-primary">
                                Lihat Semua Peminjaman
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History -->
            <?php if (!empty($paymentHistory)): ?>
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-success"></i>Riwayat Pembayaran Denda</h5>
                        <a href="<?php echo SITE_URL; ?>/user/fines.php" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                    </div>
                </div>
                <div class="col-12">
                    <div class="info-card-modern">
                        <div class="card-body">
                            <!-- Payment Stats -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3" style="background: #F0FDF4; border-radius: 10px; border: 1px solid #86EFAC;">
                                        <div class="me-3">
                                            <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 fw-bold text-success"><?php echo formatCurrency($paymentStats['total_paid_amount'] ?? 0); ?></h4>
                                            <small class="text-muted">Total Denda Dibayar (<?php echo $paymentStats['total_paid_count'] ?? 0; ?> transaksi)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3" style="background: <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? '#FEF2F2' : '#F9FAFB'; ?>; border-radius: 10px; border: 1px solid <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? '#FECACA' : '#E5E7EB'; ?>;">
                                        <div class="me-3">
                                            <i class="bi bi-exclamation-circle-fill <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted'; ?>" style="font-size: 2rem;"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 fw-bold <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted'; ?>"><?php echo formatCurrency($paymentStats['unpaid_amount'] ?? 0); ?></h4>
                                            <small class="text-muted">Denda Belum Dibayar (<?php echo $paymentStats['unpaid_count'] ?? 0; ?> denda)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment History Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Buku</th>
                                            <th>Tgl Dikembalikan</th>
                                            <th>Terlambat</th>
                                            <th>Jumlah Denda</th>
                                            <th>Dibayar Pada</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $payment): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($payment['title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['author']); ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['return_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?php echo $payment['days_late']; ?> hari
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-danger"><?php echo formatCurrency($payment['amount']); ?></strong>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['paid_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Lunas
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/user/fines.php" class="btn btn-sm btn-success">
                                <i class="bi bi-receipt me-2"></i>Lihat Semua Riwayat Denda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Categories -->
            <?php if (!empty($categories)): ?>
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bookmark-fill me-2 text-primary"></i>Kategori Populer</h5>
                </div>
                <?php foreach ($categories as $cat): ?>
                    <div class="col-md-3">
                        <a href="<?php echo SITE_URL; ?>/browse.php?category=<?php echo urlencode($cat['category']); ?>" class="text-decoration-none">
                            <div class="category-badge-modern">
                                <i class="bi bi-tag-fill me-2"></i><?php echo htmlspecialchars($cat['category']); ?>
                                <span class="badge bg-white text-primary ms-2"><?php echo $cat['count']; ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Recent Books -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-star-fill me-2 text-warning"></i>Buku Terbaru</h5>
                        <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                </div>

                <?php if (!empty($recentBooks)): ?>
                    <?php foreach ($recentBooks as $book): ?>
                        <div class="col-md-4 col-lg-2">
                            <a href="<?php echo SITE_URL; ?>/browse.php?book_id=<?php echo $book['id']; ?>" class="text-decoration-none" style="color: inherit;">
                                <div class="book-card-modern">
                                    <div class="book-cover-modern" style="background: none; padding: 0;">
                                        <?php
                                        // Generate random book cover image
                                        $coverImages = [
                                            'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=300&h=200&fit=crop',
                                            'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=200&fit=crop'
                                        ];
                                        $randomCover = $coverImages[array_rand($coverImages)];
                                        ?>
                                        <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                             style="width: 100%; height: 200px; object-fit: cover;">
                                    </div>
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-2" style="height: 44px; overflow: hidden; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </h6>
                                        <p class="text-muted small mb-2" style="height: 20px; overflow: hidden;">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary" style="font-size: 0.7rem;">
                                                <?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?>
                                            </span>
                                            <small class="text-success fw-bold">
                                                <i class="bi bi-check-circle-fill me-1"></i><?php echo $book['available_quantity']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada buku tersedia</p>
                            <a href="<?php echo SITE_URL; ?>/init.php" class="btn btn-primary">Setup Database</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
