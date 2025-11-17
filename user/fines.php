<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Handle payment action
if (isset($_POST['pay_fine'])) {
    $fineId = (int)$_POST['fine_id'];
    // In real app, this would update the database and process payment
    redirect(SITE_URL . '/user/fines.php', 'Pembayaran berhasil! Denda telah lunas.', 'success');
}

// Get fines from database or use dummy data
try {
    $fines = $db->fetchAll("
        SELECT f.*, b.title, b.author, bo.borrow_date, bo.due_date, bo.return_date
        FROM fines f
        JOIN borrowings bo ON f.borrowing_id = bo.id
        JOIN books b ON bo.book_id = b.id
        WHERE bo.user_id = ?
        ORDER BY f.created_at DESC
    ", [$currentUser['id']]);
} catch (Exception $e) {
    $fines = [];
}

// If no data, use dummy data
if (empty($fines)) {
    $fines = [
        [
            'id' => 1,
            'title' => 'Ekonomi Makro',
            'author' => 'Gregory Mankiw',
            'borrow_date' => date('Y-m-d', strtotime('-20 days')),
            'due_date' => date('Y-m-d', strtotime('-6 days')),
            'return_date' => null,
            'days_late' => 6,
            'amount' => 12000,
            'status' => 'unpaid',
            'fine_type' => 'late_return',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
        ],
        [
            'id' => 2,
            'title' => 'Pemasaran Modern',
            'author' => 'Philip Kotler',
            'borrow_date' => date('Y-m-d', strtotime('-25 days')),
            'due_date' => date('Y-m-d', strtotime('-11 days')),
            'return_date' => date('Y-m-d', strtotime('-8 days')),
            'days_late' => 3,
            'amount' => 6000,
            'status' => 'paid',
            'fine_type' => 'late_return',
            'created_at' => date('Y-m-d H:i:s', strtotime('-11 days')),
            'paid_at' => date('Y-m-d H:i:s', strtotime('-8 days'))
        ],
        [
            'id' => 3,
            'title' => 'Analisis Laporan Keuangan',
            'author' => 'Kasmir',
            'borrow_date' => date('Y-m-d', strtotime('-30 days')),
            'due_date' => date('Y-m-d', strtotime('-16 days')),
            'return_date' => date('Y-m-d', strtotime('-15 days')),
            'days_late' => 1,
            'amount' => 2000,
            'status' => 'paid',
            'fine_type' => 'late_return',
            'created_at' => date('Y-m-d H:i:s', strtotime('-16 days')),
            'paid_at' => date('Y-m-d H:i:s', strtotime('-15 days'))
        ],
        [
            'id' => 4,
            'title' => 'Manajemen Operasional',
            'author' => 'Jay Heizer',
            'borrow_date' => date('Y-m-d', strtotime('-45 days')),
            'due_date' => date('Y-m-d', strtotime('-31 days')),
            'return_date' => date('Y-m-d', strtotime('-28 days')),
            'days_late' => 3,
            'amount' => 6000,
            'status' => 'paid',
            'fine_type' => 'late_return',
            'created_at' => date('Y-m-d H:i:s', strtotime('-31 days')),
            'paid_at' => date('Y-m-d H:i:s', strtotime('-28 days'))
        ]
    ];
}

// Calculate statistics
$totalFines = array_sum(array_column($fines, 'amount'));
$unpaidAmount = array_sum(array_column(array_filter($fines, fn($f) => $f['status'] === 'unpaid'), 'amount'));
$paidAmount = array_sum(array_column(array_filter($fines, fn($f) => $f['status'] === 'paid'), 'amount'));
$unpaidCount = count(array_filter($fines, fn($f) => $f['status'] === 'unpaid'));

$pageTitle = 'Denda Saya - ' . SITE_NAME;
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
    <style>
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        .fine-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .fine-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .status-unpaid { border-left-color: #EF4444; }
        .status-paid { border-left-color: #10B981; }
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
                    <h1 class="h2 fw-bold mb-1">Denda Saya</h1>
                    <p class="text-muted mb-0">Kelola denda keterlambatan Anda</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/user/borrowing.php" class="btn btn-primary">
                    <i class="bi bi-clock-history me-2"></i>Lihat Peminjaman
                </a>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stats-card" style="border-left-color: #EF4444;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0 text-danger"><?php echo formatCurrency($unpaidAmount); ?></h4>
                                <p class="text-muted mb-0 small">Belum Dibayar</p>
                            </div>
                            <div class="text-danger" style="font-size: 2rem;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-left-color: #F59E0B;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0"><?php echo $unpaidCount; ?></h4>
                                <p class="text-muted mb-0 small">Denda Aktif</p>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-left-color: #10B981;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0 text-success"><?php echo formatCurrency($paidAmount); ?></h4>
                                <p class="text-muted mb-0 small">Sudah Dibayar</p>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-left-color: #6B7280;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0"><?php echo formatCurrency($totalFines); ?></h4>
                                <p class="text-muted mb-0 small">Total Semua Denda</p>
                            </div>
                            <div class="text-secondary" style="font-size: 2rem;">
                                <i class="bi bi-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($unpaidAmount > 0): ?>
                <div class="alert alert-warning mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Anda memiliki denda yang belum dibayar sebesar <strong><?php echo formatCurrency($unpaidAmount); ?></strong>. Mohon segera lakukan pembayaran untuk dapat meminjam buku kembali.
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button">
                        <i class="bi bi-list me-2"></i>Semua
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="unpaid-tab" data-bs-toggle="pill" data-bs-target="#unpaid" type="button">
                        <i class="bi bi-exclamation-circle me-2"></i>Belum Dibayar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="paid-tab" data-bs-toggle="pill" data-bs-target="#paid" type="button">
                        <i class="bi bi-check-circle me-2"></i>Sudah Dibayar
                    </button>
                </li>
            </ul>

            <!-- Fines List -->
            <div class="tab-content">
                <div class="tab-pane fade show active" id="all">
                    <?php if (empty($fines)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-emoji-smile text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Hebat! Anda tidak memiliki denda</p>
                            <p class="small text-muted">Kembalikan buku tepat waktu untuk menghindari denda</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($fines as $fine): ?>
                            <div class="fine-card status-<?php echo $fine['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($fine['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($fine['author']); ?>
                                        </p>
                                        <div class="d-flex gap-3 small mb-2">
                                            <span><strong>Dipinjam:</strong> <?php echo indonesianDate($fine['borrow_date']); ?></span>
                                            <span class="text-danger"><strong>Jatuh Tempo:</strong> <?php echo indonesianDate($fine['due_date']); ?></span>
                                            <?php if ($fine['return_date']): ?>
                                                <span><strong>Dikembalikan:</strong> <?php echo indonesianDate($fine['return_date']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="alert alert-danger py-2 px-3 mb-0">
                                            <i class="bi bi-calendar-x me-1"></i>
                                            Terlambat <strong><?php echo $fine['days_late']; ?> hari</strong>
                                            × Rp 2.000/hari = <strong><?php echo formatCurrency($fine['amount']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($fine['status']); ?>
                                        <div class="mt-3">
                                            <h4 class="fw-bold mb-0 <?php echo $fine['status'] === 'unpaid' ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo formatCurrency($fine['amount']); ?>
                                            </h4>
                                        </div>
                                        <?php if ($fine['status'] === 'unpaid'): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-danger btn-sm" onclick="payFine(<?php echo $fine['id']; ?>, '<?php echo htmlspecialchars($fine['title']); ?>', <?php echo $fine['amount']; ?>)">
                                                    <i class="bi bi-credit-card me-1"></i>Bayar Sekarang
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-check-circle me-1"></i>Dibayar: <?php echo formatDateTime($fine['paid_at'], 'd M Y'); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="unpaid">
                    <?php
                    $filtered = array_filter($fines, fn($f) => $f['status'] === 'unpaid');
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Tidak ada denda yang belum dibayar</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $fine): ?>
                            <div class="fine-card status-<?php echo $fine['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($fine['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($fine['author']); ?>
                                        </p>
                                        <div class="alert alert-danger py-2 px-3 mb-0">
                                            <i class="bi bi-calendar-x me-1"></i>
                                            Terlambat <strong><?php echo $fine['days_late']; ?> hari</strong>
                                            × Rp 2.000/hari = <strong><?php echo formatCurrency($fine['amount']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <h4 class="fw-bold mb-0 text-danger">
                                            <?php echo formatCurrency($fine['amount']); ?>
                                        </h4>
                                        <div class="mt-3">
                                            <button class="btn btn-danger" onclick="payFine(<?php echo $fine['id']; ?>, '<?php echo htmlspecialchars($fine['title']); ?>', <?php echo $fine['amount']; ?>)">
                                                <i class="bi bi-credit-card me-1"></i>Bayar Sekarang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>

                <div class="tab-pane fade" id="paid">
                    <?php
                    $filtered = array_filter($fines, fn($f) => $f['status'] === 'paid');
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada riwayat pembayaran denda</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $fine): ?>
                            <div class="fine-card status-<?php echo $fine['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($fine['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($fine['author']); ?>
                                        </p>
                                        <div class="small text-muted">
                                            Terlambat <?php echo $fine['days_late']; ?> hari • Dibayar: <?php echo indonesianDate($fine['paid_at']); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($fine['status']); ?>
                                        <div class="mt-2">
                                            <h4 class="fw-bold mb-0 text-success">
                                                <?php echo formatCurrency($fine['amount']); ?>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function payFine(id, title, amount) {
            Swal.fire({
                title: 'Bayar Denda',
                html: `
                    <p>Buku: <strong>${title}</strong></p>
                    <p>Jumlah: <strong>Rp ${amount.toLocaleString('id-ID')}</strong></p>
                    <hr>
                    <p class="small text-muted">Pilih metode pembayaran:</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="processPayment(${id}, 'gopay')">
                            <i class="bi bi-wallet2 me-2"></i>GoPay
                        </button>
                        <button class="btn btn-success" onclick="processPayment(${id}, 'ovo')">
                            <i class="bi bi-wallet2 me-2"></i>OVO
                        </button>
                        <button class="btn btn-info" onclick="processPayment(${id}, 'dana')">
                            <i class="bi bi-wallet2 me-2"></i>DANA
                        </button>
                        <button class="btn btn-warning" onclick="processPayment(${id}, 'bank')">
                            <i class="bi bi-bank me-2"></i>Transfer Bank
                        </button>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Batal'
            });
        }

        function processPayment(id, method) {
            Swal.close();
            showLoading('Memproses pembayaran...');

            setTimeout(() => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="pay_fine" value="1">
                    <input type="hidden" name="fine_id" value="${id}">
                    <input type="hidden" name="payment_method" value="${method}">
                `;
                document.body.appendChild(form);
                form.submit();
            }, 1500);
        }
    </script>
</body>
</html>
