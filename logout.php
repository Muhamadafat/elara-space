<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'];

    // Log activity
    logActivity($userId, 'logout', 'auth', 'User logged out');

    // Destroy session
    session_unset();
    session_destroy();

    // Redirect to homepage
    redirect(SITE_URL . '/index.php', 'Anda telah logout. Sampai jumpa lagi!', 'success');
} else {
    redirect(SITE_URL . '/index.php');
}
