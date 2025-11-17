<?php
require_once '../../config/config.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = new Database();

// Handle add/edit book
if (isset($_POST['save_book'])) {
    $bookId = $_POST['book_id'] ?? null;
    $publisherId = $_POST['publisher_id'] ?: null;
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'];
    $partnerStore = $_POST['partner_store'];
    $commissionRate = $_POST['commission_rate'];
    $estimatedDelivery = $_POST['estimated_delivery'];
    $stock = $_POST['stock'];
    $status = $_POST['status'];

    try {
        if ($bookId) {
            // Update existing book
            $db->query("
                UPDATE marketplace_books
                SET publisher_id = ?, isbn = ?, title = ?, author = ?, category = ?,
                    description = ?, price = ?, partner_store = ?, commission_rate = ?,
                    estimated_delivery = ?, stock = ?, status = ?
                WHERE id = ?
            ", [$publisherId, $isbn, $title, $author, $category, $description, $price,
                $partnerStore, $commissionRate, $estimatedDelivery, $stock, $status, $bookId]);
            $message = 'Buku berhasil diupdate!';
        } else {
            // Add new book
            $db->query("
                INSERT INTO marketplace_books
                (publisher_id, isbn, title, author, category, description, price, partner_store,
                 commission_rate, estimated_delivery, stock, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$publisherId, $isbn, $title, $author, $category, $description, $price,
                $partnerStore, $commissionRate, $estimatedDelivery, $stock, $status]);
            $message = 'Buku berhasil ditambahkan!';
        }

        redirect(SITE_URL . '/admin/marketplace/books.php', $message, 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/marketplace/books.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle delete
if (isset($_POST['delete_book'])) {
    $bookId = (int)$_POST['book_id'];

    try {
        $db->query("DELETE FROM marketplace_books WHERE id = ?", [$bookId]);
        redirect(SITE_URL . '/admin/marketplace/books.php', 'Buku berhasil dihapus!', 'success');
    } catch (Exception $e) {
        redirect(SITE_URL . '/admin/marketplace/books.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get all marketplace books
try {
    $books = $db->fetchAll("
        SELECT mb.*, p.name AS publisher_name
        FROM marketplace_books mb
        LEFT JOIN publishers p ON mb.publisher_id = p.id
        ORDER BY mb.created_at DESC
    ");
} catch (Exception $e) {
    $books = [];
}

// Get publishers for dropdown
try {
    $publishers = $db->fetchAll("SELECT id, name FROM publishers ORDER BY name");
} catch (Exception $e) {
    $publishers = [];
}

$pageTitle = 'Marketplace Books - ' . SITE_NAME;
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
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-1">Marketplace Books</h1>
                    <p class="text-muted mb-0">Kelola buku marketplace dari toko partner</p>
                </div>
                <button class="btn btn-primary" onclick="showAddEditModal()">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Buku
                </button>
            </div>

            <?php displaySweetAlert(); ?>

            <!-- Books Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Buku</th>
                                    <th>ISBN</th>
                                    <th>Partner Store</th>
                                    <th>Harga</th>
                                    <th>Komisi</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Belum ada buku marketplace</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?php echo $book['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($book['partner_store']); ?></td>
                                            <td><?php echo formatCurrency($book['price']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $book['commission_rate']; ?>% (<?php echo formatCurrency($book['price'] * $book['commission_rate'] / 100); ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $book['stock'] > 0 ? 'primary' : 'danger'; ?>">
                                                    <?php echo $book['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'available' => 'success',
                                                    'unavailable' => 'danger',
                                                    'discontinued' => 'secondary'
                                                ];
                                                $badge = $statusBadges[$book['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>">
                                                    <?php echo strtoupper($book['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick='showAddEditModal(<?php echo json_encode($book, JSON_HEX_APOS); ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/alerts.js"></script>
    <script>
        const publishers = <?php echo json_encode($publishers); ?>;

        function showAddEditModal(book = null) {
            const isEdit = book !== null;
            const title = isEdit ? 'Edit Buku' : 'Tambah Buku Baru';

            let publishersOptions = '<option value="">-- Pilih Publisher (Opsional) --</option>';
            publishers.forEach(p => {
                const selected = isEdit && book.publisher_id == p.id ? 'selected' : '';
                publishersOptions += `<option value="${p.id}" ${selected}>${p.name}</option>`;
            });

            Swal.fire({
                title: title,
                html: `
                    <form id="bookForm" class="text-start">
                        <input type="hidden" name="book_id" value="${isEdit ? book.id : ''}">

                        <div class="mb-3">
                            <label class="form-label">Publisher</label>
                            <select class="form-select" name="publisher_id">
                                ${publishersOptions}
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn" value="${isEdit ? book.isbn || '' : ''}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <input type="text" class="form-control" name="category" value="${isEdit ? book.category || '' : ''}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="${isEdit ? book.title : ''}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Penulis <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="author" value="${isEdit ? book.author : ''}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3">${isEdit ? (book.description || '') : ''}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Partner Store <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="partner_store" value="${isEdit ? book.partner_store : ''}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estimasi Pengiriman <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="estimated_delivery" value="${isEdit ? book.estimated_delivery : '3-5 hari kerja'}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" value="${isEdit ? book.price : ''}" required min="0" step="1000">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Komisi (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="commission_rate" value="${isEdit ? book.commission_rate : '10'}" required min="0" max="100" step="0.1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stock" value="${isEdit ? book.stock : '0'}" required min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="available" ${isEdit && book.status === 'available' ? 'selected' : ''}>Available</option>
                                <option value="unavailable" ${isEdit && book.status === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                                <option value="discontinued" ${isEdit && book.status === 'discontinued' ? 'selected' : ''}>Discontinued</option>
                            </select>
                        </div>
                    </form>
                `,
                width: '800px',
                showCancelButton: true,
                confirmButtonText: isEdit ? 'Update' : 'Tambah',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const form = document.getElementById('bookForm');
                    const formData = new FormData(form);
                    return Object.fromEntries(formData);
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    let formHtml = '<input type="hidden" name="save_book" value="1">';
                    for (const [key, value] of Object.entries(result.value)) {
                        formHtml += `<input type="hidden" name="${key}" value="${value}">`;
                    }
                    form.innerHTML = formHtml;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function deleteBook(bookId, title) {
            Swal.fire({
                title: 'Hapus Buku?',
                html: `Anda yakin ingin menghapus buku:<br><strong>${title}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="delete_book" value="1">
                        <input type="hidden" name="book_id" value="${bookId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
