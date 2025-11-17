<?php
require_once '../../config/config.php';
requireLogin();

$currentUser = getCurrentUser();
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/browse.php');
}

$bookId = (int)($_POST['book_id'] ?? 0);

if (!$bookId) {
    redirect(SITE_URL . '/browse.php', 'Buku tidak valid', 'error');
}

// Get book data
$book = $db->fetchOne("SELECT * FROM books WHERE id = ?", [$bookId]);

if (!$book) {
    redirect(SITE_URL . '/browse.php', 'Buku tidak ditemukan', 'error');
}

// Check if book is available
if ($book['available_quantity'] <= 0) {
    redirect(SITE_URL . '/user/books/view.php?id=' . $bookId, 'Buku sedang tidak tersedia', 'error');
}

// Check maximum borrow limit
$maxBorrowBooks = (int)getSetting('max_borrow_books', 3);
$currentBorrowings = $db->fetchOne(
    "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status IN ('borrowed', 'overdue')",
    [$currentUser['id']]
)['count'];

if ($currentBorrowings >= $maxBorrowBooks) {
    redirect(SITE_URL . '/user/books/view.php?id=' . $bookId,
             'Anda sudah mencapai batas maksimal peminjaman (' . $maxBorrowBooks . ' buku)',
             'error');
}

// Check if user already borrowed this book and not returned yet
$alreadyBorrowed = $db->fetchOne(
    "SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue')",
    [$currentUser['id'], $bookId]
);

if ($alreadyBorrowed) {
    redirect(SITE_URL . '/user/books/view.php?id=' . $bookId,
             'Anda sudah meminjam buku ini dan belum mengembalikannya',
             'error');
}

// Check for unpaid fines
$unpaidFines = $db->fetchOne(
    "SELECT COUNT(*) as count FROM fines WHERE user_id = ? AND status = 'unpaid'",
    [$currentUser['id']]
)['count'];

if ($unpaidFines > 0) {
    redirect(SITE_URL . '/user/borrowing.php',
             'Anda memiliki denda yang belum dibayar. Mohon lunasi terlebih dahulu',
             'error');
}

// Calculate due date
$borrowDurationDays = (int)getSetting('borrow_duration_days', 14);
$borrowDate = date('Y-m-d');
$dueDate = date('Y-m-d', strtotime("+$borrowDurationDays days"));

// Start transaction
$db->beginTransaction();

try {
    // Insert borrowing record
    $insertBorrowing = $db->execute(
        "INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status, created_at)
         VALUES (?, ?, ?, ?, 'borrowed', NOW())",
        [$currentUser['id'], $bookId, $borrowDate, $dueDate]
    );

    if (!$insertBorrowing) {
        throw new Exception('Gagal membuat record peminjaman');
    }

    // Update book availability
    $updateBook = $db->execute(
        "UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ? AND available_quantity > 0",
        [$bookId]
    );

    if (!$updateBook) {
        throw new Exception('Gagal mengupdate ketersediaan buku');
    }

    // Log activity
    logActivity($currentUser['id'], 'borrow_book', 'borrowings', 'Borrowed book: ' . $book['title']);

    // Create notification for admin
    createNotification(
        null, // for all admins
        'new_borrowing',
        'Peminjaman Baru',
        $currentUser['name'] . ' meminjam buku: ' . $book['title'],
        SITE_URL . '/admin/borrowing/index.php'
    );

    $db->commit();

    redirect(SITE_URL . '/user/borrowing.php',
             'Buku berhasil dipinjam! Harap dikembalikan sebelum tanggal ' . formatDate($dueDate),
             'success');

} catch (Exception $e) {
    $db->rollback();
    redirect(SITE_URL . '/user/books/view.php?id=' . $bookId,
             'Gagal meminjam buku: ' . $e->getMessage(),
             'error');
}
