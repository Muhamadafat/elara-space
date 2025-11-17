<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Sidebar Brand -->
    <a class="sidebar-brand" href="<?php echo SITE_URL; ?>/admin/index.php">
        <i class="bi bi-book-half"></i>
        <div><?php echo SITE_NAME; ?></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/index.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Manajemen</div>

        <!-- Nav Item - Books -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'books') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/books/index.php">
                <i class="bi bi-book"></i>
                <span>Buku</span>
            </a>
        </li>

        <!-- Nav Item - Borrowing -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'borrowing') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/borrowing/index.php">
                <i class="bi bi-arrow-left-right"></i>
                <span>Peminjaman</span>
                <?php
                $db = new Database();
                $overdueCount = $db->fetchOne("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
                if ($overdueCount && $overdueCount['count'] > 0):
                ?>
                    <span class="badge bg-danger"><?php echo $overdueCount['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Nav Item - Book Requests -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'requests') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/requests/index.php">
                <i class="bi bi-cart-plus"></i>
                <span>Permintaan Buku</span>
                <?php
                $pendingRequests = $db->fetchOne("SELECT COUNT(*) as count FROM book_requests WHERE status = 'pending'");
                if ($pendingRequests && $pendingRequests['count'] > 0):
                ?>
                    <span class="badge bg-warning"><?php echo $pendingRequests['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Nav Item - Users -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/users/index.php">
                <i class="bi bi-people"></i>
                <span>Pengguna</span>
            </a>
        </li>

        <!-- Nav Item - Publishers -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'publishers') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/publishers/index.php">
                <i class="bi bi-building"></i>
                <span>Penerbit</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Laporan</div>

        <!-- Nav Item - Reports -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/reports/index.php">
                <i class="bi bi-graph-up"></i>
                <span>Laporan & Analitik</span>
            </a>
        </li>

        <!-- Nav Item - Fines -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'fines') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/fines/index.php">
                <i class="bi bi-currency-dollar"></i>
                <span>Manajemen Denda</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">Sistem</div>

        <!-- Nav Item - Activity Logs -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'logs') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/logs/index.php">
                <i class="bi bi-clipboard-data"></i>
                <span>Log Aktivitas</span>
            </a>
        </li>

        <?php if (hasRole('super_admin')): ?>
        <!-- Nav Item - Settings (Super Admin Only) -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/settings/index.php">
                <i class="bi bi-gear"></i>
                <span>Pengaturan</span>
            </a>
        </li>

        <!-- Nav Item - Universities (Super Admin Only) -->
        <li class="nav-item">
            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'universities') !== false ? 'active' : ''; ?>"
               href="<?php echo SITE_URL; ?>/admin/universities/index.php">
                <i class="bi bi-bank"></i>
                <span>Universitas</span>
            </a>
        </li>
        <?php endif; ?>

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
