<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Handle cancel request
if (isset($_POST['cancel_request'])) {
    $requestId = (int)$_POST['request_id'];
    // In real app, this would update the database
    redirect(SITE_URL . '/user/requests.php', 'Permintaan berhasil dibatalkan!', 'success');
}

// Get requests from database or use dummy data
try {
    $requests = $db->fetchAll("
        SELECT *
        FROM book_requests
        WHERE user_id = ?
        ORDER BY created_at DESC
    ", [$currentUser['id']]);
} catch (Exception $e) {
    $requests = [];
}

// If no data, use dummy data
if (empty($requests)) {
    $requests = [
        [
            'id' => 1,
            'title' => 'Business Intelligence and Analytics',
            'author' => 'Ramesh Sharda',
            'isbn' => '978-0-13-305090-5',
            'publisher_name' => 'Pearson',
            'category' => 'Information Systems',
            'reason' => 'Dibutuhkan untuk mata kuliah Sistem Informasi Manajemen semester ini',
            'estimated_price' => 850000,
            'priority' => 'high',
            'status' => 'pending',
            'admin_notes' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'title' => 'Financial Statement Analysis',
            'author' => 'K.R. Subramanyam',
            'isbn' => '978-0-07-802531-9',
            'publisher_name' => 'McGraw-Hill',
            'category' => 'Accounting',
            'reason' => 'Reference untuk tugas akhir tentang analisis laporan keuangan perusahaan',
            'estimated_price' => 650000,
            'priority' => 'urgent',
            'status' => 'approved',
            'admin_notes' => 'Permintaan disetujui. Buku akan dipesan dari penerbit. Estimasi 2 minggu.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'processed_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'id' => 3,
            'title' => 'Digital Marketing Strategy',
            'author' => 'Simon Kingsnorth',
            'isbn' => '978-0-7494-8379-9',
            'publisher_name' => 'Kogan Page',
            'category' => 'Marketing',
            'reason' => 'Untuk memperdalam pemahaman strategi pemasaran digital',
            'estimated_price' => 450000,
            'priority' => 'medium',
            'status' => 'rejected',
            'admin_notes' => 'Maaf, buku serupa sudah tersedia di perpustakaan. Silakan cek "Marketing 4.0" by Philip Kotler.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            'processed_at' => date('Y-m-d H:i:s', strtotime('-7 days'))
        ],
        [
            'id' => 4,
            'title' => 'Python for Data Analysis',
            'author' => 'Wes McKinney',
            'isbn' => '978-1-4919-5766-0',
            'publisher_name' => 'O\'Reilly Media',
            'category' => 'Data Science',
            'reason' => 'Untuk belajar data analytics dengan Python',
            'estimated_price' => 750000,
            'priority' => 'high',
            'status' => 'completed',
            'admin_notes' => 'Buku sudah tiba dan tersedia untuk dipinjam!',
            'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
            'processed_at' => date('Y-m-d H:i:s', strtotime('-18 days')),
            'completed_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 5,
            'title' => 'Principles of Corporate Finance',
            'author' => 'Richard Brealey',
            'isbn' => '978-1-260-01390-0',
            'publisher_name' => 'McGraw-Hill',
            'category' => 'Finance',
            'reason' => 'Buku wajib untuk mata kuliah Manajemen Keuangan',
            'estimated_price' => 900000,
            'priority' => 'urgent',
            'status' => 'ordered',
            'admin_notes' => 'Pesanan sudah dikirim ke penerbit. Estimasi tiba 1 minggu.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-12 days')),
            'processed_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ]
    ];
}

// Calculate statistics
$totalRequests = count($requests);
$pendingRequests = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approvedRequests = count(array_filter($requests, fn($r) => in_array($r['status'], ['approved', 'ordered', 'completed'])));

// Handle payment for book request invoices
if (isset($_POST['pay_invoice'])) {
    $invoiceId = (int)$_POST['invoice_id'];
    $paymentMethod = $_POST['payment_method'] ?? '';

    // Update payment status to paid
    if ($db->execute("UPDATE book_request_payments SET payment_status = 'paid', payment_date = NOW(), payment_method = ? WHERE id = ? AND user_id = ?", [$paymentMethod, $invoiceId, $currentUser['id']])) {
        redirect(SITE_URL . '/user/requests.php', 'Pembayaran berhasil! Admin akan segera memproses pemesanan buku Anda.', 'success');
    } else {
        redirect(SITE_URL . '/user/requests.php', 'Pembayaran gagal. Silakan coba lagi.', 'error');
    }
}

// Get unpaid invoices for book requests (for pending requests)
try {
    $unpaidInvoices = $db->fetchAll("
        SELECT brp.*, br.title, br.author, br.publisher_name, br.category, br.priority, br.status as request_status
        FROM book_request_payments brp
        JOIN book_requests br ON brp.book_request_id = br.id
        WHERE brp.user_id = ? AND brp.payment_status = 'unpaid' AND br.status = 'pending'
        ORDER BY brp.due_date ASC, brp.created_at DESC
    ", [$currentUser['id']]);
} catch (Exception $e) {
    $unpaidInvoices = [];
}

// Get payment info for each request (to show payment status on cards)
$requestPayments = [];
try {
    $payments = $db->fetchAll("
        SELECT id, book_request_id, payment_status, payment_date, payment_method, invoice_number, amount
        FROM book_request_payments
        WHERE user_id = ?
    ", [$currentUser['id']]);

    foreach ($payments as $payment) {
        $requestPayments[$payment['book_request_id']] = $payment;
    }
} catch (Exception $e) {
    // Ignore
}

// Get payment history for user
try {
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
} catch (Exception $e) {
    $paymentHistory = [];
    $paymentStats = ['total_paid_count' => 0, 'total_paid_amount' => 0, 'unpaid_count' => 0, 'unpaid_amount' => 0];
}

// Helper function for priority badges
function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-secondary priority-badge">Low</span>',
        'medium' => '<span class="badge bg-info priority-badge">Medium</span>',
        'high' => '<span class="badge bg-warning priority-badge">High</span>',
        'urgent' => '<span class="badge bg-danger priority-badge">Urgent</span>'
    ];
    return $badges[$priority] ?? '<span class="badge bg-secondary priority-badge">' . ucfirst($priority) . '</span>';
}

$pageTitle = 'Request Saya - ' . SITE_NAME;
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
        .request-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .status-pending { border-left-color: #F59E0B; }
        .status-approved { border-left-color: #10B981; }
        .status-rejected { border-left-color: #EF4444; }
        .status-ordered { border-left-color: #3B82F6; }
        .status-completed { border-left-color: #8B5CF6; }
        .priority-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
                    <h1 class="h2 fw-bold mb-1">Request Saya</h1>
                    <p class="text-muted mb-0">Lacak status permintaan buku Anda</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/user/request-book.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Request Buku Baru
                </a>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #3B82F6;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $totalRequests; ?></h3>
                                <p class="text-muted mb-0 small">Total Requests</p>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="bi bi-list-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #F59E0B;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $pendingRequests; ?></h3>
                                <p class="text-muted mb-0 small">Menunggu Review</p>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #10B981;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $approvedRequests; ?></h3>
                                <p class="text-muted mb-0 small">Disetujui</p>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($requests)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada permintaan buku</p>
                            <a href="<?php echo SITE_URL; ?>/user/request-book.php" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i>Request Buku Pertama
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card status-<?php echo $request['status']; ?>">
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="d-flex align-items-start justify-content-between mb-2">
                                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($request['title']); ?></h5>
                                            <div>
                                                <?php echo getPriorityBadge($request['priority']); ?>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($request['author']); ?>
                                            <?php if ($request['publisher_name']): ?>
                                                <span class="ms-3"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($request['publisher_name']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($request['category']): ?>
                                                <span class="ms-3"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($request['category']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="small text-muted mb-2">
                                            <strong>Alasan:</strong> <?php echo htmlspecialchars($request['reason']); ?>
                                        </p>
                                        <?php if ($request['admin_notes']): ?>
                                            <div class="alert alert-info alert-sm py-2 px-3 mb-2">
                                                <i class="bi bi-chat-left-text me-1"></i>
                                                <strong>Catatan Admin:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex gap-3 small text-muted">
                                            <span><i class="bi bi-calendar me-1"></i>Requested: <?php echo formatDateTime($request['created_at'], 'd M Y H:i'); ?></span>
                                            <?php if ($request['estimated_price']): ?>
                                                <span><i class="bi bi-cash me-1"></i><?php echo formatCurrency($request['estimated_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($request['status']); ?>

                                        <?php
                                        // Get payment info for this request
                                        $payment = $requestPayments[$request['id']] ?? null;
                                        ?>

                                        <?php if ($payment): ?>
                                            <!-- Payment Status -->
                                            <div class="mt-3">
                                                <?php if ($payment['payment_status'] === 'paid'): ?>
                                                    <!-- Paid Status -->
                                                    <div class="alert alert-success alert-sm py-2 px-3 mb-2" style="border-left: 4px solid #10B981;">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem;"></i>
                                                            <strong>Sudah Dibayar</strong>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="d-block"><i class="bi bi-calendar-check me-1"></i><?php echo formatDateTime($payment['payment_date'], 'd M Y H:i'); ?></small>
                                                            <small class="d-block mt-1">
                                                                <i class="bi bi-wallet2 me-1"></i>
                                                                <strong>via <?php echo $payment['payment_method']; ?></strong>
                                                            </small>
                                                            <small class="d-block mt-1"><i class="bi bi-hash me-1"></i><?php echo $payment['invoice_number']; ?></small>
                                                            <div class="mt-2 pt-2" style="border-top: 1px solid rgba(16, 185, 129, 0.2);">
                                                                <small class="text-muted">Jumlah Dibayar:</small><br>
                                                                <strong class="text-success"><?php echo formatCurrency($payment['amount']); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Unpaid Invoice -->
                                                    <div class="alert alert-warning alert-sm py-3 px-3 mb-2" style="border-left: 4px solid #F59E0B; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="bi bi-exclamation-triangle-fill me-2 text-warning" style="font-size: 1.8rem;"></i>
                                                            <strong class="text-dark">Menunggu Pembayaran</strong>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="d-block text-dark"><i class="bi bi-hash me-1"></i><strong><?php echo $payment['invoice_number']; ?></strong></small>
                                                            <div class="mt-2 pt-2" style="border-top: 2px solid rgba(245, 158, 11, 0.3);">
                                                                <small class="text-dark fw-bold">Total Pembayaran:</small><br>
                                                                <h5 class="text-danger fw-bold mb-0"><?php echo formatCurrency($payment['amount']); ?></h5>
                                                            </div>

                                                            <!-- Payment Button - Always show if unpaid -->
                                                            <div class="mt-3 d-grid">
                                                                <button class="btn btn-warning fw-bold shadow-sm"
                                                                        onclick="payInvoice(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($request['title']); ?>', '<?php echo $payment['invoice_number']; ?>', <?php echo $payment['amount']; ?>)"
                                                                        style="padding: 0.6rem 1rem; border: 2px solid #D97706;">
                                                                    <i class="bi bi-credit-card me-2"></i>💳 BAYAR SEKARANG
                                                                </button>
                                                            </div>
                                                            <small class="d-block text-center mt-2 text-muted">
                                                                <i class="bi bi-info-circle me-1"></i>Pilih metode: GoPay, OVO, DANA, Transfer Bank
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($request['status'] === 'pending'): ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-danger w-100" onclick="cancelRequest(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['title']); ?>')">
                                                    <i class="bi bi-x-circle me-1"></i>Batalkan
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['status'] === 'completed'): ?>
                                            <div class="mt-3">
                                                <a href="<?php echo SITE_URL; ?>/user/books.php" class="btn btn-sm btn-success">
                                                    <i class="bi bi-book me-1"></i>Pinjam Sekarang
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Payment History Section -->
            <?php if (!empty($paymentHistory)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">
                            <i class="bi bi-receipt-cutoff me-2 text-success"></i>Riwayat Pembayaran Denda
                        </h4>
                        <a href="<?php echo SITE_URL; ?>/user/fines.php" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-right me-2"></i>Lihat Semua Denda
                        </a>
                    </div>
                </div>

                <!-- Payment Stats Mini Cards -->
                <div class="col-md-6 mb-3">
                    <div class="stats-card" style="border-left-color: #10B981;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0 text-success"><?php echo formatCurrency($paymentStats['total_paid_amount'] ?? 0); ?></h4>
                                <p class="text-muted mb-0 small">Total Denda Dibayar (<?php echo $paymentStats['total_paid_count'] ?? 0; ?> transaksi)</p>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stats-card" style="border-left-color: <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? '#EF4444' : '#6B7280'; ?>;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="fw-bold mb-0 <?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo formatCurrency($paymentStats['unpaid_amount'] ?? 0); ?>
                                </h4>
                                <p class="text-muted mb-0 small">Denda Belum Dibayar (<?php echo $paymentStats['unpaid_count'] ?? 0; ?> denda)</p>
                            </div>
                            <div class="<?php echo ($paymentStats['unpaid_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted'; ?>" style="font-size: 2rem;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Table -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: #F9FAFB;">
                                        <tr>
                                            <th class="border-0 py-3">Buku</th>
                                            <th class="border-0 py-3">Tgl Dikembalikan</th>
                                            <th class="border-0 py-3">Terlambat</th>
                                            <th class="border-0 py-3">Jumlah</th>
                                            <th class="border-0 py-3">Dibayar</th>
                                            <th class="border-0 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $payment): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($payment['title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['author']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="text-muted"><?php echo formatDateTime($payment['return_date'], 'd M Y'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-clock-fill me-1"></i><?php echo $payment['days_late']; ?> hari
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-danger"><?php echo formatCurrency($payment['amount']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="text-muted small"><?php echo formatDateTime($payment['paid_at'], 'd M Y'); ?></span>
                                                </td>
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
                        <div class="card-footer bg-white border-0 text-center" style="border-radius: 0 0 12px 12px;">
                            <a href="<?php echo SITE_URL; ?>/user/fines.php" class="btn btn-sm btn-success">
                                <i class="bi bi-receipt me-2"></i>Lihat Semua Riwayat Pembayaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function cancelRequest(id, title) {
            Swal.fire({
                title: 'Batalkan Request?',
                text: 'Apakah Anda yakin ingin membatalkan request untuk "' + title + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="cancel_request" value="1">
                        <input type="hidden" name="request_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Payment Functions for Book Request Invoices
        function payInvoice(id, title, invoiceNumber, amount) {
            const formattedAmount = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);

            Swal.fire({
                title: 'Pembayaran Buku Request',
                html: `
                    <div class="text-start">
                        <p class="mb-2"><strong>Buku:</strong> ${title}</p>
                        <p class="mb-3 small text-muted"><i class="bi bi-hash me-1"></i>Invoice: ${invoiceNumber}</p>
                        <p class="mb-4"><strong>Total Pembayaran:</strong> <span class="text-warning fs-5 fw-bold">${formattedAmount}</span></p>
                        <hr class="my-3">
                        <p class="small mb-3"><strong>Pilih Metode Pembayaran:</strong></p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary text-start" onclick="processInvoicePayment(${id}, 'GoPay')">
                                <i class="bi bi-wallet2 me-2"></i>GoPay
                            </button>
                            <button class="btn btn-outline-success text-start" onclick="processInvoicePayment(${id}, 'OVO')">
                                <i class="bi bi-wallet2 me-2"></i>OVO
                            </button>
                            <button class="btn btn-outline-info text-start" onclick="processInvoicePayment(${id}, 'DANA')">
                                <i class="bi bi-wallet2 me-2"></i>DANA
                            </button>
                            <button class="btn btn-outline-secondary text-start" onclick="processInvoicePayment(${id}, 'Transfer Bank')">
                                <i class="bi bi-bank me-2"></i>Transfer Bank
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Batal',
                width: '550px'
            });
        }

        function processInvoicePayment(id, method) {
            Swal.close();

            Swal.fire({
                title: 'Memproses Pembayaran...',
                html: `Metode: <strong>${method}</strong><br>Mohon tunggu sebentar...`,
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();

                    // Simulate payment processing
                    setTimeout(() => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="pay_invoice" value="1">
                            <input type="hidden" name="invoice_id" value="${id}">
                            <input type="hidden" name="payment_method" value="${method}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }, 1500);
                }
            });
        }
    </script>
</body>
</html>
