<?php
require_once '../../config/config.php';
requireLogin();

$bookId = clean($_GET['id'] ?? '');
$db = new Database();

// Get book data
$book = $db->fetchOne("SELECT * FROM books WHERE id = ?", [$bookId]);

// If book not found in database, use dummy data
if (!$book && is_numeric($bookId)) {
    $dummyBooks = [
        1 => [
            'id' => 1,
            'title' => 'Manajemen Strategis',
            'author' => 'Fred David',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-344479-7',
            'total_quantity' => 10,
            'available_quantity' => 5,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Buku ini membahas konsep dan teori manajemen strategis secara komprehensif. Materi mencakup analisis lingkungan eksternal dan internal, formulasi strategi, implementasi, serta evaluasi dan kontrol strategi. Dilengkapi dengan studi kasus dari berbagai perusahaan terkemuka di dunia.',
            'publisher' => 'Pearson',
            'year' => 2020,
            'pages' => 720,
            'language' => 'Indonesia'
        ],
        2 => [
            'id' => 2,
            'title' => 'Akuntansi Biaya',
            'author' => 'William Carter',
            'category' => 'Akuntansi',
            'isbn' => '978-1-119-49698-2',
            'total_quantity' => 15,
            'available_quantity' => 8,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan lengkap tentang akuntansi biaya untuk mahasiswa dan praktisi. Buku ini membahas konsep biaya, sistem biaya, analisis biaya-volume-laba, dan pengambilan keputusan manajerial berbasis informasi biaya.',
            'publisher' => 'Wiley',
            'year' => 2019,
            'pages' => 650,
            'language' => 'Indonesia'
        ],
        3 => [
            'id' => 3,
            'title' => 'Ekonomi Makro',
            'author' => 'Gregory Mankiw',
            'category' => 'Ekonomi',
            'isbn' => '978-1-305-50703-7',
            'total_quantity' => 20,
            'available_quantity' => 10,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Buku teks ekonomi makro yang paling populer di dunia. Membahas GDP, inflasi, pengangguran, kebijakan fiskal dan moneter, pertumbuhan ekonomi, dan teori ekonomi makro modern dengan pendekatan yang mudah dipahami.',
            'publisher' => 'Cengage',
            'year' => 2021,
            'pages' => 600,
            'language' => 'Indonesia'
        ],
        4 => [
            'id' => 4,
            'title' => 'Pemasaran Modern',
            'author' => 'Philip Kotler',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-385646-0',
            'total_quantity' => 12,
            'available_quantity' => 6,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Konsep pemasaran modern untuk era digital. Membahas strategi pemasaran 4.0, digital marketing, social media marketing, dan customer experience management.',
            'publisher' => 'Pearson',
            'year' => 2020,
            'pages' => 580,
            'language' => 'Indonesia'
        ],
        5 => [
            'id' => 5,
            'title' => 'Analisis Laporan Keuangan',
            'author' => 'Kasmir',
            'category' => 'Akuntansi',
            'isbn' => '978-979-769-207-5',
            'total_quantity' => 8,
            'available_quantity' => 4,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan analisis laporan keuangan perusahaan. Membahas teknik analisis rasio keuangan, analisis horizontal dan vertikal, serta interpretasi laporan keuangan untuk pengambilan keputusan bisnis.',
            'publisher' => 'Rajawali Pers',
            'year' => 2018,
            'pages' => 450,
            'language' => 'Indonesia'
        ],
        6 => [
            'id' => 6,
            'title' => 'Bisnis Internasional',
            'author' => 'Charles Hill',
            'category' => 'Bisnis',
            'isbn' => '978-1-259-57842-9',
            'total_quantity' => 10,
            'available_quantity' => 7,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Eksplorasi dunia bisnis global dan perdagangan internasional. Membahas teori perdagangan internasional, investasi asing langsung, strategi global, dan manajemen operasi internasional.',
            'publisher' => 'McGraw-Hill',
            'year' => 2019,
            'pages' => 700,
            'language' => 'Indonesia'
        ],
        7 => [
            'id' => 7,
            'title' => 'Manajemen Keuangan',
            'author' => 'Eugene Brigham',
            'category' => 'Keuangan',
            'isbn' => '978-1-305-63297-3',
            'total_quantity' => 15,
            'available_quantity' => 9,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Teori dan praktik manajemen keuangan korporat. Membahas analisis keuangan, perencanaan keuangan, manajemen modal kerja, keputusan investasi, dan struktur modal.',
            'publisher' => 'Cengage',
            'year' => 2020,
            'pages' => 800,
            'language' => 'Indonesia'
        ],
        8 => [
            'id' => 8,
            'title' => 'Ekonomi Mikro',
            'author' => 'Robert Pindyck',
            'category' => 'Ekonomi',
            'isbn' => '978-0-13-417001-9',
            'total_quantity' => 10,
            'available_quantity' => 3,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Analisis ekonomi mikro dan perilaku konsumen. Membahas teori permintaan dan penawaran, elastisitas, perilaku konsumen, teori produksi, struktur pasar, dan kegagalan pasar.',
            'publisher' => 'Pearson',
            'year' => 2018,
            'pages' => 680,
            'language' => 'Indonesia'
        ],
        9 => [
            'id' => 9,
            'title' => 'Sistem Informasi Manajemen',
            'author' => 'Kenneth Laudon',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-450870-8',
            'total_quantity' => 12,
            'available_quantity' => 8,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Penerapan teknologi informasi dalam manajemen bisnis. Membahas sistem informasi enterprise, e-commerce, business intelligence, dan transformasi digital.',
            'publisher' => 'Pearson',
            'year' => 2020,
            'pages' => 620,
            'language' => 'Indonesia'
        ],
        10 => [
            'id' => 10,
            'title' => 'Perpajakan Indonesia',
            'author' => 'Mardiasmo',
            'category' => 'Akuntansi',
            'isbn' => '978-602-262-197-4',
            'total_quantity' => 10,
            'available_quantity' => 5,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan lengkap perpajakan di Indonesia. Membahas ketentuan umum perpajakan, PPh, PPN, PPnBM, dan administrasi pajak sesuai dengan peraturan terbaru.',
            'publisher' => 'Andi',
            'year' => 2019,
            'pages' => 500,
            'language' => 'Indonesia'
        ],
        11 => [
            'id' => 11,
            'title' => 'Entrepreneurship',
            'author' => 'Hisrich',
            'category' => 'Bisnis',
            'isbn' => '978-1-259-92373-4',
            'total_quantity' => 8,
            'available_quantity' => 6,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan memulai dan mengembangkan bisnis startup. Membahas identifikasi peluang bisnis, business plan, pendanaan startup, dan strategi pertumbuhan bisnis.',
            'publisher' => 'McGraw-Hill',
            'year' => 2020,
            'pages' => 550,
            'language' => 'Indonesia'
        ],
        12 => [
            'id' => 12,
            'title' => 'Pasar Modal Indonesia',
            'author' => 'Eduardus Tandelilin',
            'category' => 'Keuangan',
            'isbn' => '978-979-503-359-7',
            'total_quantity' => 10,
            'available_quantity' => 7,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Analisis investasi dan manajemen portofolio di pasar modal Indonesia. Membahas instrumen investasi, analisis fundamental dan teknikal, dan strategi investasi di bursa saham.',
            'publisher' => 'Kanisius',
            'year' => 2017,
            'pages' => 480,
            'language' => 'Indonesia'
        ]
    ];

    if (isset($dummyBooks[$bookId])) {
        $book = $dummyBooks[$bookId];
    }
}

if (!$book) {
    redirect(SITE_URL . '/browse.php', 'Buku tidak ditemukan', 'error');
}

$currentUser = getCurrentUser();
$pageTitle = htmlspecialchars($book['title']) . ' - ' . SITE_NAME;
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
        .book-detail-header {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .book-cover-large {
            width: 100%;
            max-width: 350px;
            height: 450px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 8rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin: 0 auto;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .info-row {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6B7280;
            width: 150px;
            flex-shrink: 0;
        }
        .info-value {
            color: #1F2937;
            flex-grow: 1;
        }
        .availability-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .availability-badge.available {
            background: #DEF7EC;
            color: #03543F;
        }
        .availability-badge.limited {
            background: #FEF3C7;
            color: #92400E;
        }
        .availability-badge.unavailable {
            background: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/browse.php">Browse Buku</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($book['title']); ?></li>
                </ol>
            </nav>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <!-- Book Cover -->
                <div class="col-lg-4 mb-4">
                    <div class="book-cover-large">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?php echo SITE_URL . '/uploads/books/' . htmlspecialchars($book['cover_image']); ?>"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;">
                        <?php else:
                            // Generate random book cover image
                            $coverImages = [
                                'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1519682337058-a94d519337bc?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1476275466078-4007374efbbe?w=500&h=700&fit=crop',
                                'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=500&h=700&fit=crop'
                            ];
                            $randomCover = $coverImages[($book['id'] ?? 0) % count($coverImages)];
                        ?>
                            <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;">
                        <?php endif; ?>
                    </div>

                    <!-- Availability Status -->
                    <div class="text-center mt-4">
                        <?php if ($book['available_quantity'] > 5): ?>
                            <div class="availability-badge available">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $book['available_quantity']; ?> Buku Tersedia
                            </div>
                        <?php elseif ($book['available_quantity'] > 0): ?>
                            <div class="availability-badge limited">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $book['available_quantity']; ?> Buku Tersedia
                            </div>
                        <?php else: ?>
                            <div class="availability-badge unavailable">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                Tidak Tersedia
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Borrow Button -->
                    <div class="d-grid gap-2 mt-4">
                        <?php if ($book['available_quantity'] > 0): ?>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#borrowModal">
                                <i class="bi bi-bookmark-plus me-2"></i>Pinjam Buku Ini
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="bi bi-x-circle me-2"></i>Tidak Tersedia
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali ke Browse
                        </a>
                    </div>
                </div>

                <!-- Book Details -->
                <div class="col-lg-8">
                    <!-- Main Info -->
                    <div class="info-card">
                        <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <p class="text-muted mb-4">
                            <i class="bi bi-person me-2"></i><?php echo htmlspecialchars($book['author']); ?>
                        </p>

                        <div class="mb-4">
                            <span class="badge bg-primary" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?>
                            </span>
                        </div>

                        <h5 class="fw-bold mb-3">Deskripsi</h5>
                        <p class="text-muted" style="line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($book['description'] ?? 'Tidak ada deskripsi tersedia.')); ?>
                        </p>
                    </div>

                    <!-- Detailed Info -->
                    <div class="info-card">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-info-circle me-2 text-primary"></i>Informasi Detail
                        </h5>

                        <div class="info-row">
                            <div class="info-label">ISBN</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Penulis</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['author']); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Penerbit</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['publisher'] ?? '-'); ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tahun Terbit</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['year'] ?? '-'); ?></div>
                        </div>

                        <?php if (!empty($book['pages'])): ?>
                        <div class="info-row">
                            <div class="info-label">Jumlah Halaman</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['pages']); ?> halaman</div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($book['language'])): ?>
                        <div class="info-row">
                            <div class="info-label">Bahasa</div>
                            <div class="info-value"><?php echo htmlspecialchars($book['language']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <div class="info-label">Kategori</div>
                            <div class="info-value">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Total Eksemplar</div>
                            <div class="info-value"><?php echo $book['quantity'] ?? 0; ?> buku</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tersedia</div>
                            <div class="info-value">
                                <strong class="text-success"><?php echo $book['available_quantity']; ?></strong> dari
                                <?php echo $book['quantity'] ?? 0; ?> buku
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <?php if ($book['available_quantity'] > 0): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Tersedia
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>Tidak Tersedia
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-bookmark-plus me-2"></i>Konfirmasi Peminjaman
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="borrow_process.php">
                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                    <div class="modal-body">
                        <p class="mb-3"><strong>Buku:</strong> <?php echo htmlspecialchars($book['title']); ?></p>
                        <p class="mb-3"><strong>Penulis:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="mb-3"><strong>Peminjam:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></p>

                        <?php
                        $borrowDurationDays = (int)getSetting('borrow_duration_days', 14);
                        $dueDate = date('Y-m-d', strtotime("+$borrowDurationDays days"));
                        ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-calendar-check me-2"></i>
                            Buku harus dikembalikan sebelum: <strong><?php echo formatDate($dueDate); ?></strong>
                            <br>
                            <small>Durasi peminjaman: <?php echo $borrowDurationDays; ?> hari</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-bookmark-plus me-2"></i>Pinjam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
