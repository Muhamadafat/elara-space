<?php
/**
 * Auto Fine Calculator
 * This script calculates fines for overdue books automatically
 * Can be run manually by admin or as a cron job
 */

require_once '../../config/config.php';

// Check if running from CLI (cron job) or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // If accessed from web, require admin login
    requireLogin();
    requireRole(['admin', 'super_admin']);
}

$db = new Database();
$results = [
    'processed' => 0,
    'fines_created' => 0,
    'fines_updated' => 0,
    'total_fine_amount' => 0,
    'errors' => []
];

try {
    // Get fine per day setting
    $finePerDaySetting = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'");
    $finePerDay = $finePerDaySetting ? (int)$finePerDaySetting['setting_value'] : 2000;

    // Get all overdue borrowings
    $overdueBooks = $db->fetchAll("
        SELECT b.*, u.name AS user_name, u.email, bk.title AS book_title
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.status IN ('borrowed', 'overdue')
        AND b.due_date < CURDATE()
        AND b.return_date IS NULL
    ");

    foreach ($overdueBooks as $borrowing) {
        try {
            $results['processed']++;

            // Calculate days late
            $dueDate = new DateTime($borrowing['due_date']);
            $today = new DateTime();
            $daysLate = $today->diff($dueDate)->days;

            // Calculate fine amount
            $fineAmount = $daysLate * $finePerDay;

            // Update borrowing status to overdue
            $db->query("
                UPDATE borrowings
                SET status = 'overdue'
                WHERE id = ?
            ", [$borrowing['id']]);

            // Check if fine already exists
            $existingFine = $db->fetchOne("
                SELECT * FROM fines
                WHERE borrowing_id = ?
            ", [$borrowing['id']]);

            if ($existingFine) {
                // Update existing fine
                $db->query("
                    UPDATE fines
                    SET amount = ?, days_late = ?
                    WHERE borrowing_id = ?
                ", [$fineAmount, $daysLate, $borrowing['id']]);
                $results['fines_updated']++;
            } else {
                // Create new fine
                $db->query("
                    INSERT INTO fines (borrowing_id, user_id, amount, days_late, status)
                    VALUES (?, ?, ?, ?, 'unpaid')
                ", [$borrowing['id'], $borrowing['user_id'], $fineAmount, $daysLate]);
                $results['fines_created']++;

                // Create notification for user
                $db->query("
                    INSERT INTO notifications (user_id, title, message, type, link)
                    VALUES (?, ?, ?, 'warning', '/user/fines.php')
                ", [
                    $borrowing['user_id'],
                    'Denda Keterlambatan',
                    "Anda terkena denda sebesar " . formatCurrency($fineAmount) . " untuk buku '{$borrowing['book_title']}' yang terlambat {$daysLate} hari."
                ]);
            }

            $results['total_fine_amount'] += $fineAmount;

        } catch (Exception $e) {
            $results['errors'][] = "Error processing borrowing ID {$borrowing['id']}: " . $e->getMessage();
        }
    }

    // Log activity
    if (!$isCLI && isset($_SESSION['user'])) {
        $currentUser = getCurrentUser();
        $db->query("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, 'auto_calculate_fines', 'system', ?, ?)
        ", [
            $currentUser['id'],
            "Auto-calculate fines: {$results['processed']} processed, {$results['fines_created']} created, {$results['fines_updated']} updated",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

} catch (Exception $e) {
    $results['errors'][] = "Fatal error: " . $e->getMessage();
}

// Output results
if ($isCLI) {
    // CLI output
    echo "=== Auto Fine Calculator Results ===\n";
    echo "Processed: {$results['processed']}\n";
    echo "Fines Created: {$results['fines_created']}\n";
    echo "Fines Updated: {$results['fines_updated']}\n";
    echo "Total Fine Amount: Rp " . number_format($results['total_fine_amount']) . "\n";
    if (!empty($results['errors'])) {
        echo "Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    echo "===================================\n";
} else {
    // Web output - redirect with message
    $message = "Fine calculation completed! Processed: {$results['processed']}, Created: {$results['fines_created']}, Updated: {$results['fines_updated']}";
    if (!empty($results['errors'])) {
        $message .= " (with " . count($results['errors']) . " errors)";
    }
    redirect(SITE_URL . '/admin/system/fines.php', $message, empty($results['errors']) ? 'success' : 'warning');
}
?>
