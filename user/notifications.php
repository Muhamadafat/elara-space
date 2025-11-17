<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $notifId = (int)$_POST['notif_id'];
    // In real app, this would update the database
    redirect(SITE_URL . '/user/notifications.php', 'Notifikasi ditandai sebagai dibaca', 'success');
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    // In real app, this would update all notifications
    redirect(SITE_URL . '/user/notifications.php', 'Semua notifikasi ditandai sebagai dibaca', 'success');
}

// Handle delete notification
if (isset($_POST['delete_notif'])) {
    $notifId = (int)$_POST['notif_id'];
    // In real app, this would delete from database
    redirect(SITE_URL . '/user/notifications.php', 'Notifikasi berhasil dihapus', 'success');
}

// Get notifications from database or use dummy data
try {
    $notifications = $db->fetchAll("
        SELECT *
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ", [$currentUser['id']]);
} catch (Exception $e) {
    $notifications = [];
}

// If no data, use dummy data
if (empty($notifications)) {
    $notifications = [
        [
            'id' => 1,
            'title' => 'Buku yang Anda request sudah tiba!',
            'message' => 'Buku "Python for Data Analysis" sudah tersedia dan siap dipinjam. Segera kunjungi perpustakaan!',
            'type' => 'success',
            'is_read' => 0,
            'link' => SITE_URL . '/user/books.php',
            'icon' => 'book',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'id' => 2,
            'title' => 'Pengingat: Buku akan jatuh tempo besok',
            'message' => 'Buku "Manajemen Strategis" akan jatuh tempo besok ('. date('d M Y', strtotime('+1 day')) .'). Jangan lupa kembalikan tepat waktu!',
            'type' => 'warning',
            'is_read' => 0,
            'link' => SITE_URL . '/user/borrowing.php',
            'icon' => 'clock-history',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'id' => 3,
            'title' => 'Request buku disetujui',
            'message' => 'Permintaan Anda untuk buku "Financial Statement Analysis" telah disetujui. Buku sedang dalam proses pemesanan.',
            'type' => 'info',
            'is_read' => 0,
            'link' => SITE_URL . '/user/requests.php',
            'icon' => 'check-circle',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'id' => 4,
            'title' => 'Anda memiliki denda',
            'message' => 'Terdapat denda keterlambatan sebesar Rp 12.000 untuk buku "Ekonomi Makro". Mohon segera lakukan pembayaran.',
            'type' => 'error',
            'is_read' => 1,
            'link' => SITE_URL . '/user/fines.php',
            'icon' => 'exclamation-triangle',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 5,
            'title' => 'Peminjaman berhasil',
            'message' => 'Anda telah berhasil meminjam buku "Akuntansi Biaya". Jatuh tempo: ' . date('d M Y', strtotime('+14 days')),
            'type' => 'success',
            'is_read' => 1,
            'link' => SITE_URL . '/user/borrowing.php',
            'icon' => 'book-fill',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'id' => 6,
            'title' => 'Buku baru tersedia',
            'message' => '5 buku baru kategori "Data Science" telah ditambahkan ke perpustakaan. Cek sekarang!',
            'type' => 'info',
            'is_read' => 1,
            'link' => SITE_URL . '/user/books.php?category=Data+Science',
            'icon' => 'stars',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 7,
            'title' => 'Pengembalian buku berhasil',
            'message' => 'Buku "Pemasaran Modern" telah berhasil dikembalikan. Terima kasih!',
            'type' => 'success',
            'is_read' => 1,
            'link' => SITE_URL . '/user/borrowing.php',
            'icon' => 'check2-circle',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days'))
        ],
        [
            'id' => 8,
            'title' => 'Pembayaran denda berhasil',
            'message' => 'Pembayaran denda sebesar Rp 6.000 telah berhasil diproses. Terima kasih!',
            'type' => 'success',
            'is_read' => 1,
            'link' => SITE_URL . '/user/fines.php',
            'icon' => 'cash-coin',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days'))
        ]
    ];
}

// Calculate statistics
$totalNotifications = count($notifications);
$unreadCount = count(array_filter($notifications, fn($n) => $n['is_read'] == 0));
$readCount = count(array_filter($notifications, fn($n) => $n['is_read'] == 1));

$pageTitle = 'Notifications - ' . SITE_NAME;
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
        .notif-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
            cursor: pointer;
        }
        .notif-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .notif-card.unread {
            background: #F0F9FF;
            font-weight: 500;
        }
        .notif-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .type-success { border-left-color: #10B981; }
        .type-error { border-left-color: #EF4444; }
        .type-warning { border-left-color: #F59E0B; }
        .type-info { border-left-color: #3B82F6; }
        .bg-success-light { background: #D1FAE5; color: #10B981; }
        .bg-error-light { background: #FEE2E2; color: #EF4444; }
        .bg-warning-light { background: #FEF3C7; color: #F59E0B; }
        .bg-info-light { background: #DBEAFE; color: #3B82F6; }
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
                    <h1 class="h2 fw-bold mb-1">Notifications</h1>
                    <p class="text-muted mb-0">Tetap update dengan semua aktivitas Anda</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <button class="btn btn-primary" onclick="markAllRead()">
                        <i class="bi bi-check-all me-2"></i>Tandai Semua Dibaca
                    </button>
                <?php endif; ?>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #3B82F6;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $totalNotifications; ?></h3>
                                <p class="text-muted mb-0 small">Total Notifikasi</p>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="bi bi-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #F59E0B;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $unreadCount; ?></h3>
                                <p class="text-muted mb-0 small">Belum Dibaca</p>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #10B981;">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="fw-bold mb-0"><?php echo $readCount; ?></h3>
                                <p class="text-muted mb-0 small">Sudah Dibaca</p>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#all">
                        <i class="bi bi-list me-2"></i>Semua
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#unread">
                        <i class="bi bi-bell-fill me-2"></i>Belum Dibaca
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#read">
                        <i class="bi bi-check-circle me-2"></i>Sudah Dibaca
                    </button>
                </li>
            </ul>

            <!-- Notifications List -->
            <div class="tab-content">
                <div class="tab-pane fade show active" id="all">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada notifikasi</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <?php
                            $iconBgClass = 'bg-info-light';
                            if ($notif['type'] === 'success') $iconBgClass = 'bg-success-light';
                            if ($notif['type'] === 'error') $iconBgClass = 'bg-error-light';
                            if ($notif['type'] === 'warning') $iconBgClass = 'bg-warning-light';
                            ?>
                            <div class="notif-card type-<?php echo $notif['type']; ?> <?php echo $notif['is_read'] ? '' : 'unread'; ?>"
                                 onclick="openNotification(<?php echo $notif['id']; ?>, '<?php echo $notif['link']; ?>', <?php echo $notif['is_read']; ?>)">
                                <div class="d-flex gap-3">
                                    <div class="notif-icon <?php echo $iconBgClass; ?>">
                                        <i class="bi bi-<?php echo $notif['icon']; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="badge bg-primary ms-2" style="font-size: 0.65rem;">NEW</span>
                                                <?php endif; ?>
                                            </h6>
                                            <button class="btn btn-sm btn-link text-danger p-0"
                                                    onclick="event.stopPropagation(); deleteNotification(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($notif['title']); ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <p class="text-muted mb-2 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?php echo formatDateTime($notif['created_at'], 'd M Y, H:i'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="unread">
                    <?php
                    $filtered = array_filter($notifications, fn($n) => $n['is_read'] == 0);
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Semua notifikasi sudah dibaca</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $notif):
                            $iconBgClass = 'bg-info-light';
                            if ($notif['type'] === 'success') $iconBgClass = 'bg-success-light';
                            if ($notif['type'] === 'error') $iconBgClass = 'bg-error-light';
                            if ($notif['type'] === 'warning') $iconBgClass = 'bg-warning-light';
                            ?>
                            <div class="notif-card type-<?php echo $notif['type']; ?> unread"
                                 onclick="openNotification(<?php echo $notif['id']; ?>, '<?php echo $notif['link']; ?>', <?php echo $notif['is_read']; ?>)">
                                <div class="d-flex gap-3">
                                    <div class="notif-icon <?php echo $iconBgClass; ?>">
                                        <i class="bi bi-<?php echo $notif['icon']; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                                <span class="badge bg-primary ms-2" style="font-size: 0.65rem;">NEW</span>
                                            </h6>
                                            <button class="btn btn-sm btn-link text-danger p-0"
                                                    onclick="event.stopPropagation(); deleteNotification(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($notif['title']); ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <p class="text-muted mb-2 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?php echo formatDateTime($notif['created_at'], 'd M Y, H:i'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>

                <div class="tab-pane fade" id="read">
                    <?php
                    $filtered = array_filter($notifications, fn($n) => $n['is_read'] == 1);
                    if (empty($filtered)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">Belum ada notifikasi yang dibaca</p>
                        </div>
                    <?php else:
                        foreach ($filtered as $notif):
                            $iconBgClass = 'bg-info-light';
                            if ($notif['type'] === 'success') $iconBgClass = 'bg-success-light';
                            if ($notif['type'] === 'error') $iconBgClass = 'bg-error-light';
                            if ($notif['type'] === 'warning') $iconBgClass = 'bg-warning-light';
                            ?>
                            <div class="notif-card type-<?php echo $notif['type']; ?>"
                                 onclick="openNotification(<?php echo $notif['id']; ?>, '<?php echo $notif['link']; ?>', <?php echo $notif['is_read']; ?>)">
                                <div class="d-flex gap-3">
                                    <div class="notif-icon <?php echo $iconBgClass; ?>">
                                        <i class="bi bi-<?php echo $notif['icon']; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                            <button class="btn btn-sm btn-link text-danger p-0"
                                                    onclick="event.stopPropagation(); deleteNotification(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($notif['title']); ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <p class="text-muted mb-2 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i><?php echo formatDateTime($notif['created_at'], 'd M Y, H:i'); ?>
                                        </small>
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
        function openNotification(id, link, isRead) {
            if (!isRead) {
                // Mark as read
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo SITE_URL; ?>/user/notifications.php';
                form.innerHTML = `
                    <input type="hidden" name="mark_read" value="1">
                    <input type="hidden" name="notif_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }

            // Open link if provided
            if (link) {
                setTimeout(() => {
                    window.location.href = link;
                }, 300);
            }
        }

        function markAllRead() {
            Swal.fire({
                title: 'Tandai Semua Dibaca?',
                text: 'Semua notifikasi akan ditandai sebagai sudah dibaca',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3B82F6',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Tandai Semua',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="mark_all_read" value="1">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deleteNotification(id, title) {
            Swal.fire({
                title: 'Hapus Notifikasi?',
                text: 'Apakah Anda yakin ingin menghapus notifikasi ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="delete_notif" value="1">
                        <input type="hidden" name="notif_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
