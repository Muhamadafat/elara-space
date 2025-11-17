<?php
require_once 'config/config.php';

$db = new Database();

// Get search and filter parameters
$search = clean($_GET['search'] ?? '');
$category = clean($_GET['category'] ?? '');
$sort = clean($_GET['sort'] ?? 'latest');

// Build query
$query = "SELECT * FROM books WHERE status = 'available' AND available_quantity > 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

// Sorting
switch ($sort) {
    case 'title_asc':
        $query .= " ORDER BY title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY title DESC";
        break;
    case 'author':
        $query .= " ORDER BY author ASC";
        break;
    case 'latest':
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$books = $db->fetchAll($query, $params);

// If no books, use dummy data
if (empty($books) && empty($search) && empty($category)) {
    $books = [
        [
            'id' => 1,
            'title' => 'Manajemen Strategis',
            'author' => 'Fred David',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-344479-7',
            'total_quantity' => 10,
            'available_quantity' => 5,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Buku ini membahas konsep dan teori manajemen strategis secara komprehensif.',
            'publisher' => 'Pearson',
            'year' => 2020
        ],
        [
            'id' => 2,
            'title' => 'Akuntansi Biaya',
            'author' => 'William Carter',
            'category' => 'Akuntansi',
            'isbn' => '978-1-119-49698-2',
            'total_quantity' => 15,
            'available_quantity' => 8,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan lengkap tentang akuntansi biaya untuk mahasiswa dan praktisi.',
            'publisher' => 'Wiley',
            'year' => 2019
        ],
        [
            'id' => 3,
            'title' => 'Ekonomi Makro',
            'author' => 'Gregory Mankiw',
            'category' => 'Ekonomi',
            'isbn' => '978-1-305-50703-7',
            'total_quantity' => 20,
            'available_quantity' => 10,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Buku teks ekonomi makro yang paling populer di dunia.',
            'publisher' => 'Cengage',
            'year' => 2021
        ],
        [
            'id' => 4,
            'title' => 'Pemasaran Modern',
            'author' => 'Philip Kotler',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-385646-0',
            'total_quantity' => 12,
            'available_quantity' => 6,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Konsep pemasaran modern untuk era digital.',
            'publisher' => 'Pearson',
            'year' => 2020
        ],
        [
            'id' => 5,
            'title' => 'Analisis Laporan Keuangan',
            'author' => 'Kasmir',
            'category' => 'Akuntansi',
            'isbn' => '978-979-769-207-5',
            'total_quantity' => 8,
            'available_quantity' => 4,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan analisis laporan keuangan perusahaan.',
            'publisher' => 'Rajawali Pers',
            'year' => 2018
        ],
        [
            'id' => 6,
            'title' => 'Bisnis Internasional',
            'author' => 'Charles Hill',
            'category' => 'Bisnis',
            'isbn' => '978-1-259-57842-9',
            'total_quantity' => 10,
            'available_quantity' => 7,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Eksplorasi dunia bisnis global dan perdagangan internasional.',
            'publisher' => 'McGraw-Hill',
            'year' => 2019
        ],
        [
            'id' => 7,
            'title' => 'Manajemen Keuangan',
            'author' => 'Eugene Brigham',
            'category' => 'Keuangan',
            'isbn' => '978-1-305-63297-3',
            'total_quantity' => 15,
            'available_quantity' => 9,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Teori dan praktik manajemen keuangan korporat.',
            'publisher' => 'Cengage',
            'year' => 2020
        ],
        [
            'id' => 8,
            'title' => 'Ekonomi Mikro',
            'author' => 'Robert Pindyck',
            'category' => 'Ekonomi',
            'isbn' => '978-0-13-417001-9',
            'total_quantity' => 10,
            'available_quantity' => 3,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Analisis ekonomi mikro dan perilaku konsumen.',
            'publisher' => 'Pearson',
            'year' => 2018
        ],
        [
            'id' => 9,
            'title' => 'Sistem Informasi Manajemen',
            'author' => 'Kenneth Laudon',
            'category' => 'Manajemen',
            'isbn' => '978-0-13-450870-8',
            'total_quantity' => 12,
            'available_quantity' => 8,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Penerapan teknologi informasi dalam manajemen bisnis.',
            'publisher' => 'Pearson',
            'year' => 2020
        ],
        [
            'id' => 10,
            'title' => 'Perpajakan Indonesia',
            'author' => 'Mardiasmo',
            'category' => 'Akuntansi',
            'isbn' => '978-602-262-197-4',
            'total_quantity' => 10,
            'available_quantity' => 5,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan lengkap perpajakan di Indonesia.',
            'publisher' => 'Andi',
            'year' => 2019
        ],
        [
            'id' => 11,
            'title' => 'Entrepreneurship',
            'author' => 'Hisrich',
            'category' => 'Bisnis',
            'isbn' => '978-1-259-92373-4',
            'total_quantity' => 8,
            'available_quantity' => 6,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Panduan memulai dan mengembangkan bisnis startup.',
            'publisher' => 'McGraw-Hill',
            'year' => 2020
        ],
        [
            'id' => 12,
            'title' => 'Pasar Modal Indonesia',
            'author' => 'Eduardus Tandelilin',
            'category' => 'Keuangan',
            'isbn' => '978-979-503-359-7',
            'total_quantity' => 10,
            'available_quantity' => 7,
            'status' => 'available',
            'cover_image' => null,
            'description' => 'Analisis investasi dan manajemen portofolio di pasar modal Indonesia.',
            'publisher' => 'Kanisius',
            'year' => 2017
        ]
    ];
}

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");

// If no categories, use dummy categories
if (empty($categories)) {
    $categories = [
        ['category' => 'Manajemen'],
        ['category' => 'Akuntansi'],
        ['category' => 'Ekonomi'],
        ['category' => 'Bisnis'],
        ['category' => 'Keuangan']
    ];
}

$pageTitle = 'Browse Books - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .public-navbar {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .public-navbar .navbar-brand {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .public-navbar .btn-login {
            background: white;
            color: #3B82F6;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .public-navbar .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .hero-banner {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 20px;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Public Navbar -->
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-book-half me-2"></i>Elara Space
            </a>
            <div class="d-flex">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/<?php echo hasRole(['admin', 'super_admin']) ? 'admin' : 'user'; ?>/index.php" class="btn btn-login">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Hero Banner -->
        <div class="hero-banner text-center">
            <h1 class="display-4 fw-bold text-gradient mb-3">Jelajahi Koleksi Buku Kami</h1>
            <p class="lead text-muted">Lebih dari 10,000+ buku dari 5 universitas terkemuka</p>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Cari judul, penulis, atau ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sort" class="form-select">
                            <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Judul A-Z</option>
                            <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Judul Z-A</option>
                            <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Penulis</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <h5 class="mb-0">Ditemukan <strong><?php echo count($books); ?></strong> buku</h5>
            <a href="?" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset Filter
            </a>
        </div>

        <!-- Books Grid -->
        <?php if (empty($books)): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h4>Tidak ada buku ditemukan</h4>
                <p>Coba ubah filter atau kata kunci pencarian Anda</p>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="card book-card">
                        <?php if ($book['cover_image']): ?>
                            <img src="<?php echo SITE_URL . '/uploads/books/' . htmlspecialchars($book['cover_image']); ?>"
                                 class="book-cover"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php else:
                            // Generate random book cover image
                            $coverImages = [
                                'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1519682337058-a94d519337bc?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1476275466078-4007374efbbe?w=400&h=300&fit=crop',
                                'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=300&fit=crop'
                            ];
                            $randomCover = $coverImages[($book['id'] ?? 0) % count($coverImages)];
                        ?>
                            <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="book-cover" style="width: 100%; height: 300px; object-fit: cover;">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="book-author">
                                <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($book['author']); ?>
                            </p>
                            <?php if ($book['category']): ?>
                                <span class="badge bg-info mb-2">
                                    <?php echo htmlspecialchars($book['category']); ?>
                                </span>
                            <?php endif; ?>
                            <div class="book-info mt-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Tersedia:</small>
                                    <small><strong><?php echo $book['available_quantity']; ?></strong> dari <?php echo $book['quantity'] ?? $book['total_quantity'] ?? 0; ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo SITE_URL; ?>/user/books/view.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-eye me-2"></i>Lihat Detail
                                </a>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode('/user/books/view.php?id=' . $book['id']); ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login untuk Pinjam
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 Elara Space. All rights reserved.</p>
            <small class="text-muted">Melayani Fakultas Ekonomi di UPI, UNPAD, UIN, UMB, IKOPIN</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
