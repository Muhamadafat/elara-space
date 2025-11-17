<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$borrowingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$borrowingId) {
    redirect(SITE_URL . '/admin/borrowing/index.php', 'Invalid borrowing ID', 'error');
}

// Get borrowing details
$borrowing = $db->fetchOne("
    SELECT b.*,
           bk.title, bk.author, bk.isbn, bk.category, bk.cover_image,
           u.name as user_name, u.user_code, u.email as user_email, u.phone as user_phone,
           uni.name as university_name, uni.code as university_code,
           creator.name as created_by_name,
           returner.name as returned_to_name,
           DATEDIFF(CURDATE(), b.due_date) as days_overdue,
           (SELECT SUM(amount) FROM fines WHERE borrowing_id = b.id) as total_fines,
           (SELECT SUM(amount) FROM fines WHERE borrowing_id = b.id AND status = 'unpaid') as unpaid_fines
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    JOIN users u ON b.user_id = u.id
    JOIN universities uni ON u.university_id = uni.id
    LEFT JOIN users creator ON b.created_by = creator.id
    LEFT JOIN users returner ON b.returned_to = returner.id
    WHERE b.id = ?
", [$borrowingId]);

if (!$borrowing) {
    redirect(SITE_URL . '/admin/borrowing/index.php', 'Borrowing not found', 'error');
}

// Get fine details if any
$fines = $db->fetchAll("
    SELECT * FROM fines
    WHERE borrowing_id = ?
    ORDER BY created_at DESC
", [$borrowingId]);

$pageTitle = 'Borrowing Details - ' . SITE_NAME;
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
                    <h1 class="h2 fw-bold mb-1">Detail Peminjaman #<?php echo $borrowingId; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php">Borrowings</a></li>
                            <li class="breadcrumb-item active">Detail #<?php echo $borrowingId; ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <!-- Borrowing Info -->
                <div class="col-lg-8">
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Peminjaman</h5>

                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?php echo getStatusBadge($borrowing['status']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tanggal Pinjam</div>
                            <div class="info-value"><?php echo indonesianDate($borrowing['borrow_date']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Jatuh Tempo</div>
                            <div class="info-value">
                                <?php echo indonesianDate($borrowing['due_date']); ?>
                                <?php if ($borrowing['status'] == 'borrowed' && $borrowing['days_overdue'] > 0): ?>
                                    <span class="badge bg-danger ms-2">Terlambat <?php echo $borrowing['days_overdue']; ?> hari</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($borrowing['return_date']): ?>
                        <div class="info-row">
                            <div class="info-label">Tanggal Kembali</div>
                            <div class="info-value"><?php echo indonesianDate($borrowing['return_date']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Durasi Peminjaman</div>
                            <div class="info-value">
                                <?php
                                $start = new DateTime($borrowing['borrow_date']);
                                $end = $borrowing['return_date'] ? new DateTime($borrowing['return_date']) : new DateTime();
                                echo $start->diff($end)->days;
                                ?> hari
                            </div>
                        </div>

                        <?php if ($borrowing['extended_count'] > 0): ?>
                        <div class="info-row">
                            <div class="info-label">Perpanjangan</div>
                            <div class="info-value"><?php echo $borrowing['extended_count']; ?>x</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($borrowing['notes']): ?>
                        <div class="info-row">
                            <div class="info-label">Catatan</div>
                            <div class="info-value"><?php echo htmlspecialchars($borrowing['notes']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Dibuat oleh</div>
                            <div class="info-value"><?php echo htmlspecialchars($borrowing['created_by_name'] ?? 'System'); ?></div>
                        </div>

                        <?php if ($borrowing['returned_to_name']): ?>
                        <div class="info-row">
                            <div class="info-label">Dikembalikan ke</div>
                            <div class="info-value"><?php echo htmlspecialchars($borrowing['returned_to_name']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Book Info -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-book me-2 text-primary"></i>Informasi Buku</h5>

                        <div class="info-row">
                            <div class="info-label">Judul</div>
                            <div class="info-value">
                                <a href="<?php echo SITE_URL; ?>/admin/books/view.php?id=<?php echo $borrowing['book_id']; ?>">
                                    <?php echo htmlspecialchars($borrowing['title']); ?>
                                </a>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Penulis</div>
                            <div class="info-value"><?php echo htmlspecialchars($borrowing['author']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">ISBN</div>
                            <div class="info-value"><code><?php echo htmlspecialchars($borrowing['isbn']); ?></code></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Kategori</div>
                            <div class="info-value">
                                <span class="badge bg-info"><?php echo htmlspecialchars($borrowing['category'] ?? 'Umum'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Fines -->
                    <?php if (!empty($fines)): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-cash me-2 text-danger"></i>Denda</h5>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Hari Terlambat</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fines as $fine): ?>
                                    <tr>
                                        <td><?php echo indonesianDate($fine['created_at']); ?></td>
                                        <td class="fw-bold"><?php echo formatCurrency($fine['amount']); ?></td>
                                        <td><?php echo $fine['days_late']; ?> hari</td>
                                        <td>
                                            <?php if ($fine['status'] == 'paid'): ?>
                                                <span class="badge bg-success">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Belum Dibayar</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Belum Dibayar:</th>
                                        <th class="text-danger"><?php echo formatCurrency($borrowing['unpaid_fines'] ?? 0); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- User Info -->
                <div class="col-lg-4">
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>Informasi Peminjam</h5>

                        <div class="info-row">
                            <div class="info-label">Nama</div>
                            <div class="info-value">
                                <a href="<?php echo SITE_URL; ?>/admin/users/view.php?id=<?php echo $borrowing['user_id']; ?>">
                                    <?php echo htmlspecialchars($borrowing['user_name']); ?>
                                </a>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Kode User</div>
                            <div class="info-value"><code><?php echo htmlspecialchars($borrowing['user_code']); ?></code></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo $borrowing['user_email']; ?>">
                                    <?php echo htmlspecialchars($borrowing['user_email']); ?>
                                </a>
                            </div>
                        </div>

                        <?php if ($borrowing['user_phone']): ?>
                        <div class="info-row">
                            <div class="info-label">Telepon</div>
                            <div class="info-value">
                                <a href="tel:<?php echo $borrowing['user_phone']; ?>">
                                    <?php echo htmlspecialchars($borrowing['user_phone']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Universitas</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($borrowing['university_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($borrowing['university_code']); ?>)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if (in_array($borrowing['status'], ['borrowed', 'overdue'])): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-primary"></i>Aksi</h5>

                        <form method="POST" action="<?php echo SITE_URL; ?>/admin/borrowing/index.php" onsubmit="return confirm('Apakah Anda yakin ingin menandai buku ini sebagai dikembalikan?')">
                            <input type="hidden" name="return" value="1">
                            <input type="hidden" name="borrowing_id" value="<?php echo $borrowingId; ?>">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-box-arrow-in-left me-2"></i>Tandai Dikembalikan
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
