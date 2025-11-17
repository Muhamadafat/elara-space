<?php
require_once '../config/config.php';
requireLogin();

if (hasRole(['admin', 'super_admin'])) {
    redirect(SITE_URL . '/admin/index.php');
}

$currentUser = getCurrentUser();
$db = new Database();

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where = ["status = 'available'", "available_quantity > 0"];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($category)) {
    $where[] = "category = ?";
    $params[] = $category;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Sort options
$orderBy = match($sort) {
    'popular' => "(SELECT COUNT(*) FROM borrowings WHERE book_id = books.id) DESC",
    'oldest' => "created_at ASC",
    'title_asc' => "title ASC",
    'title_desc' => "title DESC",
    default => "created_at DESC"
};

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalBooks = $db->fetchOne("SELECT COUNT(*) as count FROM books $whereClause", $params)['count'];
$pagination = paginate($totalBooks, $page, 12); // 12 books per page

// Get books
$query = "SELECT * FROM books
          $whereClause
          ORDER BY $orderBy
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$books = $db->fetchAll($query, $params);

// If no books, use dummy data (only when no filters applied)
if (empty($books) && empty($search) && empty($category)) {
    $dummyBooks = [
        ['id' => 1, 'title' => 'Manajemen Strategis', 'author' => 'Fred David', 'category' => 'Manajemen', 'isbn' => '978-0-13-344479-7', 'total_quantity' => 10, 'available_quantity' => 5, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Pearson', 'year' => 2020],
        ['id' => 2, 'title' => 'Akuntansi Biaya', 'author' => 'William Carter', 'category' => 'Akuntansi', 'isbn' => '978-1-119-49698-2', 'total_quantity' => 15, 'available_quantity' => 8, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Wiley', 'year' => 2019],
        ['id' => 3, 'title' => 'Ekonomi Makro', 'author' => 'Gregory Mankiw', 'category' => 'Ekonomi', 'isbn' => '978-1-305-50703-7', 'total_quantity' => 20, 'available_quantity' => 10, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Cengage', 'year' => 2021],
        ['id' => 4, 'title' => 'Pemasaran Modern', 'author' => 'Philip Kotler', 'category' => 'Manajemen', 'isbn' => '978-0-13-385646-0', 'total_quantity' => 12, 'available_quantity' => 6, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Pearson', 'year' => 2020],
        ['id' => 5, 'title' => 'Analisis Laporan Keuangan', 'author' => 'Kasmir', 'category' => 'Akuntansi', 'isbn' => '978-979-769-207-5', 'total_quantity' => 8, 'available_quantity' => 4, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Rajawali Pers', 'year' => 2018],
        ['id' => 6, 'title' => 'Bisnis Internasional', 'author' => 'Charles Hill', 'category' => 'Bisnis', 'isbn' => '978-1-259-57842-9', 'total_quantity' => 10, 'available_quantity' => 7, 'status' => 'available', 'cover_image' => null, 'publisher' => 'McGraw-Hill', 'year' => 2019],
        ['id' => 7, 'title' => 'Manajemen Keuangan', 'author' => 'Eugene Brigham', 'category' => 'Keuangan', 'isbn' => '978-1-305-63297-3', 'total_quantity' => 15, 'available_quantity' => 9, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Cengage', 'year' => 2020],
        ['id' => 8, 'title' => 'Ekonomi Mikro', 'author' => 'Robert Pindyck', 'category' => 'Ekonomi', 'isbn' => '978-0-13-417001-9', 'total_quantity' => 10, 'available_quantity' => 3, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Pearson', 'year' => 2018],
        ['id' => 9, 'title' => 'Sistem Informasi Manajemen', 'author' => 'Kenneth Laudon', 'category' => 'Manajemen', 'isbn' => '978-0-13-450870-8', 'total_quantity' => 12, 'available_quantity' => 8, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Pearson', 'year' => 2020],
        ['id' => 10, 'title' => 'Perpajakan Indonesia', 'author' => 'Mardiasmo', 'category' => 'Akuntansi', 'isbn' => '978-602-262-197-4', 'total_quantity' => 10, 'available_quantity' => 5, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Andi', 'year' => 2019],
        ['id' => 11, 'title' => 'Entrepreneurship', 'author' => 'Hisrich', 'category' => 'Bisnis', 'isbn' => '978-1-259-92373-4', 'total_quantity' => 8, 'available_quantity' => 6, 'status' => 'available', 'cover_image' => null, 'publisher' => 'McGraw-Hill', 'year' => 2020],
        ['id' => 12, 'title' => 'Pasar Modal Indonesia', 'author' => 'Eduardus Tandelilin', 'category' => 'Keuangan', 'isbn' => '978-979-503-359-7', 'total_quantity' => 10, 'available_quantity' => 7, 'status' => 'available', 'cover_image' => null, 'publisher' => 'Kanisius', 'year' => 2017]
    ];
    $books = $dummyBooks;
    $totalBooks = count($dummyBooks);
    $pagination = paginate($totalBooks, $page, 12);
}

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND status = 'available' ORDER BY category");

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

$pageTitle = 'Telusuri Buku - ' . SITE_NAME;
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>Telusuri Buku</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Buku</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Cari Buku</label>
                                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan judul, penulis, ISBN..." value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['category']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Urutkan</label>
                                <select class="form-select" name="sort">
                                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Paling Populer</option>
                                    <option value="title_asc" <?php echo $sort == 'title_asc' ? 'selected' : ''; ?>>Judul (A-Z)</option>
                                    <option value="title_desc" <?php echo $sort == 'title_desc' ? 'selected' : ''; ?>>Judul (Z-A)</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Cari
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Books Grid -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5><?php echo formatNumber($totalBooks); ?> Buku Ditemukan</h5>
                        <div>
                            <a href="<?php echo SITE_URL; ?>/user/request-book.php" class="btn btn-warning">
                                <i class="bi bi-cart-plus me-2"></i>Tidak menemukan buku? Request di sini
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($books): ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card book-card h-100">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $book['cover_image']; ?>"
                                         class="book-cover" alt="<?php echo $book['title']; ?>">
                                <?php else:
                                    // Generate random book cover image
                                    $coverImages = [
                                        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1519682337058-a94d519337bc?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1476275466078-4007374efbbe?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=250&fit=crop'
                                    ];
                                    $randomCover = $coverImages[($book['id'] ?? 0) % count($coverImages)];
                                ?>
                                    <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         style="width: 100%; height: 250px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="book-title"><?php echo truncate($book['title'], 45); ?></h6>
                                    <p class="book-author">by <?php echo $book['author']; ?></p>

                                    <?php if ($book['category']): ?>
                                        <p class="book-info">
                                            <span class="badge bg-secondary"><?php echo $book['category']; ?></span>
                                        </p>
                                    <?php endif; ?>

                                    <div class="book-info mb-2">
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <span class="badge bg-success">Tersedia: <?php echo $book['available_quantity']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </div>

                                    <a href="<?php echo SITE_URL; ?>/user/books/view.php?id=<?php echo $book['id']; ?>"
                                       class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-eye me-2"></i>Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php echo renderPagination($pagination, 'books.php'); ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="bi bi-search"></i>
                            <h4>Tidak ada buku ditemukan</h4>
                            <p>Coba sesuaikan kriteria pencarian atau filter Anda</p>
                            <a href="books.php" class="btn btn-primary">Hapus Filter</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
