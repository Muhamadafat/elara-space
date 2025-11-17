<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Handle return action
if (isset($_POST['return_book'])) {
    $borrowingId = (int)$_POST['borrowing_id'];

    // Get borrowing details
    $borrowing = $db->fetchOne("SELECT * FROM borrowings WHERE id = ? AND user_id = ?", [$borrowingId, $currentUser['id']]);

    if ($borrowing && in_array($borrowing['status'], ['borrowed', 'overdue'])) {
        $returnDate = date('Y-m-d');

        // Update borrowing status
        $db->execute(
            "UPDATE borrowings SET status = 'returned', return_date = ? WHERE id = ?",
            [$returnDate, $borrowingId]
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
                    "INSERT INTO fines (borrowing_id, user_id, amount, days_late, status, created_at)
                     VALUES (?, ?, ?, ?, 'unpaid', NOW())",
                    [$borrowingId, $currentUser['id'], $fine['amount'], $fine['days_late']]
                );

                // Notify user about fine
                createNotification(
                    $currentUser['id'],
                    'Denda Keterlambatan',
                    'Anda memiliki denda keterlambatan sebesar ' . formatCurrency($fine['amount']) . ' untuk keterlambatan ' . $fine['days_late'] . ' hari.',
                    'warning'
                );

                setFlashMessage('warning', 'Buku berhasil dikembalikan. Anda memiliki denda keterlambatan sebesar ' . formatCurrency($fine['amount']));
            } else {
                setFlashMessage('success', 'Buku berhasil dikembalikan!');
            }
        } else {
            setFlashMessage('success', 'Buku berhasil dikembalikan tepat waktu!');
        }

        logActivity($currentUser['id'], 'return_book', 'borrowing', 'Returned borrowing ID: ' . $borrowingId);
    } else {
        setFlashMessage('error', 'Peminjaman tidak ditemukan atau sudah dikembalikan');
    }

    redirect(SITE_URL . '/user/borrowing.php');
}

// Handle extend borrowing
if (isset($_POST['extend_book'])) {
    $borrowingId = (int)$_POST['borrowing_id'];

    // Get borrowing details
    $borrowing = $db->fetchOne("SELECT * FROM borrowings WHERE id = ? AND user_id = ?", [$borrowingId, $currentUser['id']]);

    if ($borrowing && $borrowing['status'] == 'borrowed') {
        $maxExtensions = 2;
        $currentExtensions = $borrowing['extended_count'] ?? 0;

        if ($currentExtensions >= $maxExtensions) {
            setFlashMessage('error', 'Anda sudah mencapai batas maksimal perpanjangan (' . $maxExtensions . 'x)');
        } else {
            // Extend due date by 7 days
            $newDueDate = date('Y-m-d', strtotime($borrowing['due_date'] . ' +7 days'));
            $newExtendedCount = $currentExtensions + 1;

            $db->execute(
                "UPDATE borrowings SET due_date = ?, extended_count = ? WHERE id = ?",
                [$newDueDate, $newExtendedCount, $borrowingId]
            );

            // Notify user
            createNotification(
                $currentUser['id'],
                'Peminjaman Diperpanjang',
                'Peminjaman Anda berhasil diperpanjang hingga ' . indonesianDate($newDueDate),
                'success'
            );

            logActivity($currentUser['id'], 'extend_borrowing', 'borrowing', 'Extended borrowing ID: ' . $borrowingId);

            setFlashMessage('success', 'Peminjaman berhasil diperpanjang 7 hari! Jatuh tempo baru: ' . indonesianDate($newDueDate));
        }
    } else {
        setFlashMessage('error', 'Peminjaman tidak ditemukan atau tidak dapat diperpanjang');
    }

    redirect(SITE_URL . '/user/borrowing.php');
}

// Get borrowings from database or use dummy data
try {
    $borrowings = $db->fetchAll("
        SELECT b.*, bk.title, bk.author, bk.isbn, bk.category,
               DATEDIFF(CURDATE(), b.due_date) as days_overdue,
               (SELECT SUM(amount) FROM fines WHERE borrowing_id = b.id AND status = 'unpaid') as fine_amount
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ?
        ORDER BY b.borrow_date DESC
    ", [$currentUser['id']]);

    // Set fine_amount to 0 if null
    foreach ($borrowings as &$b) {
        $b['fine_amount'] = $b['fine_amount'] ?? 0;
        $b['extended_count'] = $b['extended_count'] ?? 0;
    }
} catch (Exception $e) {
    $borrowings = [];
}

// If no data, use dummy data
if (empty($borrowings)) {
    $borrowings = [
        [
            'id' => 1,
            'title' => 'Manajemen Strategis',
            'author' => 'Fred David',
            'isbn' => '978-979-061-123-4',
            'category' => 'Manajemen',
            'borrow_date' => date('Y-m-d', strtotime('-5 days')),
            'due_date' => date('Y-m-d', strtotime('+9 days')),
            'return_date' => null,
            'status' => 'borrowed',
            'fine_amount' => 0,
            'extended_count' => 0
        ],
        [
            'id' => 2,
            'title' => 'Akuntansi Biaya',
            'author' => 'William Carter',
            'isbn' => '978-979-061-456-7',
            'category' => 'Akuntansi',
            'borrow_date' => date('Y-m-d', strtotime('-3 days')),
            'due_date' => date('Y-m-d', strtotime('+11 days')),
            'return_date' => null,
            'status' => 'borrowed',
            'fine_amount' => 0,
            'extended_count' => 1
        ],
        [
            'id' => 3,
            'title' => 'Ekonomi Makro',
            'author' => 'Gregory Mankiw',
            'isbn' => '978-979-061-789-1',
            'category' => 'Ekonomi',
            'borrow_date' => date('Y-m-d', strtotime('-20 days')),
            'due_date' => date('Y-m-d', strtotime('-6 days')),
            'return_date' => null,
            'status' => 'overdue',
            'fine_amount' => 12000,
            'extended_count' => 2
        ],
        [
            'id' => 4,
            'title' => 'Pemasaran Modern',
            'author' => 'Philip Kotler',
            'isbn' => '978-979-061-321-8',
            'category' => 'Manajemen',
            'borrow_date' => date('Y-m-d', strtotime('-25 days')),
            'due_date' => date('Y-m-d', strtotime('-11 days')),
            'return_date' => date('Y-m-d', strtotime('-8 days')),
            'status' => 'returned',
            'fine_amount' => 6000,
            'extended_count' => 0
        ],
        [
            'id' => 5,
            'title' => 'Analisis Laporan Keuangan',
            'author' => 'Kasmir',
            'isbn' => '978-979-061-654-3',
            'category' => 'Akuntansi',
            'borrow_date' => date('Y-m-d', strtotime('-30 days')),
            'due_date' => date('Y-m-d', strtotime('-16 days')),
            'return_date' => date('Y-m-d', strtotime('-15 days')),
            'status' => 'returned',
            'fine_amount' => 2000,
            'extended_count' => 1
        ]
    ];
}

// Calculate statistics
$activeBorrowings = 0;
$overdueBorrowings = 0;
$totalFines = 0;

foreach ($borrowings as $borrowing) {
    if ($borrowing['status'] === 'borrowed') {
        $activeBorrowings++;
    }
    if ($borrowing['status'] === 'overdue') {
        $overdueBorrowings++;
        $activeBorrowings++;
    }
    if ($borrowing['fine_amount'] > 0) {
        $totalFines += $borrowing['fine_amount'];
    }
}

$pageTitle = 'Peminjaman Saya - ' . SITE_NAME;
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
        .borrowing-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .borrowing-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .status-borrowed { border-left-color: #3B82F6; }
        .status-overdue { border-left-color: #EF4444; }
        .status-returned { border-left-color: #10B981; }
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
                    <h1 class="h2 fw-bold mb-1">Peminjaman Saya</h1>
                    <p class="text-muted mb-0">Kelola semua peminjaman buku Anda</p>
                </div>
                <a href="<?php echo SITE_URL; ?>/user/books.php" class="btn btn-primary">
                    <i class="bi bi-book me-2"></i>Telusuri Buku
                </a>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stats-card status-borrowed">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $activeBorrowings; ?></h3>
                                <p class="text-muted mb-0 small">Sedang Dipinjam</p>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="bi bi-book"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card status-overdue">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $overdueBorrowings; ?></h3>
                                <p class="text-muted mb-0 small">Terlambat</p>
                            </div>
                            <div class="text-danger" style="font-size: 2rem;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card status-returned">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo formatCurrency($totalFines); ?></h3>
                                <p class="text-muted mb-0 small">Total Denda</p>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4" id="borrowingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button">
                        <i class="bi bi-list me-2"></i>Semua
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="borrowed-tab" data-bs-toggle="pill" data-bs-target="#borrowed" type="button">
                        <i class="bi bi-book me-2"></i>Sedang Dipinjam
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="overdue-tab" data-bs-toggle="pill" data-bs-target="#overdue" type="button">
                        <i class="bi bi-exclamation-triangle me-2"></i>Terlambat
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="returned-tab" data-bs-toggle="pill" data-bs-target="#returned" type="button">
                        <i class="bi bi-check-circle me-2"></i>Dikembalikan
                    </button>
                </li>
            </ul>

            <!-- Borrowings List -->
            <div class="tab-content" id="borrowingTabContent">
                <div class="tab-pane fade show active" id="all">
                    <?php foreach ($borrowings as $borrowing): ?>
                        <div class="borrowing-card status-<?php echo $borrowing['status']; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($borrowing['title']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($borrowing['author']); ?>
                                        <span class="ms-3"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($borrowing['category']); ?></span>
                                        <span class="ms-3"><i class="bi bi-upc me-1"></i><?php echo $borrowing['isbn']; ?></span>
                                    </p>
                                    <div class="d-flex gap-3 small">
                                        <span><strong>Dipinjam:</strong> <?php echo indonesianDate($borrowing['borrow_date']); ?></span>
                                        <span><strong>Jatuh Tempo:</strong> <?php echo indonesianDate($borrowing['due_date']); ?></span>
                                        <?php if ($borrowing['return_date']): ?>
                                            <span><strong>Dikembalikan:</strong> <?php echo indonesianDate($borrowing['return_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <?php echo getStatusBadge($borrowing['status']); ?>
                                    <?php if ($borrowing['fine_amount'] > 0): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning text-dark">Denda: <?php echo formatCurrency($borrowing['fine_amount']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                        <div class="mt-2">
                                            <?php if ($borrowing['status'] === 'borrowed' && ($borrowing['extended_count'] ?? 0) < 2): ?>
                                                <button class="btn btn-sm btn-warning me-2" onclick="extendBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>', <?php echo ($borrowing['extended_count'] ?? 0); ?>)">
                                                    <i class="bi bi-clock-history me-1"></i>Perpanjang
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-success" onclick="returnBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>')">
                                                <i class="bi bi-box-arrow-in-left me-1"></i>Kembalikan
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tab-pane fade" id="borrowed">
                    <?php
                    $filtered = array_filter($borrowings, fn($b) => $b['status'] === 'borrowed');
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Tidak ada peminjaman aktif</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $borrowing): ?>
                            <div class="borrowing-card status-<?php echo $borrowing['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($borrowing['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($borrowing['author']); ?>
                                            <span class="ms-3"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($borrowing['category']); ?></span>
                                        </p>
                                        <div class="d-flex gap-3 small">
                                            <span><strong>Dipinjam:</strong> <?php echo indonesianDate($borrowing['borrow_date']); ?></span>
                                            <span><strong>Jatuh Tempo:</strong> <?php echo indonesianDate($borrowing['due_date']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($borrowing['status']); ?>
                                        <div class="mt-2">
                                            <?php if (($borrowing['extended_count'] ?? 0) < 2): ?>
                                                <button class="btn btn-sm btn-warning me-2" onclick="extendBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>', <?php echo ($borrowing['extended_count'] ?? 0); ?>)">
                                                    <i class="bi bi-clock-history me-1"></i>Perpanjang
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-success" onclick="returnBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>')">
                                                <i class="bi bi-box-arrow-in-left me-1"></i>Kembalikan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>

                <div class="tab-pane fade" id="overdue">
                    <?php
                    $filtered = array_filter($borrowings, fn($b) => $b['status'] === 'overdue');
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Tidak ada peminjaman terlambat</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $borrowing): ?>
                            <div class="borrowing-card status-<?php echo $borrowing['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($borrowing['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($borrowing['author']); ?>
                                            <span class="ms-3"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($borrowing['category']); ?></span>
                                        </p>
                                        <div class="d-flex gap-3 small">
                                            <span><strong>Dipinjam:</strong> <?php echo indonesianDate($borrowing['borrow_date']); ?></span>
                                            <span class="text-danger"><strong>Jatuh Tempo:</strong> <?php echo indonesianDate($borrowing['due_date']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($borrowing['status']); ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning text-dark">Denda: <?php echo formatCurrency($borrowing['fine_amount']); ?></span>
                                        </div>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success" onclick="returnBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>')">
                                                <i class="bi bi-box-arrow-in-left me-1"></i>Kembalikan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>

                <div class="tab-pane fade" id="returned">
                    <?php
                    $filtered = array_filter($borrowings, fn($b) => $b['status'] === 'returned');
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada riwayat pengembalian</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $borrowing): ?>
                            <div class="borrowing-card status-<?php echo $borrowing['status']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($borrowing['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($borrowing['author']); ?>
                                            <span class="ms-3"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($borrowing['category']); ?></span>
                                        </p>
                                        <div class="d-flex gap-3 small">
                                            <span><strong>Dipinjam:</strong> <?php echo indonesianDate($borrowing['borrow_date']); ?></span>
                                            <span><strong>Dikembalikan:</strong> <?php echo indonesianDate($borrowing['return_date']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php echo getStatusBadge($borrowing['status']); ?>
                                        <?php if ($borrowing['fine_amount'] > 0): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-warning text-dark">Denda Dibayar: <?php echo formatCurrency($borrowing['fine_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
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
        function returnBook(id, title) {
            Swal.fire({
                title: 'Kembalikan Buku?',
                text: 'Apakah Anda yakin ingin mengembalikan "' + title + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Kembalikan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="return_book" value="1">
                        <input type="hidden" name="borrowing_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function extendBook(id, title, extendedCount) {
            const remainingExtensions = 2 - extendedCount;
            let message = `Perpanjang peminjaman buku "${title}" selama 7 hari lagi?`;

            if (remainingExtensions === 1) {
                message += '\n\nCatatan: Ini adalah perpanjangan terakhir yang bisa Anda lakukan untuk buku ini.';
            } else if (remainingExtensions === 2) {
                message += '\n\nAnda dapat memperpanjang maksimal 2 kali (14 hari).';
            }

            Swal.fire({
                title: 'Perpanjang Peminjaman?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#F59E0B',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Perpanjang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="extend_book" value="1">
                        <input type="hidden" name="borrowing_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
