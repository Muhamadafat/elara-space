<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

if (!isset($_GET['id'])) {
    redirect(SITE_URL . '/admin/books/index.php', 'Invalid book ID', 'error');
}

$bookId = (int)$_GET['id'];
$book = $db->fetchOne("SELECT * FROM books WHERE id = ?", [$bookId]);

if (!$book) {
    redirect(SITE_URL . '/admin/books/index.php', 'Book not found', 'error');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'isbn' => clean($_POST['isbn'] ?? ''),
        'title' => clean($_POST['title'] ?? ''),
        'author' => clean($_POST['author'] ?? ''),
        'publisher_id' => clean($_POST['publisher_id'] ?? ''),
        'category' => clean($_POST['category'] ?? ''),
        'publication_year' => clean($_POST['publication_year'] ?? ''),
        'pages' => clean($_POST['pages'] ?? ''),
        'language' => clean($_POST['language'] ?? 'Indonesian'),
        'description' => clean($_POST['description'] ?? ''),
        'quantity' => (int)($_POST['quantity'] ?? 1),
        'available_quantity' => (int)($_POST['available_quantity'] ?? 0),
        'location' => clean($_POST['location'] ?? ''),
        'price' => clean($_POST['price'] ?? ''),
        'status' => clean($_POST['status'] ?? 'available')
    ];

    // Validation
    if (empty($formData['title']) || empty($formData['author'])) {
        $error = 'Title and Author are required';
    } elseif ($formData['quantity'] < 1) {
        $error = 'Quantity must be at least 1';
    } elseif ($formData['available_quantity'] > $formData['quantity']) {
        $error = 'Available quantity cannot exceed total quantity';
    } elseif ($formData['available_quantity'] < 0) {
        $error = 'Available quantity cannot be negative';
    } else {
        // Check if ISBN already exists (excluding current book)
        if (!empty($formData['isbn'])) {
            $checkIsbn = $db->fetchOne("SELECT id FROM books WHERE isbn = ? AND id != ?", [$formData['isbn'], $bookId]);
            if ($checkIsbn) {
                $error = 'ISBN already exists in the system';
            }
        }

        if (empty($error)) {
            // Handle cover image upload
            $coverImage = $book['cover_image'];
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['cover_image'], BOOK_COVER_DIR);
                if ($upload['success']) {
                    // Delete old cover if exists
                    if (!empty($book['cover_image']) && file_exists(BOOK_COVER_DIR . $book['cover_image'])) {
                        deleteFile(BOOK_COVER_DIR . $book['cover_image']);
                    }
                    $coverImage = $upload['filename'];
                } else {
                    $error = $upload['message'];
                }
            }

            if (empty($error)) {
                $query = "UPDATE books SET
                          isbn = ?, title = ?, author = ?, publisher_id = ?, category = ?,
                          publication_year = ?, pages = ?, language = ?, description = ?,
                          cover_image = ?, quantity = ?, available_quantity = ?, location = ?,
                          price = ?, status = ?
                          WHERE id = ?";

                $params = [
                    $formData['isbn'] ?: null,
                    $formData['title'],
                    $formData['author'],
                    $formData['publisher_id'] ?: null,
                    $formData['category'] ?: null,
                    $formData['publication_year'] ?: null,
                    $formData['pages'] ?: null,
                    $formData['language'],
                    $formData['description'] ?: null,
                    $coverImage ?: null,
                    $formData['quantity'],
                    $formData['available_quantity'],
                    $formData['location'] ?: null,
                    $formData['price'] ?: null,
                    $formData['status'],
                    $bookId
                ];

                if ($db->execute($query, $params)) {
                    logActivity($currentUser['id'], 'edit_book', 'books', 'Updated book: ' . $formData['title']);

                    redirect(SITE_URL . '/admin/books/view.php?id=' . $bookId, 'Book updated successfully!', 'success');
                } else {
                    $error = 'Failed to update book. Please try again.';
                }
            }
        }
    }

    // Update book array with form data if error
    if (!empty($error)) {
        $book = array_merge($book, $formData);
    }
}

// Get publishers
$publishers = $db->fetchAll("SELECT id, name FROM publishers ORDER BY name");

// Get existing categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");

// Check borrowed count
$borrowedCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')",
    [$bookId]
)['count'];

$pageTitle = 'Edit Book - ' . SITE_NAME;
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
                    <h1>Edit Book</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/books/index.php">Books</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($borrowedCount > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    This book currently has <strong><?php echo $borrowedCount; ?></strong> active borrowing(s).
                    Be careful when updating quantity and availability.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-book me-2"></i>Book Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Book Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" value="<?php echo $book['title']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Author <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="author" value="<?php echo $book['author']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" value="<?php echo $book['isbn']; ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publisher</label>
                                        <select class="form-select" name="publisher_id">
                                            <option value="">Select Publisher</option>
                                            <?php foreach ($publishers as $pub): ?>
                                                <option value="<?php echo $pub['id']; ?>" <?php echo $book['publisher_id'] == $pub['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $pub['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <input type="text" class="form-control" name="category" value="<?php echo $book['category']; ?>" list="categoryList">
                                        <datalist id="categoryList">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category']; ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" name="publication_year" value="<?php echo $book['publication_year']; ?>"
                                               min="1900" max="<?php echo date('Y'); ?>">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pages</label>
                                        <input type="number" class="form-control" name="pages" value="<?php echo $book['pages']; ?>" min="1">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Language</label>
                                        <select class="form-select" name="language">
                                            <option value="Indonesian" <?php echo $book['language'] == 'Indonesian' ? 'selected' : ''; ?>>Indonesian</option>
                                            <option value="English" <?php echo $book['language'] == 'English' ? 'selected' : ''; ?>>English</option>
                                            <option value="Other" <?php echo $book['language'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4"><?php echo $book['description']; ?></textarea>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Total Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="quantity" value="<?php echo $book['quantity']; ?>" min="1" required>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Available Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="available_quantity" value="<?php echo $book['available_quantity']; ?>"
                                               min="0" max="<?php echo $book['quantity']; ?>" required>
                                        <small class="text-muted">Currently borrowed: <?php echo $borrowedCount; ?></small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Location/Shelf</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $book['location']; ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Price (Rp)</label>
                                        <input type="number" class="form-control" name="price" value="<?php echo $book['price']; ?>" min="0" step="1000">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="available" <?php echo $book['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="unavailable" <?php echo $book['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                            <option value="maintenance" <?php echo $book['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo SITE_URL; ?>/admin/books/view.php?id=<?php echo $bookId; ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Update Book
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-image me-2"></i>Book Cover</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload New Cover</label>
                                <input type="file" class="form-control" name="cover_image" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted">Leave empty to keep current cover</small>
                            </div>

                            <div class="text-center">
                                <img id="coverPreview"
                                     src="<?php echo !empty($book['cover_image']) ? SITE_URL . '/uploads/book_covers/' . $book['cover_image'] : SITE_URL . '/assets/images/no-cover.png'; ?>"
                                     class="img-fluid rounded" style="max-height: 300px;" alt="Cover Preview">
                            </div>

                            <?php if (!empty($book['cover_image'])): ?>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">Current cover: <?php echo $book['cover_image']; ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('coverPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
