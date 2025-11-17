<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();

// Handle waive fine
if (isset($_POST['waive_fine'])) {
    $fineId = (int)$_POST['fine_id'];

    try {
        $db->query("UPDATE fines SET status = 'waived' WHERE id = ?", [$fineId]);

        // Log activity
        $currentUser = getCurrentUser();
        $fine = $db->fetchOne("SELECT * FROM fines WHERE id = ?", [$fineId]);
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'waive_fine', 'fines', ?, ?)
        ", [
            $currentUser['id'],
            "Membebaskan denda ID: {$fineId}, Amount: " . formatCurrency($fine['amount']),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        redirect(SITE_URL . '/admin/system/fines.php', 'Denda berhasil dibebaskan!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/system/fines.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle mark as paid
if (isset($_POST['mark_paid'])) {
    $fineId = (int)$_POST['fine_id'];

    try {
        $db->query("
            UPDATE fines
            SET status = 'paid', paid_date = CURDATE()
            WHERE id = ?
        ", [$fineId]);

        // Log activity
        $currentUser = getCurrentUser();
        $fine = $db->fetchOne("SELECT * FROM fines WHERE id = ?", [$fineId]);
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'mark_fine_paid', 'fines', ?, ?)
        ", [
            $currentUser['id'],
            "Menandai denda sebagai lunas ID: {$fineId}, Amount: " . formatCurrency($fine['amount']),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        redirect(SITE_URL . '/admin/system/fines.php', 'Denda berhasil ditandai sebagai lunas!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/system/fines.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $whereClause .= " AND f.status = ?";
    $params[] = $statusFilter;
}

// Get fines
try {
    $fines = $db->fetchAll("
        SELECT f.*, u.name AS user_name, u.user_code, u.email,
               b.borrow_date, b.due_date, b.return_date,
               bk.title AS book_title, bk.author
        FROM fines f
        JOIN users u ON f.user_id = u.id
        JOIN borrowings b ON f.borrowing_id = b.id
        JOIN books bk ON b.book_id = bk.id
        $whereClause
        ORDER BY f.created_at DESC
    ", $params);
} catch (Exception $e) {
    $fines = [];
    $error = $e->getMessage();
}

// Get statistics
try {
    $stats = $db->fetchOne("
        SELECT
            COUNT(*) as total_fines,
            SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'waived' THEN 1 ELSE 0 END) as waived_count,
            SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as unpaid_amount,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
            SUM(amount) as total_amount
        FROM fines
    ");
} catch (Exception $e) {
    $stats = [
        'total_fines' => 0,
        'unpaid_count' => 0,
        'paid_count' => 0,
        'waived_count' => 0,
        'unpaid_amount' => 0,
        'paid_amount' => 0,
        'total_amount' => 0
    ];
}

$pageTitle = 'Fine Management - ' . SITE_NAME;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Fine Management</h1>
                    <p class="text-muted mb-0">Kelola denda keterlambatan</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/system/auto-fine-calculator.php" class="btn btn-primary">
                    <i class="bi bi-calculator me-2"></i>Hitung Denda Otomatis
                </a>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Total Fines</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_fines']); ?></h3>
                            <small><?php echo formatCurrency($stats['total_amount']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Unpaid</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['unpaid_count']); ?></h3>
                            <small><?php echo formatCurrency($stats['unpaid_amount']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Paid</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['paid_count']); ?></h3>
                            <small><?php echo formatCurrency($stats['paid_amount']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Waived</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['waived_count']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Auto Fine Calculator</h6>
                <p class="mb-0 small">
                    Sistem dapat menghitung denda secara otomatis untuk semua buku yang terlambat.
                    Klik tombol "Hitung Denda Otomatis" untuk menjalankan kalkulasi.
                    <br>Anda juga dapat mengatur cron job untuk menjalankan file: <code>/admin/system/auto-fine-calculator.php</code>
                </p>
            </div>

            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="waived" <?php echo $statusFilter === 'waived' ? 'selected' : ''; ?>>Waived</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Fines Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Buku</th>
                                    <th>Due Date</th>
                                    <th>Days Late</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fines)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada denda</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td><?php echo $fine['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fine['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo $fine['user_code']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fine['book_title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($fine['author']); ?></small>
                                            </td>
                                            <td><?php echo indonesianDate($fine['due_date']); ?></td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $fine['days_late']; ?> hari</span>
                                            </td>
                                            <td><strong><?php echo formatCurrency($fine['amount']); ?></strong></td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'unpaid' => 'danger',
                                                    'paid' => 'success',
                                                    'waived' => 'secondary'
                                                ];
                                                $badge = $statusBadges[$fine['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>">
                                                    <?php echo strtoupper($fine['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($fine['status'] === 'unpaid'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="markPaid(<?php echo $fine['id']; ?>)">
                                                        <i class="bi bi-check-circle"></i> Lunas
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary" onclick="waiveFine(<?php echo $fine['id']; ?>)">
                                                        <i class="bi bi-x-circle"></i> Bebaskan
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function markPaid(fineId) {
            Swal.fire({
                title: 'Tandai Sebagai Lunas?',
                text: 'Denda akan ditandai sebagai sudah dibayar.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lunas',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="mark_paid" value="1">
                        <input type="hidden" name="fine_id" value="${fineId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function waiveFine(fineId) {
            Swal.fire({
                title: 'Bebaskan Denda?',
                text: 'Denda akan dibebaskan (tidak perlu dibayar).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Bebaskan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="waive_fine" value="1">
                        <input type="hidden" name="fine_id" value="${fineId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
