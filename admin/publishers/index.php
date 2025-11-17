<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $publisherId = (int)$_GET['id'];

    // Check if publisher has books
    $hasBooks = $db->fetchOne("SELECT COUNT(*) as count FROM books WHERE publisher_id = ?", [$publisherId]);

    if ($hasBooks['count'] > 0) {
        setFlashMessage('error', 'Cannot delete publisher with existing books. Please reassign books first.');
    } else {
        $publisher = $db->fetchOne("SELECT name FROM publishers WHERE id = ?", [$publisherId]);

        if ($db->execute("DELETE FROM publishers WHERE id = ?", [$publisherId])) {
            logActivity($currentUser['id'], 'delete_publisher', 'publishers', 'Deleted publisher: ' . $publisher['name']);
            setFlashMessage('success', 'Publisher deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete publisher');
        }
    }

    redirect(SITE_URL . '/admin/publishers/index.php');
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status)) {
    $where[] = "partnership_status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalPublishers = $db->fetchOne("SELECT COUNT(*) as count FROM publishers $whereClause", $params)['count'];
$pagination = paginate($totalPublishers, $page);

// Get publishers
$query = "SELECT p.*,
          (SELECT COUNT(*) FROM books WHERE publisher_id = p.id) as book_count
          FROM publishers p
          $whereClause
          ORDER BY p.name ASC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$publishers = $db->fetchAll($query, $params);

$pageTitle = 'Publishers Management - ' . SITE_NAME;
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Publishers Management</h1>
                    <p class="text-muted mb-0">Kelola penerbit dan mitra toko buku</p>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/publishers/add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Publisher
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama, kontak, email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Publishers List -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($publishers)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Publisher</th>
                                        <th>Kontak</th>
                                        <th>Email/Telepon</th>
                                        <th>Status</th>
                                        <th>Komisi</th>
                                        <th>Jumlah Buku</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($publisher['name']); ?></div>
                                                <?php if ($publisher['website']): ?>
                                                    <small><a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank" class="text-decoration-none">
                                                        <i class="bi bi-globe me-1"></i>Website
                                                    </a></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($publisher['contact_person'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($publisher['email']): ?>
                                                    <div><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($publisher['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($publisher['phone']): ?>
                                                    <div><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($publisher['phone']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'active' => 'success',
                                                    'pending' => 'warning',
                                                    'inactive' => 'secondary'
                                                ];
                                                $color = $statusColors[$publisher['partnership_status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo strtoupper($publisher['partnership_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($publisher['commission_rate'], 2); ?>%</td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $publisher['book_count']; ?> buku</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo SITE_URL; ?>/admin/publishers/edit.php?id=<?php echo $publisher['id']; ?>"
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $publisher['id']; ?>"
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Yakin ingin menghapus publisher ini?')"
                                                       title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada publisher</p>
                            <a href="<?php echo SITE_URL; ?>/admin/publishers/add.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Publisher Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
