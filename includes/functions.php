<?php
/**
 * Helper Functions
 * Elara Space - Library Management System
 */

/**
 * Sanitize input data
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Flash messages
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        $class = $alertClass[$flash['type']] ?? 'alert-info';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo clean($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Display flash message with SweetAlert2
 */
function displaySweetAlert() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

        // Map type to SweetAlert icons
        $iconMap = [
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info'
        ];
        $icon = $iconMap[$type] ?? 'info';

        $title = [
            'success' => 'Success!',
            'error' => 'Oops...',
            'warning' => 'Warning!',
            'info' => 'Info'
        ];
        $titleText = $title[$type] ?? 'Notice';

        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '{$icon}',
                title: '{$titleText}',
                text: '{$message}',
                confirmButtonColor: '#3B82F6',
                timer: 3000,
                timerProgressBar: true
            });
        });
        </script>";
    }
}

/**
 * Date formatting
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd M Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

function indonesianDate($date) {
    if (empty($date)) return '-';

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);

    return "$day $month $year";
}

/**
 * Calculate days difference
 */
function daysDifference($date1, $date2 = null) {
    if ($date2 === null) $date2 = date('Y-m-d');

    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);

    return $interval->days * ($interval->invert ? -1 : 1);
}

/**
 * Number formatting
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

function formatCurrency($amount) {
    return 'Rp ' . formatNumber($amount, 0);
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedTypes = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large (max 5MB)'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateRandomString(16) . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Pagination
 */
function paginate($totalItems, $currentPage = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;

    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Previous
    if ($pagination['has_previous']) {
        $prevPage = $pagination['current_page'] - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $prevPage . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }

    // Pages
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }

    // Next
    if ($pagination['has_next']) {
        $nextPage = $pagination['current_page'] + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $nextPage . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $module, $description = null) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $query = "INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $params = [
            $userId,
            $action,
            $module,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        $db->execute($query, $params);
        return true;
    } catch (Exception $e) {
        error_log("Log Activity Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $query = "INSERT INTO notifications (user_id, title, message, type, link)
                  VALUES (?, ?, ?, ?, ?)";

        $params = [$userId, $title, $message, $type, $link];

        $db->execute($query, $params);
        return true;
    } catch (Exception $e) {
        error_log("Create Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $result = $db->fetchOne($query, [$userId]);

    return $result ? $result['count'] : 0;
}

/**
 * Redirect helper
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        setFlashMessage($type, $message);
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    static $settings = null;

    if ($settings === null) {
        $db = new Database();
        $conn = $db->getConnection();
        $results = $db->fetchAll("SELECT setting_key, setting_value FROM settings");

        $settings = [];
        if ($results) {
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Calculate due date
 */
function calculateDueDate($borrowDate = null, $days = null) {
    if ($borrowDate === null) {
        $borrowDate = date('Y-m-d');
    }
    if ($days === null) {
        $days = getSetting('borrow_duration_days', 14);
    }

    return date('Y-m-d', strtotime($borrowDate . ' + ' . $days . ' days'));
}

/**
 * Calculate fine amount
 */
function calculateFine($dueDate, $returnDate = null) {
    if ($returnDate === null) {
        $returnDate = date('Y-m-d');
    }

    $daysLate = daysDifference($returnDate, $dueDate);

    if ($daysLate <= 0) {
        return ['days_late' => 0, 'amount' => 0];
    }

    $finePerDay = getSetting('fine_per_day', 2000);
    $amount = $daysLate * $finePerDay;

    return ['days_late' => $daysLate, 'amount' => $amount];
}

/**
 * Get status badge
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'suspended' => '<span class="badge bg-danger">Suspended</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'borrowed' => '<span class="badge bg-primary">Borrowed</span>',
        'returned' => '<span class="badge bg-success">Returned</span>',
        'overdue' => '<span class="badge bg-danger">Overdue</span>',
        'available' => '<span class="badge bg-success">Available</span>',
        'unavailable' => '<span class="badge bg-secondary">Unavailable</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'unpaid' => '<span class="badge bg-danger">Unpaid</span>',
        'ordered' => '<span class="badge bg-info">Ordered</span>',
        'received' => '<span class="badge bg-success">Received</span>',
        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
    ];

    return $badges[strtolower($status)] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get role badge
 */
function getRoleBadge($role) {
    $badges = [
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin' => '<span class="badge bg-primary">Admin</span>',
        'dosen' => '<span class="badge bg-info">Dosen</span>',
        'staff' => '<span class="badge bg-warning">Staff</span>',
        'mahasiswa' => '<span class="badge bg-success">Mahasiswa</span>'
    ];

    return $badges[$role] ?? '<span class="badge bg-secondary">' . ucfirst($role) . '</span>';
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if ($text === null || $text === '') {
        return '';
    }
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . $suffix;
    }
    return $text;
}

/**
 * Check if book is available
 */
function isBookAvailable($bookId) {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT available_quantity FROM books WHERE id = ? AND status = 'available'";
    $result = $db->fetchOne($query, [$bookId]);

    return $result && $result['available_quantity'] > 0;
}

/**
 * Check if user can borrow
 */
function canUserBorrow($userId) {
    $db = new Database();
    $conn = $db->getConnection();

    // Check active borrowings
    $query = "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'";
    $result = $db->fetchOne($query, [$userId]);
    $activeBorrowings = $result['count'];

    $maxBorrowBooks = getSetting('max_borrow_books', 3);

    // Check unpaid fines
    $query = "SELECT COUNT(*) as count FROM fines WHERE user_id = ? AND status = 'unpaid'";
    $result = $db->fetchOne($query, [$userId]);
    $unpaidFines = $result['count'];

    return [
        'can_borrow' => $activeBorrowings < $maxBorrowBooks && $unpaidFines == 0,
        'active_borrowings' => $activeBorrowings,
        'max_allowed' => $maxBorrowBooks,
        'unpaid_fines' => $unpaidFines
    ];
}

// Language is now permanently set to Indonesian (Bahasa Indonesia)
