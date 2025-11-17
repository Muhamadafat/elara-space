<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Filters
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR al.details LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($action)) {
    $where[] = "al.action = ?";
    $params[] = $action;
}

if (!empty($userId)) {
    $where[] = "al.user_id = ?";
    $params[] = $userId;
}

if (!empty($startDate)) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $endDate;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalLogs = $db->fetchOne("SELECT COUNT(*) as count FROM activity_logs al
                             JOIN users u ON al.user_id = u.id
                             $whereClause", $params)['count'];
$pagination = paginate($totalLogs, $page);

// Get activity logs
$query = "SELECT al.*, u.name as user_name, u.user_code, u.role
          FROM activity_logs al
          JOIN users u ON al.user_id = u.id
          $whereClause
          ORDER BY al.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$logs = $db->fetchAll($query, $params);

// Get distinct actions for filter
$actions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");

// Get users for filter
$users = $db->fetchAll("SELECT id, name, user_code FROM users WHERE role = 'admin' ORDER BY name");

// Statistics
$stats = [
    'total_logs' => $db->fetchOne("SELECT COUNT(*) as count FROM activity_logs")['count'],
    'today_logs' => $db->fetchOne("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()")['count'],
    'unique_users' => $db->fetchOne("SELECT COUNT(DISTINCT user_id) as count FROM activity_logs")['count']
];

$pageTitle = 'Activity Logs - ' . SITE_NAME;
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
        .log-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border-left: 4px solid #E5E7EB;
        }
        .log-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            transform: translateX(5px);
        }
        .log-card.login {
            border-left-color: #10B981;
        }
        .log-card.logout {
            border-left-color: #6B7280;
        }
        .log-card.add {
            border-left-color: #3B82F6;
        }
        .log-card.edit {
            border-left-color: #F59E0B;
        }
        .log-card.delete {
            border-left-color: #EF4444;
        }
        .log-card.approve, .log-card.mark_paid {
            border-left-color: #10B981;
        }
        .log-card.reject {
            border-left-color: #EF4444;
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
        .action-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .log-time {
            font-size: 0.85rem;
            color: #6B7280;
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
                    <h1 class="h2 fw-bold mb-1">Activity Logs</h1>
                    <p class="text-muted mb-0">Monitor semua aktivitas sistem perpustakaan</p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Aktivitas</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['total_logs']); ?></h3>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="bi bi-activity"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Hari Ini</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['today_logs']); ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Unique Users</p>
                                <h3 class="fw-bold mb-0"><?php echo formatNumber($stats['unique_users']); ?></h3>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Cari user atau detail..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="action">
                                <option value="">Semua Aksi</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $action == $act['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($act['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="user_id">
                                <option value="">Semua Admin</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $userId == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" placeholder="Dari">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" placeholder="Sampai">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Logs List -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul me-2 text-primary"></i>
                    Riwayat Aktivitas <span class="badge bg-primary"><?php echo formatNumber($totalLogs); ?></span>
                </h5>
            </div>

            <?php if (!empty($logs)): ?>
                <div class="mb-4">
                    <?php foreach ($logs as $log): ?>
                        <?php
                        // Determine action color
                        $actionColors = [
                            'login' => 'success',
                            'logout' => 'secondary',
                            'add_book' => 'primary',
                            'edit_book' => 'warning',
                            'delete_book' => 'danger',
                            'add_user' => 'primary',
                            'edit_user' => 'warning',
                            'delete_user' => 'danger',
                            'approve_request' => 'success',
                            'reject_request' => 'danger',
                            'mark_fine_paid' => 'success',
                            'add_borrowing' => 'info',
                            'return_book' => 'success'
                        ];

                        // Extract main action type (login, add, edit, delete, etc.)
                        $mainAction = 'default';
                        foreach (['login', 'logout', 'add', 'edit', 'delete', 'approve', 'reject', 'mark_paid', 'return'] as $type) {
                            if (strpos($log['action'], $type) !== false) {
                                $mainAction = $type;
                                break;
                            }
                        }

                        $actionColor = $actionColors[$log['action']] ?? 'secondary';
                        ?>
                        <div class="log-card <?php echo $mainAction; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    <div class="text-<?php echo $actionColor; ?>" style="font-size: 1.5rem;">
                                        <?php
                                        $icons = [
                                            'login' => 'box-arrow-in-right',
                                            'logout' => 'box-arrow-left',
                                            'add' => 'plus-circle',
                                            'edit' => 'pencil-square',
                                            'delete' => 'trash',
                                            'approve' => 'check-circle',
                                            'reject' => 'x-circle',
                                            'mark_paid' => 'cash-coin',
                                            'return' => 'arrow-return-left'
                                        ];
                                        $icon = $icons[$mainAction] ?? 'circle';
                                        ?>
                                        <i class="bi bi-<?php echo $icon; ?>"></i>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <span class="action-badge bg-<?php echo $actionColor; ?> text-white">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <div class="fw-bold"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                    <small class="text-muted">
                                        <code><?php echo htmlspecialchars($log['user_code']); ?></code>
                                    </small>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">Target:</small>
                                    <div class="small fw-bold"><?php echo htmlspecialchars($log['target_type'] ?? '-'); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Detail:</small>
                                    <div class="small"><?php echo htmlspecialchars(truncate($log['details'] ?? '-', 60)); ?></div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="log-time">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo formatDateTime($log['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php echo renderPagination($pagination, 'index.php'); ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Tidak ada aktivitas</h4>
                    <p class="text-muted">Belum ada log aktivitas yang tercatat</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
