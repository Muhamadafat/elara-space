<?php
require_once '../../config/config.php';
requireAdmin();

$db = new Database();

if (!isset($_GET['id'])) {
    redirect(SITE_URL . '/admin/books/index.php', 'Invalid book ID', 'error');
}

$bookId = (int)$_GET['id'];

// Get book details
$query = "SELECT b.*, p.name as publisher_name, p.email as publisher_email, p.phone as publisher_phone
          FROM books b
          LEFT JOIN publishers p ON b.publisher_id = p.id
          WHERE b.id = ?";

$book = $db->fetchOne($query, [$bookId]);

if (!$book) {
    redirect(SITE_URL . '/admin/books/index.php', 'Book not found', 'error');
}

// Get borrowing history
$borrowHistory = $db->fetchAll(
    "SELECT b.*, u.name as user_name, u.user_code, u.email, u.role
     FROM borrowings b
     JOIN users u ON b.user_id = u.id
     WHERE b.book_id = ?
     ORDER BY b.created_at DESC
     LIMIT 10",
    [$bookId]
);

// Get borrowing statistics
$stats = [
    'total_borrowed' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ?", [$bookId])['count'],
    'currently_borrowed' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status = 'borrowed'", [$bookId])['count'],
    'overdue' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status = 'overdue'", [$bookId])['count'],
    'returned' => $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status = 'returned'", [$bookId])['count']
];

$pageTitle = $book['title'] . ' - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
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
                    <h1>Book Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/books/index.php">Books</a></li>
                            <li class="breadcrumb-item active">View</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="edit.php?id=<?php echo $bookId; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Edit
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <!-- Book Information -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?php echo SITE_URL . '/uploads/book_covers/' . $book['cover_image']; ?>"
                                     class="img-fluid rounded mb-3" style="max-height: 400px;" alt="Book Cover">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center mb-3"
                                     style="height: 400px; font-size: 3rem;">
                                    <i class="bi bi-book"></i>
                                </div>
                            <?php endif; ?>

                            <h4 class="mb-3"><?php echo $book['title']; ?></h4>
                            <p class="text-muted">by <?php echo $book['author']; ?></p>

                            <div class="d-grid gap-2">
                                <?php echo getStatusBadge($book['status']); ?>
                                <div class="mt-2">
                                    <span class="badge bg-primary">Available: <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-graph-up me-2"></i>Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Borrowed:</span>
                                <strong><?php echo $stats['total_borrowed']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Currently Borrowed:</span>
                                <strong class="text-info"><?php echo $stats['currently_borrowed']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Overdue:</span>
                                <strong class="text-danger"><?php echo $stats['overdue']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Returned:</span>
                                <strong class="text-success"><?php echo $stats['returned']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Book Details -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle me-2"></i>Book Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">ISBN</th>
                                    <td><?php echo $book['isbn'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Title</th>
                                    <td><?php echo $book['title']; ?></td>
                                </tr>
                                <tr>
                                    <th>Author</th>
                                    <td><?php echo $book['author']; ?></td>
                                </tr>
                                <tr>
                                    <th>Publisher</th>
                                    <td><?php echo $book['publisher_name'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td><?php echo $book['category'] ? '<span class="badge bg-secondary">' . $book['category'] . '</span>' : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Publication Year</th>
                                    <td><?php echo $book['publication_year'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Pages</th>
                                    <td><?php echo $book['pages'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Language</th>
                                    <td><?php echo $book['language']; ?></td>
                                </tr>
                                <tr>
                                    <th>Location</th>
                                    <td><?php echo $book['location'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Price</th>
                                    <td><?php echo $book['price'] ? formatCurrency($book['price']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Quantity</th>
                                    <td><?php echo $book['quantity']; ?></td>
                                </tr>
                                <tr>
                                    <th>Available Quantity</th>
                                    <td>
                                        <span class="badge bg-<?php echo $book['available_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $book['available_quantity']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><?php echo getStatusBadge($book['status']); ?></td>
                                </tr>
                                <tr>
                                    <th>Added Date</th>
                                    <td><?php echo formatDateTime($book['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td><?php echo formatDateTime($book['updated_at']); ?></td>
                                </tr>
                            </table>

                            <?php if (!empty($book['description'])): ?>
                                <div class="mt-3">
                                    <h6>Description</h6>
                                    <p class="text-muted"><?php echo nl2br($book['description']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Borrowing History -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-clock-history me-2"></i>Borrowing History</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($borrowHistory): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Borrow Date</th>
                                                <th>Due Date</th>
                                                <th>Return Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($borrowHistory as $borrow): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?php echo $borrow['user_name']; ?></div>
                                                        <small class="text-muted"><?php echo $borrow['user_code']; ?> | <?php echo getRoleBadge($borrow['role']); ?></small>
                                                    </td>
                                                    <td><?php echo formatDate($borrow['borrow_date']); ?></td>
                                                    <td><?php echo formatDate($borrow['due_date']); ?></td>
                                                    <td><?php echo $borrow['return_date'] ? formatDate($borrow['return_date']) : '-'; ?></td>
                                                    <td><?php echo getStatusBadge($borrow['status']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h4>No borrowing history</h4>
                                    <p>This book has not been borrowed yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
