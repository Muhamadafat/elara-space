<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle mark as paid
if (isset($_POST['mark_paid']) && isset($_POST['fine_id'])) {
    $fineId = (int)$_POST['fine_id'];

    if ($db->execute("UPDATE fines SET status = 'paid', paid_at = NOW() WHERE id = ?", [$fineId])) {
        logActivity($currentUser['id'], 'mark_fine_paid', 'fines', 'Marked fine ID ' . $fineId . ' as paid');
        setFlashMessage('success', 'Denda berhasil ditandai sebagai lunas');
    } else {
        setFlashMessage('error', 'Gagal mengupdate denda');
    }

    redirect(SITE_URL . '/admin/fines/index.php');
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.user_code LIKE ? OR b.title LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status)) {
    $where[] = "f.status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalFines = $db->fetchOne("SELECT COUNT(*) as count FROM fines f
                              JOIN users u ON f.user_id = u.id
                              JOIN borrowings br ON f.borrowing_id = br.id
                              JOIN books b ON br.book_id = b.id
                              $whereClause", $params)['count'];
$pagination = paginate($totalFines, $page);

// Get fines with details
$query = "SELECT f.*, u.name as user_name, u.user_code, u.email,
          b.title as book_title, b.isbn,
          br.borrow_date, br.due_date, br.return_date,
          DATEDIFF(COALESCE(br.return_date, CURDATE()), br.due_date) as days_overdue
          FROM fines f
          JOIN users u ON f.user_id = u.id
          JOIN borrowings br ON f.borrowing_id = br.id
          JOIN books b ON br.book_id = b.id
          $whereClause
          ORDER BY f.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$fines = $db->fetchAll($query, $params);

// Get statistics
$stats = [
    'total_fines' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM fines")['total'],
    'unpaid_fines' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE status = 'unpaid'")['total'],
    'paid_fines' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM fines WHERE status = 'paid'")['total'],
    'total_count' => $db->fetchOne("SELECT COUNT(*) as count FROM fines")['count']
];

$pageTitle = 'Fines Management - ' . SITE_NAME;
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
        .fine-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border-left: 4px solid transparent;
        }
        .fine-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .fine-card.unpaid {
            border-left-color: #EF4444;
        }
        .fine-card.paid {
            border-left-color: #10B981;
            opacity: 0.8;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .amount-display {
            font-size: 1.5rem;
            font-weight: 700;
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
                    <h1 class="h2 fw-bold mb-1">Fines Management</h1>
                    <p class="text-muted mb-0">Kelola denda keterlambatan peminjaman</p>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Statistics -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Denda</p>
                                <h4 class="fw-bold mb-0"><?php echo formatCurrency($stats['total_fines']); ?></h4>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Belum Dibayar</p>
                                <h4 class="fw-bold mb-0 text-danger"><?php echo formatCurrency($stats['unpaid_fines']); ?></h4>
                            </div>
                            <div class="text-danger" style="font-size: 2.5rem;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Sudah Dibayar</p>
                                <h4 class="fw-bold mb-0 text-success"><?php echo formatCurrency($stats['paid_fines']); ?></h4>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Jumlah Kasus</p>
                                <h4 class="fw-bold mb-0"><?php echo formatNumber($stats['total_count']); ?></h4>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem;">
                                <i class="bi bi-list-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Cari user, buku..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="unpaid" <?php echo $status == 'unpaid' ? 'selected' : ''; ?>>Belum Dibayar</option>
                                <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Fines List -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul me-2 text-primary"></i>
                    Daftar Denda <span class="badge bg-primary"><?php echo formatNumber($totalFines); ?></span>
                </h5>
            </div>

            <?php if (!empty($fines)): ?>
                <div class="row g-4 mb-4">
                    <?php foreach ($fines as $fine): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="fine-card <?php echo $fine['status']; ?>">
                                <!-- Status Badge -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <?php if ($fine['status'] == 'unpaid'): ?>
                                        <span class="badge bg-danger">Belum Dibayar</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Lunas</span>
                                    <?php endif; ?>
                                    <span class="badge bg-warning text-dark"><?php echo $fine['days_overdue']; ?> hari</span>
                                </div>

                                <!-- Amount -->
                                <div class="text-center mb-3">
                                    <div class="amount-display text-<?php echo $fine['status'] == 'unpaid' ? 'danger' : 'success'; ?>">
                                        <?php echo formatCurrency($fine['amount']); ?>
                                    </div>
                                    <small class="text-muted">Total Denda</small>
                                </div>

                                <!-- User Info -->
                                <div class="mb-3">
                                    <h6 class="fw-bold mb-1">
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($fine['user_name']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <code><?php echo htmlspecialchars($fine['user_code']); ?></code>
                                    </small>
                                </div>

                                <!-- Book Info -->
                                <div class="mb-3 pb-3 border-bottom">
                                    <p class="mb-1 small">
                                        <i class="bi bi-book me-1"></i>
                                        <strong><?php echo truncate($fine['book_title'], 40); ?></strong>
                                    </p>
                                    <?php if ($fine['isbn']): ?>
                                        <small class="text-muted">ISBN: <?php echo htmlspecialchars($fine['isbn']); ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- Dates -->
                                <div class="mb-3">
                                    <div class="row small">
                                        <div class="col-6">
                                            <div class="text-muted">Jatuh Tempo</div>
                                            <div class="fw-bold"><?php echo formatDate($fine['due_date']); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Dikembalikan</div>
                                            <div class="fw-bold"><?php echo $fine['return_date'] ? formatDate($fine['return_date']) : '-'; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($fine['status'] == 'paid'): ?>
                                    <div class="alert alert-success mb-0 py-2 small">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Dibayar: <?php echo formatDate($fine['paid_at']); ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="fine_id" value="<?php echo $fine['id']; ?>">
                                            <button type="submit" name="mark_paid" class="btn btn-success btn-sm w-100" onclick="return confirm('Tandai denda ini sebagai lunas?')">
                                                <i class="bi bi-check-circle me-2"></i>Tandai Lunas
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php echo renderPagination($pagination, 'index.php'); ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Tidak ada denda</h4>
                    <p class="text-muted">Semua peminjaman dikembalikan tepat waktu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
