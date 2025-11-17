<?php
require_once 'config/config.php';

// Get featured books
$db = new Database();
$featuredBooks = $db->fetchAll("SELECT * FROM books WHERE status = 'available' LIMIT 8");

// If no books in database, use dummy data
if (empty($featuredBooks)) {
    $featuredBooks = [
        [
            'id' => 1,
            'title' => 'Manajemen Strategis',
            'author' => 'Fred David',
            'category' => 'Manajemen',
            'available_quantity' => 5,
            'status' => 'available'
        ],
        [
            'id' => 2,
            'title' => 'Akuntansi Biaya',
            'author' => 'William Carter',
            'category' => 'Akuntansi',
            'available_quantity' => 8,
            'status' => 'available'
        ],
        [
            'id' => 3,
            'title' => 'Ekonomi Makro',
            'author' => 'Gregory Mankiw',
            'category' => 'Ekonomi',
            'available_quantity' => 10,
            'status' => 'available'
        ],
        [
            'id' => 4,
            'title' => 'Pemasaran Modern',
            'author' => 'Philip Kotler',
            'category' => 'Manajemen',
            'available_quantity' => 6,
            'status' => 'available'
        ],
        [
            'id' => 5,
            'title' => 'Analisis Laporan Keuangan',
            'author' => 'Kasmir',
            'category' => 'Akuntansi',
            'available_quantity' => 4,
            'status' => 'available'
        ],
        [
            'id' => 6,
            'title' => 'Bisnis Internasional',
            'author' => 'Charles Hill',
            'category' => 'Bisnis',
            'available_quantity' => 7,
            'status' => 'available'
        ],
        [
            'id' => 7,
            'title' => 'Manajemen Keuangan',
            'author' => 'Eugene Brigham',
            'category' => 'Keuangan',
            'available_quantity' => 9,
            'status' => 'available'
        ],
        [
            'id' => 8,
            'title' => 'Ekonomi Mikro',
            'author' => 'Robert Pindyck',
            'category' => 'Ekonomi',
            'available_quantity' => 3,
            'status' => 'available'
        ]
    ];
}

// Check if user is logged in for navbar
$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;

$pageTitle = 'Beranda - ' . SITE_NAME;
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
    <style>
        body { background: #f8f9fa; }

        /* Navbar */
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #3B82F6 !important;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 0.5rem 0;
            min-width: 200px;
        }
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background: #EFF6FF;
            color: #3B82F6;
        }
        .dropdown-divider {
            margin: 0.5rem 0;
        }

        /* Hero Carousel */
        .hero-carousel {
            margin-top: 80px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .carousel-item { height: 400px; }
        .carousel-item img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
        .carousel-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59,130,246,0.85), rgba(37,99,235,0.85));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white !important;
            text-align: center;
            padding: 3rem;
        }
        .carousel-overlay h1, .carousel-overlay p {
            color: white !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        /* Categories */
        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2rem 1rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-8px);
            border-color: #3B82F6;
            box-shadow: 0 10px 25px rgba(59,130,246,0.15);
        }
        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            color: white;
        }

        /* Promo Banners */
        .promo-banner {
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            height: 220px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .promo-banner:hover { transform: scale(1.02); }
        .promo-content {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 2rem;
            color: white !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .promo-content h3, .promo-content p {
            color: white !important;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
        .promo-1 {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.4)),
                        url('https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&h=400&fit=crop') center/cover;
        }
        .promo-2 {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.4)),
                        url('https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=600&h=400&fit=crop') center/cover;
        }
        .promo-3 {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.4)),
                        url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=600&h=400&fit=crop') center/cover;
        }

        /* Book Cards */
        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .book-cover {
            height: 240px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }
        .badge-new {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #EF4444;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        .section-subtitle {
            color: #6b7280;
            margin-bottom: 3rem;
        }

        /* Footer */
        .footer-custom {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white !important;
        }
        .footer-custom h5, .footer-custom h6, .footer-custom p, .footer-custom li, .footer-custom small {
            color: white !important;
        }
        .footer-link:hover {
            opacity: 1 !important;
            text-decoration: underline !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-book-half me-2"></i>Elara Space
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#categories">Kategori</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/browse.php">Semua Buku</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#promo">Promo</a>
                    </li>

                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($currentUser['name']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin' ? SITE_URL . '/admin/index.php' : SITE_URL . '/user/index.php'; ?>">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/profile.php">
                                            <i class="bi bi-person me-2"></i>Profil Saya
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-primary me-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i>Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Carousel -->
    <div class="container mt-5 pt-3">
        <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=1200&h=400&fit=crop" alt="Library">
                    <div class="carousel-overlay" style="background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5));">
                        <div>
                            <h1 class="display-4 fw-bold mb-3">Selamat Datang di Elara Space</h1>
                            <p class="lead mb-4">Perpustakaan Digital untuk Mahasiswa Ekonomi</p>
                            <?php if (!$isLoggedIn): ?>
                                <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-light btn-lg px-5">
                                    <i class="bi bi-search me-2"></i>Mulai Jelajahi
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=1200&h=400&fit=crop" alt="Books">
                    <div class="carousel-overlay" style="background: linear-gradient(135deg, rgba(40,40,40,0.8), rgba(20,20,20,0.8));">
                        <div>
                            <h1 class="display-4 fw-bold mb-3">Ribuan Buku Terbaik</h1>
                            <p class="lead mb-4">Akses Gratis untuk Mahasiswa & Dosen</p>
                            <?php if ($isLoggedIn): ?>
                                <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-light btn-lg px-5">
                                    <i class="bi bi-book me-2"></i>Jelajahi Buku
                                </a>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-light btn-lg px-5">
                                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=1200&h=400&fit=crop" alt="Reading">
                    <div class="carousel-overlay" style="background: linear-gradient(135deg, rgba(101,67,33,0.75), rgba(78,52,46,0.75));">
                        <div>
                            <h1 class="display-4 fw-bold mb-3">Pinjam Buku Online</h1>
                            <p class="lead mb-4">Mudah, Cepat & Praktis</p>
                            <?php if ($isLoggedIn): ?>
                                <a href="<?php echo $currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin' ? SITE_URL . '/admin/borrowing/index.php' : SITE_URL . '/user/books.php'; ?>" class="btn btn-light btn-lg px-5">
                                    <i class="bi bi-book-half me-2"></i>Lihat Koleksi
                                </a>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-light btn-lg px-5">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>

    <!-- Categories Section -->
    <section id="categories" class="py-5 mt-4">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Kategori Buku</h2>
                <p class="section-subtitle">Temukan buku sesuai kategori favoritmu</p>
            </div>
            <div class="row g-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php?category=Manajemen'">
                        <div class="category-icon"><i class="bi bi-briefcase"></i></div>
                        <h6 class="fw-bold mb-0">Manajemen</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php?category=Akuntansi'">
                        <div class="category-icon"><i class="bi bi-calculator"></i></div>
                        <h6 class="fw-bold mb-0">Akuntansi</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php?category=Ekonomi'">
                        <div class="category-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h6 class="fw-bold mb-0">Ekonomi</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php?category=Bisnis'">
                        <div class="category-icon"><i class="bi bi-shop"></i></div>
                        <h6 class="fw-bold mb-0">Bisnis</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php?category=Keuangan'">
                        <div class="category-icon"><i class="bi bi-cash-stack"></i></div>
                        <h6 class="fw-bold mb-0">Keuangan</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="category-card" onclick="window.location.href='<?php echo SITE_URL; ?>/browse.php'">
                        <div class="category-icon"><i class="bi bi-grid"></i></div>
                        <h6 class="fw-bold mb-0">Semua</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Banners -->
    <section id="promo" class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="promo-banner promo-1">
                        <div class="promo-content">
                            <h3 class="fw-bold mb-2">Buku Baru</h3>
                            <p class="mb-3">Koleksi terbaru bulan ini</p>
                            <a href="<?php echo SITE_URL; ?>/browse.php?sort=newest" class="btn btn-light btn-sm">Lihat Sekarang</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="promo-banner promo-2">
                        <div class="promo-content">
                            <h3 class="fw-bold mb-2">Buku Populer</h3>
                            <p class="mb-3">Paling banyak dipinjam</p>
                            <a href="<?php echo SITE_URL; ?>/browse.php?sort=popular" class="btn btn-light btn-sm">Jelajahi</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="promo-banner promo-3">
                        <div class="promo-content">
                            <h3 class="fw-bold mb-2">E-Book Gratis</h3>
                            <p class="mb-3">Download & baca offline</p>
                            <a href="<?php echo SITE_URL; ?>/browse.php?type=ebook" class="btn btn-light btn-sm">Download</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Books -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Buku Pilihan</h2>
                <p class="section-subtitle">Rekomendasi buku terbaik untuk kamu</p>
            </div>
            <div class="row g-4">
                <?php if (!empty($featuredBooks)): ?>
                    <?php foreach (array_slice($featuredBooks, 0, 8) as $index => $book): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="book-card">
                                <div class="book-cover" style="padding: 0; background: none;">
                                    <?php if ($index < 3): ?>
                                        <span class="badge-new">BARU</span>
                                    <?php endif; ?>
                                    <?php
                                    // Generate random book cover image
                                    $coverImages = [
                                        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=400&h=250&fit=crop',
                                        'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=250&fit=crop'
                                    ];
                                    $randomCover = $coverImages[($book['id'] ?? 0) % count($coverImages)];
                                    ?>
                                    <img src="<?php echo $randomCover; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="p-3">
                                    <h6 class="fw-bold mb-2" style="height: 48px; overflow: hidden;"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($book['category'] ?? 'Umum'); ?></span>
                                        <small class="text-success fw-bold">Tersedia: <?php echo $book['available_quantity']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Belum ada buku tersedia. Silakan jalankan setup terlebih dahulu.</p>
                        <a href="<?php echo SITE_URL; ?>/init.php" class="btn btn-primary">Setup Database</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($featuredBooks)): ?>
                <div class="text-center mt-5">
                    <a href="<?php echo SITE_URL; ?>/browse.php" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-grid me-2"></i>Lihat Semua Buku
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 mt-5 footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3 text-white"><i class="bi bi-book-half me-2"></i>Elara Space</h5>
                    <p class="text-white opacity-75">Sistem Manajemen Perpustakaan Modern untuk Fakultas Ekonomi</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h6 class="fw-bold mb-3 text-white">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/browse.php" class="text-white text-decoration-none opacity-75 footer-link">Katalog Buku</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/register.php" class="text-white text-decoration-none opacity-75 footer-link">Daftar</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/login.php" class="text-white text-decoration-none opacity-75 footer-link">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h6 class="fw-bold mb-3 text-white">Universitas Terdaftar</h6>
                    <ul class="list-unstyled text-white opacity-75">
                        <li>UPI - Universitas Pendidikan Indonesia</li>
                        <li>UNPAD - Universitas Padjadjaran</li>
                        <li>UIN Sunan Gunung Djati</li>
                        <li>Universitas Mercubuana</li>
                        <li>Institut Koperasi Indonesia</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-white" style="opacity: 0.2;">
            <div class="text-center text-white opacity-75">
                <small>&copy; <?php echo date('Y'); ?> Elara Space. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-rotate carousel
        var myCarousel = document.querySelector('#heroCarousel')
        var carousel = new bootstrap.Carousel(myCarousel, {
            interval: 4000,
            wrap: true
        })
    </script>
</body>
</html>
