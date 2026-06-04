-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `perpustakaan_novel`;
USE `perpustakaan_novel`;

-- 1. TABEL USERS
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'member') DEFAULT 'member',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL CATEGORIES
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL NOVELS
CREATE TABLE IF NOT EXISTS `novels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `author` VARCHAR(100) NOT NULL,
  `publisher` VARCHAR(100) DEFAULT NULL,
  `year` INT DEFAULT NULL,
  `category_id` INT DEFAULT NULL,
  `synopsis` TEXT DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `stock` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL BORROWINGS
CREATE TABLE IF NOT EXISTS `borrowings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `novel_id` INT NOT NULL,
  `borrow_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `status` ENUM('borrowed', 'returned') DEFAULT 'borrowed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`novel_id`) REFERENCES `novels`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SISIPKAN DATA CONTOH (SEEDS)

-- Admin Default (Username: admin, Password: admin123)
-- Member Default (Username: member, Password: member123)
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`) VALUES
(1, 'admin', '$2y$10$YCo60rOQpI.N6uH8VexKj.04WlhQn0L7k.f1fK9XJlhZ.b/Gf1J8q', 'Administrator Perpustakaan', 'admin'),
(2, 'member', '$2y$10$gMv3M26ZgH87h8rJg7oK4u3.x.vY5e4P6K2Y1a8B8d9U1T1R5T2Tq', 'Budi Santoso (Member)', 'member')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Kategori Novel
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Romantis', 'Kisah romantis dan drama hubungan antar manusia.'),
(2, 'Fantasi', 'Dunia sihir, makhluk mitologi, dan petualangan fantasi.'),
(3, 'Misteri', 'Detektif, teka-teki pembunuhan, dan cerita menegangkan.'),
(4, 'Sci-Fi / Fiksi Ilmiah', 'Teknologi masa depan, luar angkasa, dan distopia.'),
(5, 'Fiksi Sejarah', 'Kisah fiksi berlatar belakang peristiwa sejarah nyata.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Novel Contoh
INSERT INTO `novels` (`id`, `title`, `author`, `publisher`, `year`, `category_id`, `synopsis`, `cover_image`, `stock`) VALUES
(1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 5, 'Kisah perjuangan sepuluh anak di Belitung dalam menempuh pendidikan di sekolah Muhammadiyah yang serba kekurangan.', NULL, 5),
(2, 'Bumi', 'Tere Liye', 'Gramedia Pustaka Utama', 2014, 2, 'Petualangan Raib, Seli, dan Ali di dunia paralel (Klan Bulan, Klan Matahari, dll) dengan kemampuan unik mereka.', NULL, 3),
(3, 'Perahu Kertas', 'Dee Lestari', 'Bentang Pustaka', 2009, 1, 'Perjalanan cinta Kugy dan Keenan yang penuh lika-liku serta impian mereka yang saling bertolak belakang namun melengkapi.', NULL, 4)
ON DUPLICATE KEY UPDATE `id`=`id`;
