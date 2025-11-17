<!-- Topbar -->
<nav class="topbar">
    <!-- Sidebar Toggle -->
    <button class="topbar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <!-- Search Bar -->
    <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control" placeholder="Cari buku, pengguna, permintaan...">
    </div>

    <!-- Topbar Navbar -->
    <ul class="topbar-nav">
        <!-- Notifications -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <?php
                $currentUser = getCurrentUser();
                $unreadCount = getUnreadNotificationCount($currentUser['id']);
                if ($unreadCount > 0):
                ?>
                    <span class="notification-badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                <li class="dropdown-header">
                    <i class="bi bi-bell me-2"></i>Notifikasi
                </li>
                <?php
                $db = new Database();
                $notifications = $db->fetchAll(
                    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                    [$currentUser['id']]
                );

                if ($notifications):
                    foreach ($notifications as $notif):
                ?>
                    <li>
                        <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>"
                           href="<?php echo $notif['link'] ?? '#'; ?>">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-<?php echo $notif['type'] == 'warning' ? 'exclamation-triangle' : 'info-circle'; ?> me-2"></i>
                                <div>
                                    <div><?php echo truncate($notif['message'], 50); ?></div>
                                    <small class="text-muted"><?php echo formatDateTime($notif['created_at']); ?></small>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php
                    endforeach;
                else:
                ?>
                    <li><a class="dropdown-item" href="#">Tidak ada notifikasi baru</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/admin/notifications.php">Lihat Semua</a></li>
            </ul>
        </li>

        <!-- Messages -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/messages.php" title="Pesan">
                <i class="bi bi-envelope"></i>
            </a>
        </li>

        <!-- User Profile -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($currentUser['profile_photo'])): ?>
                    <img src="<?php echo SITE_URL . '/uploads/profiles/' . $currentUser['profile_photo']; ?>"
                         class="rounded-circle me-2" width="32" height="32" alt="Profile">
                <?php else: ?>
                    <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                <?php endif; ?>
                <span class="d-none d-md-inline"><?php echo $currentUser['name']; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li class="dropdown-header">
                    <div class="fw-bold"><?php echo $currentUser['name']; ?></div>
                    <small class="text-muted"><?php echo $currentUser['email']; ?></small>
                    <div class="mt-1"><?php echo getRoleBadge($currentUser['role']); ?></div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php">
                        <i class="bi bi-person me-2"></i>Profil Saya
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/change-password.php">
                        <i class="bi bi-key me-2"></i>Ubah Password
                    </a>
                </li>
                <?php if (hasRole('super_admin')): ?>
                <li>
                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings/index.php">
                        <i class="bi bi-gear me-2"></i>Pengaturan
                    </a>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"
                       onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
<!-- End of Topbar -->

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');

    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('sidebar').classList.add('collapsed');
        document.querySelector('.main-content').classList.add('expanded');
    }
});

// Mobile sidebar toggle
if (window.innerWidth <= 768) {
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
}
</script>
