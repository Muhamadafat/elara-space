<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Sidebar Brand -->
    <a class="sidebar-brand" href="<?php echo SITE_URL; ?>/" title="Kembali ke Beranda">
        <i class="bi bi-book-half"></i>
        <div><?php echo SITE_NAME; ?></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo SITE_URL; ?>/">
                <i class="bi bi-house-fill"></i>
                <span>Beranda</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/index.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Perpustakaan</div>

        <!-- Nav Item - Books -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/books.php">
                <i class="bi bi-book"></i>
                <span>Telusuri Buku</span>
            </a>
        </li>

        <!-- Nav Item - My Borrowings -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'borrowing.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/borrowing.php">
                <i class="bi bi-clock-history"></i>
                <span>Peminjaman Saya</span>
                <?php
                try {
                    $db = new Database();
                    $overdueCount = $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'overdue'", [$_SESSION['user_id']]);
                    if ($overdueCount && $overdueCount['count'] > 0):
                ?>
                    <span class="badge bg-danger"><?php echo $overdueCount['count']; ?></span>
                <?php
                    endif;
                } catch (Exception $e) {
                    // Table doesn't exist yet
                }
                ?>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Layanan</div>

        <!-- Nav Item - Request Book -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'request-book.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/request-book.php">
                <i class="bi bi-cart-plus"></i>
                <span>Request Buku Baru</span>
            </a>
        </li>

        <!-- Nav Item - My Requests -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/requests.php">
                <i class="bi bi-list-check"></i>
                <span>Request Saya</span>
                <?php
                try {
                    $pendingCount = $db->fetchOne("SELECT COUNT(*) as count FROM book_requests WHERE user_id = ? AND status = 'pending'", [$_SESSION['user_id']]);
                    if ($pendingCount && $pendingCount['count'] > 0):
                ?>
                    <span class="badge bg-warning"><?php echo $pendingCount['count']; ?></span>
                <?php
                    endif;
                } catch (Exception $e) {
                    // Table doesn't exist yet
                }
                ?>
            </a>
        </li>

        <!-- Nav Item - My Fines -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fines.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/fines.php">
                <i class="bi bi-currency-dollar"></i>
                <span>Denda Saya</span>
                <?php
                try {
                    $unpaidFines = $db->fetchOne("SELECT COUNT(*) as count FROM fines WHERE user_id = ? AND status = 'unpaid'", [$_SESSION['user_id']]);
                    if ($unpaidFines && $unpaidFines['count'] > 0):
                ?>
                    <span class="badge bg-danger"><?php echo $unpaidFines['count']; ?></span>
                <?php
                    endif;
                } catch (Exception $e) {
                    // Table doesn't exist yet
                }
                ?>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Akun</div>

        <!-- Nav Item - Profile -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/profile.php">
                <i class="bi bi-person"></i>
                <span>Profil Saya</span>
            </a>
        </li>

        <!-- Nav Item - Notifications -->
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/user/notifications.php">
                <i class="bi bi-bell"></i>
                <span>Notifikasi</span>
                <?php
                $unreadNotif = getUnreadNotificationCount($_SESSION['user_id']);
                if ($unreadNotif > 0):
                ?>
                    <span class="badge bg-danger"><?php echo $unreadNotif; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Nav Item - Logout -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php"
               onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </a>
        </li>
    </ul>
</div>
<!-- End of Sidebar -->
