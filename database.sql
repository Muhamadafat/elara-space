-- Elara Space - Library Management System Database
-- Created for Faculty of Economics across 5 Universities

CREATE DATABASE IF NOT EXISTS elara_space;
USE elara_space;

-- Table: Universities
CREATE TABLE universities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Users (Mahasiswa, Dosen, Staff, Admin)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    university_id INT NOT NULL,
    user_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mahasiswa', 'dosen', 'staff', 'super_admin') DEFAULT 'mahasiswa',
    phone VARCHAR(20),
    address TEXT,
    profile_photo VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_university (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Publishers (Penerbit/Toko Buku)
CREATE TABLE publishers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    website VARCHAR(255),
    partnership_status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (partnership_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Books
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher_id INT,
    category VARCHAR(100),
    publication_year YEAR,
    pages INT,
    language VARCHAR(50) DEFAULT 'Indonesian',
    description TEXT,
    cover_image VARCHAR(255),
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    location VARCHAR(100),
    price DECIMAL(10,2),
    status ENUM('available', 'unavailable', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL,
    INDEX idx_title (title),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Book Requests (Request buku dari user)
CREATE TABLE book_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    university_id INT NOT NULL,
    isbn VARCHAR(20),
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher_name VARCHAR(100),
    category VARCHAR(100),
    reason TEXT,
    estimated_price DECIMAL(10,2),
    request_type ENUM('new_book', 'additional_copy') DEFAULT 'new_book',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'approved', 'rejected', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
    admin_notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    ordered_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Book Request Payments (Tagihan pembayaran untuk buku yang direquest)
CREATE TABLE book_request_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_request_id INT NOT NULL,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid', 'paid', 'cancelled') DEFAULT 'unpaid',
    payment_method VARCHAR(50) NULL,
    payment_date TIMESTAMP NULL,
    due_date DATE NULL,
    admin_notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_request_id) REFERENCES book_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payment_status (payment_status),
    INDEX idx_user (user_id),
    INDEX idx_request (book_request_id),
    INDEX idx_due_date (due_date),
    INDEX idx_user_status (user_id, payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Borrowing Transactions
CREATE TABLE borrowings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    extended_count INT DEFAULT 0,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
    notes TEXT,
    created_by INT,
    returned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (returned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_book (book_id),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Fines (Denda keterlambatan)
CREATE TABLE fines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    borrowing_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    days_late INT NOT NULL,
    status ENUM('unpaid', 'paid', 'waived') DEFAULT 'unpaid',
    paid_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrowing_id) REFERENCES borrowings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Activity Logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: System Settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Universities
INSERT INTO universities (code, name, address, phone, email) VALUES
('UPI', 'Universitas Pendidikan Indonesia - Fakultas Ekonomi', 'Jl. Dr. Setiabudhi No. 229, Bandung', '022-2013161', 'ekonomi@upi.edu'),
('UNPAD', 'Universitas Padjadjaran - Fakultas Ekonomi dan Bisnis', 'Jl. Dipati Ukur No. 35, Bandung', '022-2504088', 'feb@unpad.ac.id'),
('UIN', 'UIN Sunan Gunung Djati - Fakultas Ekonomi dan Bisnis Islam', 'Jl. A.H. Nasution No. 105, Bandung', '022-7800525', 'febi@uinsgd.ac.id'),
('UMB', 'Universitas Muhammadiyah Bandung - Fakultas Ekonomi', 'Jl. Soekarno Hatta No. 752, Bandung', '022-7303649', 'fe@umbandung.ac.id'),
('IKOPIN', 'Institut Koperasi Indonesia - Fakultas Ekonomi', 'Jl. Jatinangor KM 20.5, Sumedang', '022-7798727', 'fe@ikopin.ac.id');

-- Insert Default Super Admin
INSERT INTO users (university_id, user_code, name, email, password, role) VALUES
(1, 'ADMIN001', 'Super Administrator', 'admin@elaraspace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Default password: password (should be changed after first login)

-- Insert Sample Publishers
INSERT INTO publishers (name, contact_person, email, phone, address, website, partnership_status) VALUES
('Gramedia Pustaka Utama', 'John Doe', 'partnership@gramedia.com', '021-53650110', 'Jakarta', 'https://www.gramedia.com', 'active'),
('Erlangga', 'Jane Smith', 'info@erlangga.co.id', '021-4892100', 'Jakarta', 'https://www.erlangga.co.id', 'active'),
('Salemba Empat', 'Ahmad Santoso', 'contact@penerbitsalemba.com', '021-3904316', 'Jakarta', 'https://www.penerbitsalemba.com', 'active'),
('Andi Publisher', 'Budi Santoso', 'info@andipublisher.com', '024-8453290', 'Semarang', 'https://www.andipublisher.com', 'active'),
('Rajagrafindo Persada', 'Siti Nurhaliza', 'info@rajagrafindo.co.id', '021-4204365', 'Jakarta', 'https://www.rajagrafindo.co.id', 'active');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('borrow_duration_days', '14', 'Default borrowing duration in days'),
('max_borrow_books', '3', 'Maximum books a user can borrow at once'),
('fine_per_day', '2000', 'Fine amount per day (in IDR)'),
('max_extension', '1', 'Maximum number of times a borrowing can be extended'),
('extension_duration_days', '7', 'Extension duration in days'),
('system_name', 'Elara Space', 'Library management system name'),
('system_email', 'noreply@elaraspace.com', 'System email for notifications'),
('overdue_notification_days', '3', 'Send overdue notification before N days'),
('request_approval_required', '1', 'Book requests require admin approval (1=yes, 0=no)'),
('allow_self_registration', '1', 'Allow users to self-register (1=yes, 0=no)');

-- Create Views for Reports

-- View: Current Borrowings
CREATE VIEW view_current_borrowings AS
SELECT
    b.id,
    b.borrow_date,
    b.due_date,
    b.status,
    b.extended_count,
    u.name AS user_name,
    u.user_code,
    u.role AS user_role,
    uni.name AS university,
    bk.title AS book_title,
    bk.author AS book_author,
    bk.isbn,
    DATEDIFF(CURDATE(), b.due_date) AS days_overdue
FROM borrowings b
JOIN users u ON b.user_id = u.id
JOIN universities uni ON u.university_id = uni.id
JOIN books bk ON b.book_id = bk.id
WHERE b.status IN ('borrowed', 'overdue');

-- View: Book Request Summary
CREATE VIEW view_book_requests_summary AS
SELECT
    br.id,
    br.title,
    br.author,
    br.publisher_name,
    br.status,
    br.priority,
    br.estimated_price,
    br.created_at,
    u.name AS requested_by,
    u.user_code,
    uni.name AS university
FROM book_requests br
JOIN users u ON br.user_id = u.id
JOIN universities uni ON br.university_id = uni.id
ORDER BY
    CASE br.priority
        WHEN 'urgent' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
    END,
    br.created_at DESC;

-- View: Popular Books
CREATE VIEW view_popular_books AS
SELECT
    b.id,
    b.title,
    b.author,
    b.isbn,
    b.category,
    COUNT(br.id) AS borrow_count,
    AVG(DATEDIFF(COALESCE(br.return_date, CURDATE()), br.borrow_date)) AS avg_borrow_days
FROM books b
LEFT JOIN borrowings br ON b.id = br.book_id
GROUP BY b.id, b.title, b.author, b.isbn, b.category
ORDER BY borrow_count DESC;

-- View: User Statistics
CREATE VIEW view_user_statistics AS
SELECT
    u.id,
    u.name,
    u.user_code,
    u.email,
    u.role,
    uni.name AS university,
    COUNT(DISTINCT b.id) AS total_borrowings,
    COUNT(DISTINCT CASE WHEN b.status = 'borrowed' THEN b.id END) AS active_borrowings,
    COUNT(DISTINCT CASE WHEN b.status = 'overdue' THEN b.id END) AS overdue_count,
    COALESCE(SUM(f.amount), 0) AS total_fines,
    COALESCE(SUM(CASE WHEN f.status = 'unpaid' THEN f.amount ELSE 0 END), 0) AS unpaid_fines
FROM users u
JOIN universities uni ON u.university_id = uni.id
LEFT JOIN borrowings b ON u.id = b.user_id
LEFT JOIN fines f ON u.id = f.user_id
GROUP BY u.id, u.name, u.user_code, u.email, u.role, uni.name;

-- Table: Marketplace Books (Buku dari toko partner)
CREATE TABLE marketplace_books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    publisher_id INT,
    isbn VARCHAR(20),
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    cover_image VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    partner_store VARCHAR(100) NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    estimated_delivery VARCHAR(50),
    stock INT DEFAULT 0,
    status ENUM('available', 'unavailable', 'discontinued') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_partner (partner_store),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Marketplace Orders (Pesanan buku dari marketplace)
CREATE TABLE marketplace_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    partner_store VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'ready_pickup', 'picked_up', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    payment_proof VARCHAR(255),
    estimated_ready DATE,
    ready_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES marketplace_books(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Sample Marketplace Books
INSERT INTO marketplace_books (publisher_id, isbn, title, author, category, price, partner_store, commission_rate, estimated_delivery, stock, status) VALUES
(1, '978-1-449-36132-7', 'Data Science untuk Bisnis', 'Foster Provost', 'Data Science', 450000, 'Gramedia', 10, '3-5 hari kerja', 15, 'available'),
(2, '978-1-492-03264-9', 'Machine Learning Praktis', 'Aurélien Géron', 'Teknologi', 850000, 'Togamas', 12, '2-4 hari kerja', 8, 'available'),
(3, '978-0-262-02728-2', 'Financial Modeling', 'Simon Benninga', 'Finance', 650000, 'Periplus', 8, '5-7 hari kerja', 5, 'available'),
(4, '978-1-119-10854-5', 'Investment Analysis', 'CFA Institute', 'Investment', 1200000, 'Gramedia', 15, '3-5 hari kerja', 3, 'available'),
(5, '978-0-231-17544-4', 'Digital Transformation', 'David Rogers', 'Business', 380000, 'Togamas', 10, '2-3 hari kerja', 20, 'available'),
(1, '978-1-101-98013-4', 'Blockchain Revolution', 'Don Tapscott', 'Technology', 480000, 'Periplus', 12, '4-6 hari kerja', 10, 'available'),
(2, '978-0-134-68581-5', 'Python for Data Analysis', 'Wes McKinney', 'Data Science', 750000, 'Gramedia', 10, '3-5 hari kerja', 12, 'available'),
(3, '978-0-262-03384-8', 'Artificial Intelligence Modern', 'Stuart Russell', 'AI', 920000, 'Togamas', 12, '2-4 hari kerja', 6, 'available');

-- ============================================================================
-- MIGRATION SCRIPT: Update Existing Database
-- ============================================================================
-- Run this section ONLY if you already have a database and want to add
-- the payment system feature to existing installation.
-- ============================================================================

-- Step 1: Add indexes to book_request_payments table (if not exists)
-- This improves query performance for payment lookups
ALTER TABLE book_request_payments
ADD INDEX IF NOT EXISTS idx_due_date (due_date),
ADD INDEX IF NOT EXISTS idx_user_status (user_id, payment_status),
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Step 2: Create invoices for existing approved requests (one-time migration)
-- This generates payment invoices for book requests that were approved
-- before the payment system was implemented
INSERT INTO book_request_payments (book_request_id, user_id, invoice_number, amount, due_date, admin_notes, created_by, payment_status)
SELECT
    br.id,
    br.user_id,
    CONCAT('INV-BR-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(br.id, 5, '0')),
    COALESCE(br.estimated_price, 0),
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    CONCAT('Pembayaran untuk buku: ', br.title),
    br.approved_by,
    'unpaid'
FROM book_requests br
WHERE br.status IN ('pending', 'approved', 'ordered')
AND NOT EXISTS (
    SELECT 1 FROM book_request_payments brp WHERE brp.book_request_id = br.id
)
AND br.estimated_price IS NOT NULL
AND br.estimated_price > 0;

-- Note: This script is safe to run multiple times.
-- It will only create invoices for requests that don't have invoices yet.
