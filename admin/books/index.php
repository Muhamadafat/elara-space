<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $bookId = (int)$_GET['id'];

    // Check if book has active borrowings
    $activeBorrowings = $db->fetchOne(
        "SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')",
        [$bookId]
    );

    if ($activeBorrowings['count'] > 0) {
        setFlashMessage('error', 'Cannot delete book with active borrowings');
    } else {
        // Get book info for logging
        $book = $db->fetchOne("SELECT * FROM books WHERE id = ?", [$bookId]);

        if ($db->execute("DELETE FROM books WHERE id = ?", [$bookId])) {
            // Delete cover image if exists
            if (!empty($book['cover_image']) && file_exists(BOOK_COVER_DIR . $book['cover_image'])) {
                deleteFile(BOOK_COVER_DIR . $book['cover_image']);
            }

            logActivity($currentUser['id'], 'delete_book', 'books', 'Deleted book: ' . $book['title']);
            setFlashMessage('success', 'Book deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete book');
        }
    }

    redirect(SITE_URL . '/admin/books/index.php');
}

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$publisher = $_GET['publisher'] ?? '';

// Build query
$where = [];
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

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
}

if (!empty($publisher)) {
    $where[] = "publisher_id = ?";
    $params[] = $publisher;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalBooks = $db->fetchOne("SELECT COUNT(*) as count FROM books $whereClause", $params)['count'];
$pagination = paginate($totalBooks, $page);

// Get books
$query = "SELECT b.*, p.name as publisher_name
          FROM books b
          LEFT JOIN publishers p ON b.publisher_id = p.id
          $whereClause
          ORDER BY b.created_at DESC
          LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}";

$books = $db->fetchAll($query, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");

// Get publishers for filter
$publishers = $db->fetchAll("SELECT id, name FROM publishers ORDER BY name");

$pageTitle = 'Books Management - ' . SITE_NAME;
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
        .book-card-admin {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
            border: 2px solid transparent;
        }
        .book-card-admin:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: #3B82F6;
        }
        .book-cover-admin {
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }
        .book-cover-admin img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .view-toggle {
            background: white;
            border-radius: 10px;
            padding: 0.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .view-toggle .btn {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        .view-toggle .btn.active {
            background: #3B82F6;
            color: white;
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
                    <h1 class="h2 fw-bold mb-1">Books Management</h1>
                    <p class="text-muted mb-0">Kelola koleksi buku perpustakaan</p>
                </div>
                <div>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Buku Baru
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Cari judul, penulis, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="category">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="publisher">
                                <option value="">Semua Publisher</option>
                                <?php foreach ($publishers as $pub): ?>
                                    <option value="<?php echo $pub['id']; ?>" <?php echo $publisher == $pub['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pub['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="available" <?php echo $status == 'available' ? 'selected' : ''; ?>>Tersedia</option>
                                <option value="unavailable" <?php echo $status == 'unavailable' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                                <option value="maintenance" <?php echo $status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Books Grid -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-book-fill me-2 text-primary"></i>
                    Daftar Buku <span class="badge bg-primary"><?php echo formatNumber($totalBooks); ?></span>
                </h5>
            </div>

            <?php if (!empty($books)): ?>
                <div class="row g-4 mb-4">
                    <?php foreach ($books as $book): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="book-card-admin">
                                <!-- Book Cover -->
                                <div class="book-cover-admin">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $book['cover_image']; ?>"
                                             alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php else: ?>
                                        <?php
                                        $coverImages = [
                                            'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=250&fit=crop',
                                            'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=300&h=250&fit=crop',
                                            'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=300&h=250&fit=crop',
                                            'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=300&h=250&fit=crop',
                                        ];
                                        $randomCover = $coverImages[array_rand($coverImages)];
                                        ?>
                                        <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php endif; ?>
                                    <!-- Status Badge -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <?php echo getStatusBadge($book['status']); ?>
                                    </div>
                                </div>

                                <!-- Book Info -->
                                <div class="p-3">
                                    <h6 class="fw-bold mb-2" style="height: 44px; overflow: hidden;">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </h6>
                                    <p class="text-muted small mb-2" style="height: 20px; overflow: hidden;">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($book['author']); ?>
                                    </p>

                                    <?php if ($book['category']): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($book['category']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-hash me-1"></i>
                                            <?php echo htmlspecialchars($book['isbn'] ?? 'No ISBN'); ?>
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">Tersedia:</small>
                                        <strong class="text-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $book['available_quantity']; ?> / <?php echo $book['quantity']; ?>
                                        </strong>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <div class="btn-group">
                                            <a href="view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=1&id=<?php echo $book['id']; ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               title="Delete"
                                               onclick="return confirm('Yakin ingin menghapus buku ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php echo renderPagination($pagination, 'index.php'); ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Belum ada buku</h4>
                    <p class="text-muted">Mulai dengan menambahkan buku pertama ke perpustakaan</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Buku Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
