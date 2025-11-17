<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    redirect(SITE_URL . '/admin/requests/index.php', 'Invalid request ID', 'error');
}

// Get request details
$request = $db->fetchOne("
    SELECT br.*,
           u.name as user_name, u.user_code, u.email as user_email, u.phone as user_phone, u.role,
           uni.name as university_name, uni.code as university_code,
           approver.name as approver_name
    FROM book_requests br
    JOIN users u ON br.user_id = u.id
    JOIN universities uni ON br.university_id = uni.id
    LEFT JOIN users approver ON br.approved_by = approver.id
    WHERE br.id = ?
", [$requestId]);

if (!$request) {
    redirect(SITE_URL . '/admin/requests/index.php', 'Request not found', 'error');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $adminNotes = clean($_POST['admin_notes'] ?? '');

    if ($action === 'approve') {
        $db->execute(
            "UPDATE book_requests SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?",
            [$currentUser['id'], $adminNotes, $requestId]
        );

        createNotification(
            $request['user_id'],
            'Permintaan Buku Disetujui',
            'Permintaan Anda untuk buku "' . $request['title'] . '" telah disetujui!',
            'success'
        );

        logActivity($currentUser['id'], 'approve_request', 'requests', 'Approved request ID: ' . $requestId);
        setFlashMessage('success', 'Permintaan berhasil disetujui');

    } elseif ($action === 'reject') {
        $db->execute(
            "UPDATE book_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?",
            [$currentUser['id'], $adminNotes, $requestId]
        );

        createNotification(
            $request['user_id'],
            'Permintaan Buku Ditolak',
            'Permintaan Anda untuk buku "' . $request['title'] . '" ditolak. Alasan: ' . $adminNotes,
            'warning'
        );

        logActivity($currentUser['id'], 'reject_request', 'requests', 'Rejected request ID: ' . $requestId);
        setFlashMessage('success', 'Permintaan berhasil ditolak');

    } elseif ($action === 'ordered') {
        $db->execute(
            "UPDATE book_requests SET status = 'ordered', ordered_at = NOW(), admin_notes = ? WHERE id = ?",
            [$adminNotes, $requestId]
        );

        createNotification(
            $request['user_id'],
            'Buku Dipesan',
            'Buku "' . $request['title'] . '" telah dipesan dari penerbit!',
            'info'
        );

        logActivity($currentUser['id'], 'order_request', 'requests', 'Marked as ordered: ' . $requestId);
        setFlashMessage('success', 'Ditandai sebagai dipesan');

    } elseif ($action === 'received') {
        $db->execute(
            "UPDATE book_requests SET status = 'received', received_at = NOW(), admin_notes = ? WHERE id = ?",
            [$adminNotes, $requestId]
        );

        createNotification(
            $request['user_id'],
            'Buku Diterima',
            'Buku "' . $request['title'] . '" telah diterima dan tersedia sekarang!',
            'success'
        );

        logActivity($currentUser['id'], 'receive_request', 'requests', 'Marked as received: ' . $requestId);
        setFlashMessage('success', 'Ditandai sebagai diterima. Silakan tambahkan buku ke inventaris.');
    }

    redirect(SITE_URL . '/admin/requests/view.php?id=' . $requestId);
}

$pageTitle = 'Request Details - ' . SITE_NAME;
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
        .priority-urgent { color: #EF4444; }
        .priority-high { color: #F59E0B; }
        .priority-medium { color: #3B82F6; }
        .priority-low { color: #10B981; }
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
                    <h1 class="h2 fw-bold mb-1">Detail Permintaan Buku #<?php echo $requestId; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/requests/index.php">Requests</a></li>
                            <li class="breadcrumb-item active">Detail #<?php echo $requestId; ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/requests/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <!-- Request Info -->
                <div class="col-lg-8">
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-book me-2 text-primary"></i>Informasi Buku</h5>

                        <div class="info-row">
                            <div class="info-label">Judul</div>
                            <div class="info-value fw-bold"><?php echo htmlspecialchars($request['title']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Penulis</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['author']); ?></div>
                        </div>

                        <?php if ($request['isbn']): ?>
                        <div class="info-row">
                            <div class="info-label">ISBN</div>
                            <div class="info-value"><code><?php echo htmlspecialchars($request['isbn']); ?></code></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['publisher_name']): ?>
                        <div class="info-row">
                            <div class="info-label">Penerbit</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['publisher_name']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['category']): ?>
                        <div class="info-row">
                            <div class="info-label">Kategori</div>
                            <div class="info-value">
                                <span class="badge bg-info"><?php echo htmlspecialchars($request['category']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['estimated_price']): ?>
                        <div class="info-row">
                            <div class="info-label">Estimasi Harga</div>
                            <div class="info-value fw-bold text-success"><?php echo formatCurrency($request['estimated_price']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Detail Permintaan</h5>

                        <div class="info-row">
                            <div class="info-label">Tipe Permintaan</div>
                            <div class="info-value">
                                <?php if ($request['request_type'] == 'new_book'): ?>
                                    <span class="badge bg-primary">Buku Baru</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Salinan Tambahan</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Prioritas</div>
                            <div class="info-value">
                                <span class="priority-<?php echo $request['priority']; ?>">
                                    <i class="bi bi-flag-fill me-1"></i>
                                    <?php echo strtoupper($request['priority']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?php echo getStatusBadge($request['status']); ?></div>
                        </div>

                        <?php if ($request['reason']): ?>
                        <div class="info-row">
                            <div class="info-label">Alasan Permintaan</div>
                            <div class="info-value" style="text-align: left; max-width: 60%;">
                                <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Tanggal Permintaan</div>
                            <div class="info-value"><?php echo indonesianDate($request['created_at']); ?></div>
                        </div>

                        <?php if ($request['approved_at']): ?>
                        <div class="info-row">
                            <div class="info-label">Tanggal Disetujui/Ditolak</div>
                            <div class="info-value"><?php echo indonesianDate($request['approved_at']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['ordered_at']): ?>
                        <div class="info-row">
                            <div class="info-label">Tanggal Dipesan</div>
                            <div class="info-value"><?php echo indonesianDate($request['ordered_at']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['received_at']): ?>
                        <div class="info-row">
                            <div class="info-label">Tanggal Diterima</div>
                            <div class="info-value"><?php echo indonesianDate($request['received_at']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['approver_name']): ?>
                        <div class="info-row">
                            <div class="info-label">Diproses oleh</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['approver_name']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($request['admin_notes']): ?>
                        <div class="info-row">
                            <div class="info-label">Catatan Admin</div>
                            <div class="info-value" style="text-align: left; max-width: 60%;">
                                <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Info & Actions -->
                <div class="col-lg-4">
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>Pemohon</h5>

                        <div class="info-row">
                            <div class="info-label">Nama</div>
                            <div class="info-value">
                                <a href="<?php echo SITE_URL; ?>/admin/users/view.php?id=<?php echo $request['user_id']; ?>">
                                    <?php echo htmlspecialchars($request['user_name']); ?>
                                </a>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Kode User</div>
                            <div class="info-value"><code><?php echo htmlspecialchars($request['user_code']); ?></code></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Role</div>
                            <div class="info-value">
                                <span class="badge bg-secondary"><?php echo strtoupper($request['role']); ?></span>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo $request['user_email']; ?>">
                                    <?php echo htmlspecialchars($request['user_email']); ?>
                                </a>
                            </div>
                        </div>

                        <?php if ($request['user_phone']): ?>
                        <div class="info-row">
                            <div class="info-label">Telepon</div>
                            <div class="info-value">
                                <a href="tel:<?php echo $request['user_phone']; ?>">
                                    <?php echo htmlspecialchars($request['user_phone']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Universitas</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($request['university_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($request['university_code']); ?>)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if ($request['status'] == 'pending'): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-primary"></i>Aksi</h5>

                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionType">

                            <div class="mb-3">
                                <label class="form-label">Catatan Admin</label>
                                <textarea name="admin_notes" class="form-control" rows="3" placeholder="Tambahkan catatan (opsional)"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" onclick="submitAction('approve')">
                                    <i class="bi bi-check-circle me-2"></i>Setujui
                                </button>
                                <button type="button" class="btn btn-danger" onclick="submitAction('reject')">
                                    <i class="bi bi-x-circle me-2"></i>Tolak
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php elseif ($request['status'] == 'approved'): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-primary"></i>Aksi</h5>

                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionType">

                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="admin_notes" class="form-control" rows="2" placeholder="Catatan pemesanan"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="button" class="btn btn-info" onclick="submitAction('ordered')">
                                    <i class="bi bi-cart-check me-2"></i>Tandai Dipesan
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php elseif ($request['status'] == 'ordered'): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-primary"></i>Aksi</h5>

                        <form method="POST" id="actionForm">
                            <input type="hidden" name="action" id="actionType">

                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="admin_notes" class="form-control" rows="2" placeholder="Catatan penerimaan"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" onclick="submitAction('received')">
                                    <i class="bi bi-box-seam me-2"></i>Tandai Diterima
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/books/add.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah ke Inventaris
                                </a>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitAction(action) {
            if (confirm('Apakah Anda yakin ingin melanjutkan aksi ini?')) {
                document.getElementById('actionType').value = action;
                document.getElementById('actionForm').submit();
            }
        }
    </script>
</body>
</html>
