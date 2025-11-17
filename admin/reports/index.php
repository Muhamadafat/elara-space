<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Get date range from filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Overall Statistics
$stats = [
    'total_books' => $db->fetchOne("SELECT COUNT(*) as count FROM books")['count'],
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role IN ('mahasiswa', 'dosen', 'staff')")['count'],
    'total_borrowings' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE borrow_date BETWEEN ? AND ?", [$startDate, $endDate])['count'],
    'active_borrowings' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE status IN ('borrowed', 'overdue')")['count'],
    'total_fines' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE status = 'unpaid'")['total'],
    'total_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM book_requests WHERE created_at BETWEEN ? AND ?", [$startDate, $endDate])['count']
];

// Borrowing trends (last 7 days)
$borrowingTrends = $db->fetchAll("
    SELECT DATE(borrow_date) as date, COUNT(*) as count
    FROM borrowings
    WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(borrow_date)
    ORDER BY date ASC
");

// Top borrowed books
$topBooks = $db->fetchAll("
    SELECT b.title, b.author, COUNT(br.id) as borrow_count
    FROM borrowings br
    JOIN books b ON br.book_id = b.id
    WHERE br.borrow_date BETWEEN ? AND ?
    GROUP BY br.book_id, b.title, b.author
    ORDER BY borrow_count DESC
    LIMIT 10
", [$startDate, $endDate]);

// Top active users
$topUsers = $db->fetchAll("
    SELECT u.name, u.user_code, COUNT(br.id) as borrow_count
    FROM borrowings br
    JOIN users u ON br.user_id = u.id
    WHERE br.borrow_date BETWEEN ? AND ?
    GROUP BY br.user_id, u.name, u.user_code
    ORDER BY borrow_count DESC
    LIMIT 10
", [$startDate, $endDate]);

// Category distribution
$categoryStats = $db->fetchAll("
    SELECT b.category, COUNT(*) as count
    FROM borrowings br
    JOIN books b ON br.book_id = b.id
    WHERE br.borrow_date BETWEEN ? AND ? AND b.category IS NOT NULL
    GROUP BY b.category
    ORDER BY count DESC
    LIMIT 8
", [$startDate, $endDate]);

// University statistics
$universityStats = $db->fetchAll("
    SELECT uni.name, uni.code, COUNT(DISTINCT u.id) as user_count, COUNT(br.id) as borrow_count
    FROM universities uni
    LEFT JOIN users u ON uni.id = u.university_id
    LEFT JOIN borrowings br ON u.id = br.user_id AND br.borrow_date BETWEEN ? AND ?
    GROUP BY uni.id, uni.name, uni.code
    ORDER BY borrow_count DESC
", [$startDate, $endDate]);

$pageTitle = 'Reports & Analytics - ' . SITE_NAME;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card-report {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Reports & Analytics</h1>
                    <p class="text-muted mb-0">Analisis dan laporan sistem perpustakaan</p>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Overview Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Buku</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_books']); ?></h3>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="bi bi-book-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Pengguna</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_users']); ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Peminjaman (Periode)</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_borrowings']); ?></h3>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem;">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Aktif Dipinjam</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['active_borrowings']); ?></h3>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Denda Belum Dibayar</p>
                                <h3 class="fw-bold mb-0"><?php echo formatCurrency($stats['total_fines']); ?></h3>
                            </div>
                            <div class="text-danger" style="font-size: 2.5rem;">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card-report">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Permintaan Buku</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_requests']); ?></h3>
                            </div>
                            <div class="text-secondary" style="font-size: 2.5rem;">
                                <i class="bi bi-cart-plus-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">
                <!-- Top Books -->
                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Top 10 Buku Terpopuler</h5>
                        <?php if (!empty($topBooks)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Judul Buku</th>
                                            <th>Penulis</th>
                                            <th>Dipinjam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topBooks as $index => $book): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo $book['borrow_count']; ?>x</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Tidak ada data untuk periode ini</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Users -->
                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="fw-bold mb-3"><i class="bi bi-people-fill me-2 text-success"></i>Top 10 Pengguna Aktif</h5>
                        <?php if (!empty($topUsers)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Kode User</th>
                                            <th>Peminjaman</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topUsers as $index => $user): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><code><?php echo htmlspecialchars($user['user_code']); ?></code></td>
                                                <td><span class="badge bg-success"><?php echo $user['borrow_count']; ?>x</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Tidak ada data untuk periode ini</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Category & University Stats -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-info"></i>Distribusi Kategori</h5>
                        <?php if (!empty($categoryStats)): ?>
                            <div style="position: relative; height: 300px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Tidak ada data untuk periode ini</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="fw-bold mb-3"><i class="bi bi-building me-2 text-warning"></i>Statistik Universitas</h5>
                        <?php if (!empty($universityStats)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Universitas</th>
                                            <th>User</th>
                                            <th>Peminjaman</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($universityStats as $uni): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($uni['name']); ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($uni['code']); ?>)</small>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo $uni['user_count']; ?></span></td>
                                                <td><span class="badge bg-warning"><?php echo $uni['borrow_count']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (!empty($categoryStats)): ?>
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($categoryStats, 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($categoryStats, 'count')); ?>,
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                        '#8B5CF6', '#06B6D4', '#EC4899', '#6366F1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
