<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle return book
if (isset($_POST['return']) && isset($_POST['borrowing_id'])) {
    $borrowingId = (int)$_POST['borrowing_id'];

    // Get borrowing details
    $borrowing = $db->fetchOne("SELECT * FROM borrowings WHERE id = ?", [$borrowingId]);

    if ($borrowing && $borrowing['status'] != 'returned') {
        $returnDate = date('Y-m-d');

        // Update borrowing status
        $db->execute(
            "UPDATE borrowings SET status = 'returned', return_date = ?, returned_to = ? WHERE id = ?",
            [$returnDate, $currentUser['id'], $borrowingId]
        );

        // Update book available quantity
        $db->execute(
            "UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?",
            [$borrowing['book_id']]
        );

        // Calculate fine if overdue
        if (strtotime($returnDate) > strtotime($borrowing['due_date'])) {
            $fine = calculateFine($borrowing['due_date'], $returnDate);

            if ($fine['amount'] > 0) {
                $db->execute(
                    "INSERT INTO fines (borrowing_id, user_id, amount, days_late, status)
                     VALUES (?, ?, ?, ?, 'unpaid')",
                    [$borrowingId, $borrowing['user_id'], $fine['amount'], $fine['days_late']]
                );

                // Notify user about fine
                createNotification(
                    $borrowing['user_id'],
                    'Denda Dikenakan',
                    'Anda dikenakan denda keterlambatan sebesar ' . formatCurrency($fine['amount']) . ' untuk ' . $fine['days_late'] . ' hari terlambat.',
                    'warning'
                );
            }
        }

        // Notify user
        createNotification(
            $borrowing['user_id'],
            'Buku Dikembalikan',
            'Buku Anda telah berhasil dikembalikan.',
            'success'
        );

        logActivity($currentUser['id'], 'return_book', 'borrowing', 'Returned borrowing ID: ' . $borrowingId);

        setFlashMessage('success', 'Buku berhasil dikembalikan');
    }

    redirect(SITE_URL . '/admin/borrowing/index.php');
}

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$university = $_GET['university'] ?? '';

// Build query
$where = ['1=1'];
$params = [];

if (!empty($status)) {
    $where[] = "b.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.user_code LIKE ? OR bk.title LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($university)) {
    $where[] = "u.university_id = ?";
    $params[] = $university;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Update overdue status
$db->execute("UPDATE borrowings SET status = 'overdue' WHERE status = 'borrowed' AND due_date < CURDATE()");

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalBorrowings = $db->fetchOne("SELECT COUNT(*) as count FROM borrowings b
                                   JOIN users u ON b.user_id = u.id
                                   JOIN books bk ON b.book_id = bk.id
                                   $whereClause", $params)['count'];
$pagination = paginate($totalBorrowings, $page);

// Get borrowings
$query = "SELECT b.*, u.name as user_name, u.user_code, u.email, u.phone, u.role,
          bk.title as book_title, bk.author as book_author, bk.isbn,
          uni.name as university_name, uni.code as university_code,
          DATEDIFF(CURDATE(), b.due_date) as days_overdue
          FROM borrowings b
          JOIN users u ON b.user_id = u.id
          JOIN books bk ON b.book_id = bk.id
          JOIN universities uni ON u.university_id = uni.id
          $whereClause
          ORDER BY b.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$borrowings = $db->fetchAll($query, $params);

// Get universities
$universities = $db->fetchAll("SELECT id, code, name FROM universities ORDER BY name");

$pageTitle = 'Manajemen Peminjaman - ' . SITE_NAME;
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
                    <h1>Manajemen Peminjaman</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Peminjaman</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Peminjaman Baru
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cari</label>
                                <input type="text" class="form-control" name="search" placeholder="Nama user, judul buku..." value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Universitas</label>
                                <select class="form-select" name="university">
                                    <option value="">Semua Universitas</option>
                                    <?php foreach ($universities as $uni): ?>
                                        <option value="<?php echo $uni['id']; ?>" <?php echo $university == $uni['id'] ? 'selected' : ''; ?>>
                                            <?php echo $uni['code']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="borrowed" <?php echo $status == 'borrowed' ? 'selected' : ''; ?>>Dipinjam</option>
                                    <option value="overdue" <?php echo $status == 'overdue' ? 'selected' : ''; ?>>Terlambat</option>
                                    <option value="returned" <?php echo $status == 'returned' ? 'selected' : ''; ?>>Dikembalikan</option>
                                    <option value="lost" <?php echo $status == 'lost' ? 'selected' : ''; ?>>Hilang</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Borrowings Table -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="bi bi-arrow-left-right me-2"></i>Daftar Peminjaman (<?php echo formatNumber($totalBorrowings); ?> data)</h6>
                </div>
                <div class="card-body">
                    <?php if ($borrowings): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pengguna</th>
                                        <th>Buku</th>
                                        <th>Universitas</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Tgl Jatuh Tempo</th>
                                        <th>Tgl Kembali</th>
                                        <th>Status</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowings as $borrow): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo $borrow['user_name']; ?></div>
                                                <small class="text-muted"><?php echo $borrow['user_code']; ?> | <?php echo getRoleBadge($borrow['role']); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo truncate($borrow['book_title'], 35); ?></div>
                                                <small class="text-muted"><?php echo $borrow['book_author']; ?></small>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo $borrow['university_code']; ?></span></td>
                                            <td><?php echo formatDate($borrow['borrow_date']); ?></td>
                                            <td>
                                                <?php echo formatDate($borrow['due_date']); ?>
                                                <?php if ($borrow['status'] == 'overdue'): ?>
                                                    <br><small class="text-danger"><?php echo abs($borrow['days_overdue']); ?> hari terlambat</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $borrow['return_date'] ? formatDate($borrow['return_date']) : '-'; ?></td>
                                            <td><?php echo getStatusBadge($borrow['status']); ?></td>
                                            <td class="table-actions">
                                                <a href="view.php?id=<?php echo $borrow['id']; ?>" class="btn btn-sm btn-info" title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($borrow['status'] != 'returned'): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tandai buku ini sudah dikembalikan?')">
                                                        <input type="hidden" name="borrowing_id" value="<?php echo $borrow['id']; ?>">
                                                        <button type="submit" name="return" class="btn btn-sm btn-success" title="Kembalikan">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="edit.php?id=<?php echo $borrow['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
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
                            <h4>Tidak ada data peminjaman</h4>
                            <p>Mulai dengan membuat transaksi peminjaman baru</p>
                            <a href="add.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Peminjaman Baru
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
