<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $adminNotes = $_POST['admin_notes'] ?? '';

    try {
        // Update order status
        if ($newStatus === 'ready_pickup') {
            $db->query("
                UPDATE marketplace_orders
                SET status = ?, admin_notes = ?, ready_at = NOW()
                WHERE id = ?
            ", [$newStatus, $adminNotes, $orderId]);

            // Get order details for notification
            $order = $db->fetchOne("SELECT * FROM marketplace_orders WHERE id = ?", [$orderId]);

            // Send notification to user
            $db->query("
                INSERT INTO notifications (user_id, title, message, type, link)
                VALUES (?, ?, ?, 'success', '/user/marketplace.php')
            ", [
                $order['user_id'],
                'Buku Siap Diambil!',
                "Pesanan buku '{$order['title']}' sudah siap diambil di perpustakaan."
            ]);
        } else {
            $db->query("
                UPDATE marketplace_orders
                SET status = ?, admin_notes = ?
                WHERE id = ?
            ", [$newStatus, $adminNotes, $orderId]);
        }

        redirect(SITE_URL . '/admin/marketplace/orders.php', 'Status pesanan berhasil diupdate!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/marketplace/orders.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle mark as picked up
if (isset($_POST['mark_picked_up'])) {
    $orderId = (int)$_POST['order_id'];

    try {
        $db->query("
            UPDATE marketplace_orders
            SET status = 'picked_up', picked_up_at = NOW()
            WHERE id = ?
        ", [$orderId]);

        redirect(SITE_URL . '/admin/marketplace/orders.php', 'Pesanan berhasil ditandai sebagai diambil!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/marketplace/orders.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get orders with filters
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $whereClause .= " AND o.status = ?";
    $params[] = $statusFilter;
}

try {
    $orders = $db->fetchAll("
        SELECT o.*, u.name AS user_name, u.user_code, u.email, uni.name AS university
        FROM marketplace_orders o
        JOIN users u ON o.user_id = u.id
        JOIN universities uni ON u.university_id = uni.id
        $whereClause
        ORDER BY
            CASE o.status
                WHEN 'pending' THEN 1
                WHEN 'processing' THEN 2
                WHEN 'ready_pickup' THEN 3
                WHEN 'picked_up' THEN 4
                WHEN 'cancelled' THEN 5
            END,
            o.created_at DESC
    ", $params);
} catch (Exception $e) {
    $orders = [];
    $error = $e->getMessage();
}

// Get statistics
try {
    $stats = $db->fetchOne("
        SELECT
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'ready_pickup' THEN 1 ELSE 0 END) as ready_pickup,
            SUM(CASE WHEN status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
            SUM(commission_amount) as total_commission
        FROM marketplace_orders
    ");
} catch (Exception $e) {
    $stats = [
        'total_orders' => 0,
        'pending' => 0,
        'processing' => 0,
        'ready_pickup' => 0,
        'picked_up' => 0,
        'total_commission' => 0
    ];
}

$pageTitle = 'Marketplace Orders - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">Marketplace Orders</h1>
                    <p class="text-muted mb-0">Kelola pesanan dari marketplace</p>
                </div>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Total Orders</h6>
                            <h3 class="fw-bold"><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Pending</h6>
                            <h3 class="fw-bold"><?php echo $stats['pending']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Processing</h6>
                            <h3 class="fw-bold"><?php echo $stats['processing']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Ready</h6>
                            <h3 class="fw-bold"><?php echo $stats['ready_pickup']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Picked Up</h6>
                            <h3 class="fw-bold"><?php echo $stats['picked_up']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Commission</h6>
                            <h6 class="fw-bold"><?php echo formatCurrency($stats['total_commission']); ?></h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="ready_pickup" <?php echo $statusFilter === 'ready_pickup' ? 'selected' : ''; ?>>Ready Pickup</option>
                                <option value="picked_up" <?php echo $statusFilter === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Buku</th>
                                    <th>User</th>
                                    <th>Partner Store</th>
                                    <th>Harga</th>
                                    <th>Komisi</th>
                                    <th>Status</th>
                                    <th>Estimasi Siap</th>
                                    <th>Order Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">Belum ada pesanan</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo $order['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['title']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($order['user_name']); ?><br>
                                                <small class="text-muted"><?php echo $order['user_code']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['partner_store']); ?></td>
                                            <td><?php echo formatCurrency($order['price']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo formatCurrency($order['commission_amount']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'ready_pickup' => 'success',
                                                    'picked_up' => 'secondary',
                                                    'cancelled' => 'danger'
                                                ];
                                                $badge = $statusBadges[$order['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>">
                                                    <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['estimated_ready']): ?>
                                                    <?php echo indonesianDate($order['estimated_ready']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo indonesianDate($order['created_at']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['title']); ?>', '<?php echo $order['status']; ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($order['status'] === 'ready_pickup'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="markPickedUp(<?php echo $order['id']; ?>)">
                                                        <i class="bi bi-check-circle"></i> Diambil
                                                    </button>
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
        function updateStatus(orderId, title, currentStatus) {
            Swal.fire({
                title: 'Update Status',
                html: `
                    <div class="text-start">
                        <p><strong>Buku:</strong> ${title}</p>
                        <hr>
                        <form id="statusForm">
                            <div class="mb-3">
                                <label class="form-label">Status Baru</label>
                                <select class="form-select" id="newStatus" name="status">
                                    <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="processing" ${currentStatus === 'processing' ? 'selected' : ''}>Processing</option>
                                    <option value="ready_pickup" ${currentStatus === 'ready_pickup' ? 'selected' : ''}>Ready for Pickup</option>
                                    <option value="picked_up" ${currentStatus === 'picked_up' ? 'selected' : ''}>Picked Up</option>
                                    <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan Admin (Opsional)</label>
                                <textarea class="form-control" id="adminNotes" name="admin_notes" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return {
                        status: document.getElementById('newStatus').value,
                        admin_notes: document.getElementById('adminNotes').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="order_id" value="${orderId}">
                        <input type="hidden" name="status" value="${result.value.status}">
                        <input type="hidden" name="admin_notes" value="${result.value.admin_notes}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function markPickedUp(orderId) {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Tandai pesanan ini sebagai sudah diambil?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sudah Diambil',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="mark_picked_up" value="1">
                        <input type="hidden" name="order_id" value="${orderId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
