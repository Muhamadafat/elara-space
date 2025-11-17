<?php
require_once '../../config/config.php';
requireAdmin();

$currentUser = getCurrentUser();
$db = new Database();

$error = '';
$formData = [
    'user_id' => '',
    'book_id' => '',
    'borrow_date' => date('Y-m-d'),
    'duration_days' => getSetting('borrow_duration_days', 14),
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['user_id'] = (int)($_POST['user_id'] ?? 0);
    $formData['book_id'] = (int)($_POST['book_id'] ?? 0);
    $formData['borrow_date'] = clean($_POST['borrow_date'] ?? '');
    $formData['duration_days'] = (int)($_POST['duration_days'] ?? 14);
    $formData['notes'] = clean($_POST['notes'] ?? '');

    // Validation
    if (empty($formData['user_id']) || empty($formData['book_id'])) {
        $error = 'Please select both user and book';
    } elseif (empty($formData['borrow_date'])) {
        $error = 'Borrow date is required';
    } elseif ($formData['duration_days'] < 1) {
        $error = 'Duration must be at least 1 day';
    } else {
        // Check if user can borrow
        $userCheck = canUserBorrow($formData['user_id']);
        if (!$userCheck['can_borrow']) {
            if ($userCheck['unpaid_fines'] > 0) {
                $error = 'User has unpaid fines. Please clear fines before borrowing.';
            } else {
                $error = 'User has reached maximum borrowing limit (' . $userCheck['max_allowed'] . ' books)';
            }
        } else {
            // Check if book is available
            if (!isBookAvailable($formData['book_id'])) {
                $error = 'Book is not available for borrowing';
            } else {
                // Calculate due date
                $dueDate = calculateDueDate($formData['borrow_date'], $formData['duration_days']);

                // Insert borrowing record
                $query = "INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status, notes, created_by)
                          VALUES (?, ?, ?, ?, 'borrowed', ?, ?)";

                $params = [
                    $formData['user_id'],
                    $formData['book_id'],
                    $formData['borrow_date'],
                    $dueDate,
                    $formData['notes'],
                    $currentUser['id']
                ];

                if ($db->execute($query, $params)) {
                    $borrowingId = $db->lastInsertId();

                    // Update book available quantity
                    $db->execute(
                        "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?",
                        [$formData['book_id']]
                    );

                    // Get book and user info for notification
                    $book = $db->fetchOne("SELECT title FROM books WHERE id = ?", [$formData['book_id']]);
                    $user = $db->fetchOne("SELECT name FROM users WHERE id = ?", [$formData['user_id']]);

                    // Create notification for user
                    createNotification(
                        $formData['user_id'],
                        'Book Borrowed',
                        'You have borrowed "' . $book['title'] . '". Due date: ' . formatDate($dueDate),
                        'success',
                        SITE_URL . '/user/borrowing/view.php?id=' . $borrowingId
                    );

                    logActivity($currentUser['id'], 'add_borrowing', 'borrowing', 'Created borrowing for user: ' . $user['name']);

                    redirect(SITE_URL . '/admin/borrowing/view.php?id=' . $borrowingId, 'Borrowing created successfully!', 'success');
                } else {
                    $error = 'Failed to create borrowing. Please try again.';
                }
            }
        }
    }
}

// Get users (exclude admins)
$users = $db->fetchAll(
    "SELECT u.id, u.name, u.user_code, u.email, u.role, uni.code as university_code
     FROM users u
     JOIN universities uni ON u.university_id = uni.id
     WHERE u.role IN ('mahasiswa', 'dosen', 'staff') AND u.status = 'active'
     ORDER BY u.name"
);

// Get available books
$books = $db->fetchAll(
    "SELECT id, title, author, isbn, available_quantity
     FROM books
     WHERE status = 'available' AND available_quantity > 0
     ORDER BY title"
);

$pageTitle = 'New Borrowing - ' . SITE_NAME;
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
    <style>
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>New Borrowing</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php">Borrowing</a></li>
                            <li class="breadcrumb-item active">New</li>
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
                            <h6><i class="bi bi-plus-circle me-2"></i>Borrowing Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="borrowingForm">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Select User <span class="text-danger">*</span></label>
                                        <select class="form-select" name="user_id" id="userId" required onchange="checkUserEligibility()">
                                            <option value="">Select User</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo $user['name']; ?>"
                                                        data-code="<?php echo $user['user_code']; ?>"
                                                        data-university="<?php echo $user['university_code']; ?>"
                                                        data-role="<?php echo $user['role']; ?>"
                                                        <?php echo $formData['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $user['name'] . ' (' . $user['user_code'] . ') - ' . $user['university_code']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="userInfo" class="mt-2"></div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Select Book <span class="text-danger">*</span></label>
                                        <select class="form-select" name="book_id" id="bookId" required onchange="showBookInfo()">
                                            <option value="">Select Book</option>
                                            <?php foreach ($books as $book): ?>
                                                <option value="<?php echo $book['id']; ?>"
                                                        data-title="<?php echo $book['title']; ?>"
                                                        data-author="<?php echo $book['author']; ?>"
                                                        data-isbn="<?php echo $book['isbn']; ?>"
                                                        data-available="<?php echo $book['available_quantity']; ?>"
                                                        <?php echo $formData['book_id'] == $book['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $book['title'] . ' - ' . $book['author'] . ' (Available: ' . $book['available_quantity'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="bookInfo" class="mt-2"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Borrow Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="borrow_date" value="<?php echo $formData['borrow_date']; ?>"
                                               max="<?php echo date('Y-m-d'); ?>" required onchange="calculateDueDate()">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Duration (Days) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="duration_days" id="durationDays"
                                               value="<?php echo $formData['duration_days']; ?>" min="1" max="90" required onchange="calculateDueDate()">
                                        <small class="text-muted">Default: <?php echo getSetting('borrow_duration_days', 14); ?> days</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="text" class="form-control" id="dueDateDisplay" readonly>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes..."><?php echo $formData['notes']; ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo SITE_URL; ?>/admin/borrowing/index.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Create Borrowing
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle me-2"></i>Borrowing Rules</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Maximum books per user: <strong><?php echo getSetting('max_borrow_books', 3); ?></strong>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Default duration: <strong><?php echo getSetting('borrow_duration_days', 14); ?> days</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Maximum extensions: <strong><?php echo getSetting('max_extension', 1); ?></strong>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Extension duration: <strong><?php echo getSetting('extension_duration_days', 7); ?> days</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Fine per day: <strong><?php echo formatCurrency(getSetting('fine_per_day', 2000)); ?></strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">
                                <small>Users with unpaid fines cannot borrow books.</small>
                            </p>
                            <p class="text-muted mb-0">
                                <small>Users who reach the maximum borrowing limit cannot borrow more books.</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateDueDate() {
            const borrowDate = document.querySelector('input[name="borrow_date"]').value;
            const duration = parseInt(document.getElementById('durationDays').value);

            if (borrowDate && duration > 0) {
                const borrow = new Date(borrowDate);
                borrow.setDate(borrow.getDate() + duration);

                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('dueDateDisplay').value = borrow.toLocaleDateString('id-ID', options);
            }
        }

        function showBookInfo() {
            const select = document.getElementById('bookId');
            const option = select.options[select.selectedIndex];

            if (option.value) {
                const info = `
                    <div class="alert alert-info">
                        <strong>${option.dataset.title}</strong><br>
                        Author: ${option.dataset.author}<br>
                        ISBN: ${option.dataset.isbn || '-'}<br>
                        Available: ${option.dataset.available} copies
                    </div>
                `;
                document.getElementById('bookInfo').innerHTML = info;
            } else {
                document.getElementById('bookInfo').innerHTML = '';
            }
        }

        async function checkUserEligibility() {
            const userId = document.getElementById('userId').value;

            if (!userId) {
                document.getElementById('userInfo').innerHTML = '';
                return;
            }

            // In a real implementation, this would be an AJAX call
            // For now, we'll just show user info
            const select = document.getElementById('userId');
            const option = select.options[select.selectedIndex];

            const info = `
                <div class="alert alert-info">
                    <strong>${option.dataset.name}</strong><br>
                    User Code: ${option.dataset.code}<br>
                    University: ${option.dataset.university}<br>
                    Role: ${option.dataset.role}
                </div>
            `;
            document.getElementById('userInfo').innerHTML = info;
        }

        // Initialize
        calculateDueDate();
        showBookInfo();
        checkUserEligibility();
    </script>
</body>
</html>
