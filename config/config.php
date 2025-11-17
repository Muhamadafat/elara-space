<?php
/**
 * Main Configuration File
 * Elara Space - Library Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Site Configuration
define('SITE_NAME', 'Elara Space');
define('SITE_URL', 'http://localhost/elara-space');
define('BASE_PATH', dirname(__DIR__));

// Directory Paths
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('BOOK_COVER_DIR', UPLOAD_DIR . 'book_covers/');
define('PROFILE_PHOTO_DIR', UPLOAD_DIR . 'profiles/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
if (!file_exists(BOOK_COVER_DIR)) mkdir(BOOK_COVER_DIR, 0777, true);
if (!file_exists(PROFILE_PHOTO_DIR)) mkdir(PROFILE_PHOTO_DIR, 0777, true);

// Error Reporting
// For production: disable display_errors and log to file instead
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors on screen
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1); // Log errors to file
ini_set('error_log', BASE_PATH . '/logs/php-error.log'); // Error log file

// For development/debugging, uncomment these lines:
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

// Security Settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_LIFETIME', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 10);

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Include database connection
require_once BASE_PATH . '/config/database.php';

// Include helper functions
require_once BASE_PATH . '/includes/functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) return false;

    if (is_array($role)) {
        return in_array($_SESSION['user_role'], $role);
    }

    return $_SESSION['user_role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to access this page';
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole(['admin', 'super_admin'])) {
        $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT u.*, uni.name as university_name, uni.code as university_code
              FROM users u
              LEFT JOIN universities uni ON u.university_id = uni.id
              WHERE u.id = ?";

    return $db->fetchOne($query, [$_SESSION['user_id']]);
}

// Check session timeout
function checkSessionTimeout() {
    if (isLoggedIn()) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            session_unset();
            session_destroy();
            header('Location: ' . SITE_URL . '/login.php?timeout=1');
            exit();
        }
        $_SESSION['last_activity'] = time();
    }
}

// Call session timeout check
checkSessionTimeout();
