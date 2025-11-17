<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Handle order book
if (isset($_POST['order_book'])) {
    $bookId = (int)$_POST['book_id'];

    try {
        // Get book details
        $book = $db->fetchOne("SELECT * FROM marketplace_books WHERE id = ? AND status = 'available'", [$bookId]);

        if (!$book) {
            redirect(SITE_URL . '/user/marketplace.php', 'Buku tidak tersedia!', 'error');
        }

        // Check stock
        if ($book['stock'] <= 0) {
            redirect(SITE_URL . '/user/marketplace.php', 'Stok buku habis!', 'error');
        }

        // Calculate commission
        $commissionAmount = $book['price'] * ($book['commission_rate'] / 100);

        // Calculate estimated ready date (3-7 days from now)
        $daysToAdd = rand(3, 7);
        $estimatedReady = date('Y-m-d', strtotime("+{$daysToAdd} days"));

        // Create order
        $db->query("
            INSERT INTO marketplace_orders
            (user_id, book_id, title, partner_store, price, commission_amount, status, payment_status, estimated_ready)
            VALUES (?, ?, ?, ?, ?, ?, 'processing', 'paid', ?)
        ", [
            $currentUser['id'],
            $bookId,
            $book['title'],
            $book['partner_store'],
            $book['price'],
            $commissionAmount,
            $estimatedReady
        ]);

        // Update stock
        $db->query("UPDATE marketplace_books SET stock = stock - 1 WHERE id = ?", [$bookId]);

        // Create notification
        $db->query("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (?, ?, ?, 'success', '/user/marketplace.php')
        ", [
            $currentUser['id'],
            'Pesanan Berhasil',
            "Pesanan buku '{$book['title']}' berhasil dibuat. Estimasi siap: " . indonesianDate($estimatedReady)
        ]);

        // Log activity
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'order_marketplace_book', 'marketplace', ?, ?)
        ", [
            $currentUser['id'],
            "Memesan buku: {$book['title']}",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        redirect(SITE_URL . '/user/marketplace.php', 'Pesanan berhasil dibuat! Anda akan mendapat notifikasi saat buku siap diambil.', 'success');

    } catch (Exception $e) {
        redirect(SITE_URL . '/user/marketplace.php', 'Terjadi kesalahan: ' . $e->getMessage(), 'error');
    }
}

// Get marketplace books (buku yang bisa dibeli dari toko partner)
try {
    $marketplaceBooks = $db->fetchAll("
        SELECT * FROM marketplace_books
        WHERE status = 'available' AND stock > 0
        ORDER BY created_at DESC
    ");
} catch (Exception $e) {
    $marketplaceBooks = [];
    error_log("Error fetching marketplace books: " . $e->getMessage());
}

// Get user's orders
try {
    $myOrders = $db->fetchAll("
        SELECT o.*
        FROM marketplace_orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ", [$currentUser['id']]);
} catch (Exception $e) {
    $myOrders = [];
    error_log("Error fetching marketplace orders: " . $e->getMessage());
}

$pageTitle = 'Marketplace - ' . SITE_NAME;
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
        .marketplace-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid #E5E7EB;
        }
        .marketplace-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            border-color: #3B82F6;
        }
        .partner-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10B981;
        }
        .commission-info {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid;
        }
        .status-processing { border-left-color: #F59E0B; }
        .status-ready_pickup { border-left-color: #10B981; }
        .status-completed { border-left-color: #6B7280; }
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
                    <h1 class="h2 fw-bold mb-1">Marketplace</h1>
                    <p class="text-muted mb-0">Beli buku baru dari toko partner kami</p>
                </div>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Info Banner -->
            <div class="alert alert-info mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Cara Kerja Marketplace</h6>
                        <p class="mb-0 small">
                            1. Pilih buku yang ingin dibeli<br>
                            2. Klik "Pesan Sekarang" dan lakukan pembayaran<br>
                            3. Toko partner akan menyiapkan buku<br>
                            4. Anda akan mendapat notifikasi saat buku <strong>siap diambil di perpustakaan</strong><br>
                            5. Ambil buku Anda di perpustakaan (sudah termasuk komisi untuk perpustakaan)
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="bi bi-shop" style="font-size: 4rem; color: #3B82F6;"></i>
                    </div>
                </div>
            </div>

            <!-- My Orders Section -->
            <?php if (!empty($myOrders)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bag-check me-2"></i>Pesanan Saya</h5>
                    <?php foreach ($myOrders as $order): ?>
                        <div class="order-card status-<?php echo $order['status']; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($order['title']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-shop me-1"></i><?php echo $order['partner_store']; ?> •
                                        <?php echo formatCurrency($order['price']); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php if ($order['status'] === 'ready_pickup'): ?>
                                        <span class="badge bg-success mb-2">
                                            <i class="bi bi-check-circle me-1"></i>Siap Diambil!
                                        </span>
                                        <br>
                                        <small class="text-muted">Ambil di perpustakaan</small>
                                    <?php elseif ($order['status'] === 'processing'): ?>
                                        <span class="badge bg-warning mb-2">
                                            <i class="bi bi-clock-history me-1"></i>Diproses
                                        </span>
                                        <br>
                                        <small class="text-muted">Est. siap: <?php echo indonesianDate($order['estimated_ready']); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary mb-2">
                                            <i class="bi bi-check-all me-1"></i>Selesai
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Available Books -->
            <h5 class="fw-bold mb-3">
                <i class="bi bi-cart-plus me-2"></i>Buku Tersedia
                <span class="badge bg-primary ms-2"><?php echo count($marketplaceBooks); ?> Buku</span>
            </h5>

            <div class="row">
                <?php foreach ($marketplaceBooks as $book): ?>
                    <div class="col-md-6 mb-3">
                        <div class="marketplace-card">
                            <div class="row">
                                <div class="col-md-4">
                                    <?php
                                    $coverImages = [
                                        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=400&fit=crop',
                                        'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=300&h=400&fit=crop',
                                        'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=300&h=400&fit=crop',
                                        'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=300&h=400&fit=crop',
                                        'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=300&h=400&fit=crop',
                                        'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=300&h=400&fit=crop'
                                    ];
                                    $randomCover = $coverImages[($book['id'] ?? 0) % count($coverImages)];
                                    ?>
                                    <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         class="img-fluid rounded" style="width: 100%; height: 250px; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($book['title']); ?></h6>
                                        <span class="partner-badge"><?php echo $book['partner_store']; ?></span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($book['author']); ?><br>
                                        <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($book['category']); ?>
                                    </p>
                                    <div class="commission-info mb-2">
                                        <i class="bi bi-piggy-bank me-1"></i>
                                        Komisi perpustakaan: <strong><?php echo $book['commission_rate']; ?>%</strong> (<?php echo formatCurrency($book['price'] * $book['commission_rate'] / 100); ?>)
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="price-tag"><?php echo formatCurrency($book['price']); ?></div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">
                                                <i class="bi bi-truck me-1"></i><?php echo $book['estimated_delivery']; ?>
                                            </small>
                                            <small class="text-success">
                                                <i class="bi bi-check-circle me-1"></i>Stock: <?php echo $book['stock']; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100" onclick="orderBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>', <?php echo $book['price']; ?>)">
                                        <i class="bi bi-cart-plus me-2"></i>Pesan Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        function orderBook(id, title, price) {
            const formattedPrice = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(price);

            Swal.fire({
                title: 'Pesan Buku',
                html: `
                    <div class="text-start">
                        <p><strong>Judul:</strong> ${title}</p>
                        <p><strong>Harga:</strong> ${formattedPrice}</p>
                        <hr>
                        <p class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Setelah pemesanan dikonfirmasi, buku akan disiapkan oleh toko partner.
                            Anda akan mendapat notifikasi saat buku sudah <strong>siap diambil di perpustakaan</strong>.
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3B82F6',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Konfirmasi Pesanan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="order_book" value="1">
                        <input type="hidden" name="book_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
