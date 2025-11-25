<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle approval/rejection
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $adminNotes = clean($_POST['admin_notes'] ?? '');

    $request = $db->fetchOne("SELECT * FROM book_requests WHERE id = ?", [$requestId]);

    if ($request) {
        if ($action === 'approve') {
            // Check payment status first
            $payment = $db->fetchOne(
                "SELECT payment_status FROM book_request_payments WHERE book_request_id = ?",
                [$requestId]
            );

            if (!$payment || $payment['payment_status'] !== 'paid') {
                setFlashMessage('error', 'User belum melakukan pembayaran! Tunggu sampai user membayar invoice terlebih dahulu.');
                redirect(SITE_URL . '/admin/requests/index.php');
                exit;
            }

            // Update request status
            $db->execute(
                "UPDATE book_requests SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?",
                [$currentUser['id'], $adminNotes, $requestId]
            );

            // Notify user
            createNotification(
                $request['user_id'],
                'Permintaan Buku Disetujui',
                'Permintaan Anda untuk "' . $request['title'] . '" telah disetujui! Buku akan segera dipesan dari penerbit.',
                'success'
            );

            logActivity($currentUser['id'], 'approve_request', 'requests', 'Approved request ID: ' . $requestId);
            setFlashMessage('success', 'Permintaan berhasil disetujui. Buku dapat dipesan dari penerbit.');
        } elseif ($action === 'reject') {
            $db->execute(
                "UPDATE book_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?",
                [$currentUser['id'], $adminNotes, $requestId]
            );

            // Notify user
            createNotification(
                $request['user_id'],
                'Permintaan Buku Ditolak',
                'Permintaan Anda untuk "' . $request['title'] . '" telah ditolak. Alasan: ' . $adminNotes,
                'warning'
            );

            logActivity($currentUser['id'], 'reject_request', 'requests', 'Rejected request ID: ' . $requestId);
            setFlashMessage('success', 'Permintaan ditolak');
        } elseif ($action === 'ordered') {
            $db->execute(
                "UPDATE book_requests SET status = 'ordered', ordered_at = NOW(), admin_notes = ? WHERE id = ?",
                [$adminNotes, $requestId]
            );

            // Notify user
            createNotification(
                $request['user_id'],
                'Buku Sudah Dipesan',
                'Buku "' . $request['title'] . '" sudah dipesan dari penerbit!',
                'info'
            );

            logActivity($currentUser['id'], 'order_request', 'requests', 'Marked as ordered: ' . $requestId);
            setFlashMessage('success', 'Ditandai sudah dipesan');
        } elseif ($action === 'received') {
            $db->execute(
                "UPDATE book_requests SET status = 'received', received_at = NOW(), admin_notes = ? WHERE id = ?",
                [$adminNotes, $requestId]
            );

            // Notify user
            createNotification(
                $request['user_id'],
                'Buku Sudah Diterima',
                'Buku "' . $request['title'] . '" sudah diterima dan tersedia!',
                'success'
            );

            logActivity($currentUser['id'], 'receive_request', 'requests', 'Marked as received: ' . $requestId);
            setFlashMessage('success', 'Ditandai sudah diterima. Silakan tambahkan buku ke inventori.');
        }
    }

    redirect(SITE_URL . '/admin/requests/index.php');
}

// Filters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$university = $_GET['university'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ['1=1'];
$params = [];

if (!empty($status)) {
    $where[] = "br.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $where[] = "br.priority = ?";
    $params[] = $priority;
}

if (!empty($university)) {
    $where[] = "br.university_id = ?";
    $params[] = $university;
}

if (!empty($search)) {
    $where[] = "(br.title LIKE ? OR br.author LIKE ? OR u.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalRequests = $db->fetchOne("SELECT COUNT(*) as count FROM book_requests br
                                 JOIN users u ON br.user_id = u.id
                                 $whereClause", $params)['count'];
$pagination = paginate($totalRequests, $page);

// Get requests with priority sorting + payment status
$query = "SELECT br.*, u.name as user_name, u.user_code, u.email, u.role,
          uni.name as university_name, uni.code as university_code,
          approver.name as approver_name,
          brp.payment_status, brp.payment_date, brp.payment_method, brp.invoice_number, brp.amount as payment_amount
          FROM book_requests br
          JOIN users u ON br.user_id = u.id
          JOIN universities uni ON br.university_id = uni.id
          LEFT JOIN users approver ON br.approved_by = approver.id
          LEFT JOIN book_request_payments brp ON br.id = brp.book_request_id
          $whereClause
          ORDER BY
            CASE br.status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'ordered' THEN 3
                WHEN 'received' THEN 4
                WHEN 'rejected' THEN 5
                WHEN 'cancelled' THEN 6
            END,
            CASE br.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            br.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$requests = $db->fetchAll($query, $params);

// Get universities
$universities = $db->fetchAll("SELECT id, code, name FROM universities ORDER BY name");

$pageTitle = 'Permintaan Buku - ' . SITE_NAME;
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
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Permintaan Buku</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Permintaan Buku</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Cari</label>
                                <input type="text" class="form-control" name="search" placeholder="Judul, Penulis, User..." value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Universitas</label>
                                <select class="form-select" name="university">
                                    <option value="">Semua</option>
                                    <?php foreach ($universities as $uni): ?>
                                        <option value="<?php echo $uni['id']; ?>" <?php echo $university == $uni['id'] ? 'selected' : ''; ?>>
                                            <?php echo $uni['code']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                    <option value="ordered" <?php echo $status == 'ordered' ? 'selected' : ''; ?>>Dipesan</option>
                                    <option value="received" <?php echo $status == 'received' ? 'selected' : ''; ?>>Diterima</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prioritas</label>
                                <select class="form-select" name="priority">
                                    <option value="">Semua Prioritas</option>
                                    <option value="urgent" <?php echo $priority == 'urgent' ? 'selected' : ''; ?>>Mendesak</option>
                                    <option value="high" <?php echo $priority == 'high' ? 'selected' : ''; ?>>Tinggi</option>
                                    <option value="medium" <?php echo $priority == 'medium' ? 'selected' : ''; ?>>Sedang</option>
                                    <option value="low" <?php echo $priority == 'low' ? 'selected' : ''; ?>>Rendah</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="bi bi-cart-plus me-2"></i>Permintaan Buku (<?php echo formatNumber($totalRequests); ?> permintaan)</h6>
                </div>
                <div class="card-body">
                    <?php if ($requests): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Detail Buku</th>
                                        <th>Diminta Oleh</th>
                                        <th>Universitas</th>
                                        <th>Prioritas</th>
                                        <th>Est. Harga</th>
                                        <th>Tgl Request</th>
                                        <th>Pembayaran</th>
                                        <th>Status</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo truncate($req['title'], 35); ?></div>
                                                <small class="text-muted">oleh <?php echo $req['author']; ?></small>
                                                <?php if ($req['publisher_name']): ?>
                                                    <br><small class="text-muted">Penerbit: <?php echo $req['publisher_name']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?php echo $req['user_name']; ?></div>
                                                <small class="text-muted"><?php echo $req['user_code']; ?> | <?php echo getRoleBadge($req['role']); ?></small>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo $req['university_code']; ?></span></td>
                                            <td>
                                                <?php
                                                $priorityColors = [
                                                    'urgent' => 'danger',
                                                    'high' => 'warning',
                                                    'medium' => 'info',
                                                    'low' => 'secondary'
                                                ];
                                                $priorityLabels = [
                                                    'urgent' => 'Mendesak',
                                                    'high' => 'Tinggi',
                                                    'medium' => 'Sedang',
                                                    'low' => 'Rendah'
                                                ];
                                                $color = $priorityColors[$req['priority']] ?? 'secondary';
                                                $label = $priorityLabels[$req['priority']] ?? ucfirst($req['priority']);
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                            </td>
                                            <td><?php echo $req['estimated_price'] ? formatCurrency($req['estimated_price']) : '-'; ?></td>
                                            <td><?php echo formatDate($req['created_at']); ?></td>
                                            <td>
                                                <?php if ($req['payment_status'] === 'paid'): ?>
                                                    <span class="badge bg-success" title="Dibayar: <?php echo formatDate($req['payment_date']); ?>">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Lunas
                                                    </span>
                                                    <br><small class="text-muted"><?php echo $req['payment_method']; ?></small>
                                                <?php elseif ($req['payment_status'] === 'unpaid'): ?>
                                                    <span class="badge bg-warning text-dark" title="Invoice: <?php echo $req['invoice_number']; ?>">
                                                        <i class="bi bi-clock-fill me-1"></i>Belum Bayar
                                                    </span>
                                                    <br><small class="text-muted"><?php echo formatCurrency($req['payment_amount']); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo getStatusBadge($req['status']); ?></td>
                                            <td class="table-actions">
                                                <a href="view.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($req['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" title="Setujui"
                                                            onclick="showActionModal(<?php echo $req['id']; ?>, 'approve', '<?php echo addslashes($req['title']); ?>')">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Tolak"
                                                            onclick="showActionModal(<?php echo $req['id']; ?>, 'reject', '<?php echo addslashes($req['title']); ?>')">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                <?php elseif ($req['status'] == 'approved'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary" title="Tandai Dipesan"
                                                            onclick="showActionModal(<?php echo $req['id']; ?>, 'ordered', '<?php echo addslashes($req['title']); ?>')">
                                                        <i class="bi bi-cart"></i>
                                                    </button>
                                                <?php elseif ($req['status'] == 'ordered'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" title="Tandai Diterima"
                                                            onclick="showActionModal(<?php echo $req['id']; ?>, 'received', '<?php echo addslashes($req['title']); ?>')">
                                                        <i class="bi bi-box-seam"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php echo renderPagination($pagination, 'index.php'); ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>Tidak ada permintaan buku</h4>
                            <p>Permintaan buku dari pengguna akan muncul di sini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="actionModalTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="requestId">
                        <input type="hidden" name="action" id="actionType">

                        <p id="actionMessage"></p>

                        <div class="mb-3">
                            <label class="form-label">Catatan/Alasan</label>
                            <textarea class="form-control" name="admin_notes" rows="3" id="adminNotes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="actionBtn">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));

        function showActionModal(requestId, action, bookTitle) {
            document.getElementById('requestId').value = requestId;
            document.getElementById('actionType').value = action;

            const titles = {
                'approve': 'Setujui Permintaan',
                'reject': 'Tolak Permintaan',
                'ordered': 'Tandai Sudah Dipesan',
                'received': 'Tandai Sudah Diterima'
            };

            const messages = {
                'approve': `Setujui permintaan untuk "${bookTitle}"?`,
                'reject': `Tolak permintaan untuk "${bookTitle}"? Mohon berikan alasan.`,
                'ordered': `Tandai "${bookTitle}" sudah dipesan dari penerbit?`,
                'received': `Tandai "${bookTitle}" sudah diterima? Jangan lupa tambahkan ke inventori.`
            };

            document.getElementById('actionModalTitle').textContent = titles[action];
            document.getElementById('actionMessage').textContent = messages[action];
            document.getElementById('adminNotes').required = (action === 'reject');

            actionModal.show();
        }
    </script>
</body>
</html>
