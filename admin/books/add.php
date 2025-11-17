<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$formData = [
    'isbn' => '',
    'title' => '',
    'author' => '',
    'publisher_id' => '',
    'category' => '',
    'publication_year' => '',
    'pages' => '',
    'language' => 'Indonesian',
    'description' => '',
    'quantity' => 1,
    'location' => '',
    'price' => '',
    'status' => 'available'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['isbn'] = clean($_POST['isbn'] ?? '');
    $formData['title'] = clean($_POST['title'] ?? '');
    $formData['author'] = clean($_POST['author'] ?? '');
    $formData['publisher_id'] = clean($_POST['publisher_id'] ?? '');
    $formData['category'] = clean($_POST['category'] ?? '');
    $formData['publication_year'] = clean($_POST['publication_year'] ?? '');
    $formData['pages'] = clean($_POST['pages'] ?? '');
    $formData['language'] = clean($_POST['language'] ?? 'Indonesian');
    $formData['description'] = clean($_POST['description'] ?? '');
    $formData['quantity'] = (int)($_POST['quantity'] ?? 1);
    $formData['location'] = clean($_POST['location'] ?? '');
    $formData['price'] = clean($_POST['price'] ?? '');
    $formData['status'] = clean($_POST['status'] ?? 'available');

    // Validation
    if (empty($formData['title']) || empty($formData['author'])) {
        $error = 'Title and Author are required';
    } elseif ($formData['quantity'] < 1) {
        $error = 'Quantity must be at least 1';
    } else {
        // Check if ISBN already exists
        if (!empty($formData['isbn'])) {
            $checkIsbn = $db->fetchOne("SELECT id FROM books WHERE isbn = ?", [$formData['isbn']]);
            if ($checkIsbn) {
                $error = 'ISBN already exists in the system';
            }
        }

        if (empty($error)) {
            // Handle cover image upload
            $coverImage = '';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['cover_image'], BOOK_COVER_DIR);
                if ($upload['success']) {
                    $coverImage = $upload['filename'];
                } else {
                    $error = $upload['message'];
                }
            }

            if (empty($error)) {
                $query = "INSERT INTO books (isbn, title, author, publisher_id, category, publication_year,
                          pages, language, description, cover_image, quantity, available_quantity, location,
                          price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
                    $formData['quantity'], // available_quantity same as quantity initially
                    $formData['location'] ?: null,
                    $formData['price'] ?: null,
                    $formData['status']
                ];

                if ($db->execute($query, $params)) {
                    $bookId = $db->lastInsertId();
                    logActivity($currentUser['id'], 'add_book', 'books', 'Added new book: ' . $formData['title']);

                    redirect(SITE_URL . '/admin/books/view.php?id=' . $bookId, 'Book added successfully!', 'success');
                } else {
                    $error = 'Failed to add book. Please try again.';
                }
            }
        }
    }
}

// Get publishers
$publishers = $db->fetchAll("SELECT id, name FROM publishers WHERE partnership_status = 'active' ORDER BY name");

// Get existing categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");

$pageTitle = 'Add New Book - ' . SITE_NAME;
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
                    <h1>Add New Book</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/books/index.php">Books</a></li>
                            <li class="breadcrumb-item active">Add New</li>
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
                                        <input type="text" class="form-control" name="title" value="<?php echo $formData['title']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Author <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="author" value="<?php echo $formData['author']; ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" value="<?php echo $formData['isbn']; ?>" placeholder="978-0-123456-78-9">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publisher</label>
                                        <select class="form-select" name="publisher_id">
                                            <option value="">Select Publisher</option>
                                            <?php foreach ($publishers as $pub): ?>
                                                <option value="<?php echo $pub['id']; ?>" <?php echo $formData['publisher_id'] == $pub['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $pub['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <input type="text" class="form-control" name="category" value="<?php echo $formData['category']; ?>"
                                               list="categoryList" placeholder="e.g., Economics, Management">
                                        <datalist id="categoryList">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category']; ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" name="publication_year" value="<?php echo $formData['publication_year']; ?>"
                                               min="1900" max="<?php echo date('Y'); ?>">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pages</label>
                                        <input type="number" class="form-control" name="pages" value="<?php echo $formData['pages']; ?>" min="1">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Language</label>
                                        <select class="form-select" name="language">
                                            <option value="Indonesian" <?php echo $formData['language'] == 'Indonesian' ? 'selected' : ''; ?>>Indonesian</option>
                                            <option value="English" <?php echo $formData['language'] == 'English' ? 'selected' : ''; ?>>English</option>
                                            <option value="Other" <?php echo $formData['language'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="4" placeholder="Brief description about the book..."><?php echo $formData['description']; ?></textarea>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="quantity" value="<?php echo $formData['quantity']; ?>" min="1" required>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Location/Shelf</label>
                                        <input type="text" class="form-control" name="location" value="<?php echo $formData['location']; ?>" placeholder="e.g., A-12">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Price (Rp)</label>
                                        <input type="number" class="form-control" name="price" value="<?php echo $formData['price']; ?>" min="0" step="1000">
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="available" <?php echo $formData['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="unavailable" <?php echo $formData['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                            <option value="maintenance" <?php echo $formData['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo SITE_URL; ?>/admin/books/index.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Save Book
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
                                <label class="form-label">Upload Cover Image</label>
                                <input type="file" class="form-control" name="cover_image" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted">Max size: 5MB. Formats: JPG, PNG</small>
                            </div>

                            <div class="text-center">
                                <img id="coverPreview" src="<?php echo SITE_URL; ?>/assets/images/no-cover.png"
                                     class="img-fluid rounded" style="max-height: 300px;" alt="Cover Preview">
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle me-2"></i>Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-0">
                                <small>
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Fill in all required fields marked with <span class="text-danger">*</span>
                                </small>
                            </p>
                            <hr>
                            <p class="text-muted mb-0">
                                <small>
                                    <i class="bi bi-lightbulb me-1"></i>
                                    The available quantity will be automatically set to match the total quantity
                                </small>
                            </p>
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
