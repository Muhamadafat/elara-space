<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();

// Get filters
$moduleFilter = $_GET['module'] ?? 'all';
$userFilter = $_GET['user'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$whereClause = "WHERE 1=1";
$params = [];

if ($moduleFilter !== 'all') {
    $whereClause .= " AND al.module = ?";
    $params[] = $moduleFilter;
}

if (!empty($userFilter)) {
    $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.user_code LIKE ?)";
    $searchParam = "%$userFilter%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($dateFrom)) {
    $whereClause .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereClause .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

// Get total count for pagination
try {
    $totalLogs = $db->fetchOne("
        SELECT COUNT(*) as total
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
    ", $params)['total'];
} catch (Exception $e) {
    $totalLogs = 0;
}

$totalPages = ceil($totalLogs / $perPage);

// Get logs
try {
    $logs = $db->fetchAll("
        SELECT al.*, u.name AS user_name, u.email, u.user_code, u.role
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$perPage, $offset]));
} catch (Exception $e) {
    $logs = [];
    $error = $e->getMessage();
}

// Get statistics
try {
    $stats = $db->fetchOne("
        SELECT
            COUNT(*) as total_logs,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT module) as unique_modules,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs
        FROM activity_logs
    ");
} catch (Exception $e) {
    $stats = [
        'total_logs' => 0,
        'unique_users' => 0,
        'unique_modules' => 0,
        'today_logs' => 0
    ];
}

// Get available modules
try {
    $modules = $db->fetchAll("
        SELECT DISTINCT module
        FROM activity_logs
        ORDER BY module
    ");
} catch (Exception $e) {
    $modules = [];
}

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .log-entry {
            border-left: 3px solid #3B82F6;
            background: #F9FAFB;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 8px;
        }
        .log-entry:hover {
            background: #F3F4F6;
        }
        .module-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
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
                    <h1 class="h2 fw-bold mb-1">Activity Logs</h1>
                    <p class="text-muted mb-0">Monitor semua aktivitas di sistem</p>
                </div>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Total Logs</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_logs']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Unique Users</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['unique_users']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Modules</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['unique_modules']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="text-white-50">Today's Logs</h6>
                            <h3 class="fw-bold"><?php echo number_format($stats['today_logs']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $moduleFilter === 'all' ? 'selected' : ''; ?>>All Modules</option>
                                <?php foreach ($modules as $mod): ?>
                                    <option value="<?php echo htmlspecialchars($mod['module']); ?>" <?php echo $moduleFilter === $mod['module'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($mod['module'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <input type="text" name="user" class="form-control" placeholder="Nama/Email/User Code" value="<?php echo htmlspecialchars($userFilter); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary me-2" type="submit">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/system/logs.php" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-list-ul me-2"></i>Activity Logs
                        <span class="badge bg-primary ms-2"><?php echo number_format($totalLogs); ?> entries</span>
                    </h5>

                    <?php if (empty($logs)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Tidak ada log ditemukan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="log-entry">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-secondary module-badge me-2"><?php echo strtoupper($log['module']); ?></span>
                                            <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($log['description'] ?? '-'); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>
                                            <?php if ($log['user_name']): ?>
                                                <?php echo htmlspecialchars($log['user_name']); ?> (<?php echo htmlspecialchars($log['user_code']); ?>)
                                            <?php else: ?>
                                                System
                                            <?php endif; ?>
                                            •
                                            <i class="bi bi-globe me-1"></i><?php echo htmlspecialchars($log['ip_address'] ?? 'unknown'); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&module=<?php echo $moduleFilter; ?>&user=<?php echo urlencode($userFilter); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&module=<?php echo $moduleFilter; ?>&user=<?php echo urlencode($userFilter); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&module=<?php echo $moduleFilter; ?>&user=<?php echo urlencode($userFilter); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
</body>
</html>
