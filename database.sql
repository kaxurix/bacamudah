-- ============================================================
-- Baca Mudah - skema database dan data contoh
-- Cara pakai: buka phpMyAdmin > Import > pilih file ini.
-- ============================================================

CREATE DATABASE IF NOT EXISTS bacamudah
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bacamudah;

DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS reading_settings;
DROP TABLE IF EXISTS texts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL
);

CREATE TABLE texts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NULL,
  visibilitas ENUM('umum', 'pribadi') DEFAULT 'pribadi',
  judul VARCHAR(150) NOT NULL,
  isi_teks TEXT NOT NULL,
  thumbnail VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE reading_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  font VARCHAR(50) DEFAULT 'sans',
  ukuran INT DEFAULT 18,
  jarak_huruf DECIMAL(3,2) DEFAULT 0.05,
  tinggi_baris DECIMAL(3,1) DEFAULT 1.8,
  tema ENUM('normal','krem','kontras') DEFAULT 'normal',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bookmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  text_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_text (user_id, text_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
  attempt_key CHAR(64) PRIMARY KEY,
  attempts INT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  INDEX idx_login_expires (expires_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Data contoh
-- Akun demo (GANTI password sebelum dipakai sungguhan):
--   admin@bacamudah.test  / admin123
--   user@bacamudah.test   / user12345
-- ------------------------------------------------------------
INSERT INTO users (id, nama, email, password, role) VALUES
(1, 'Admin Baca Mudah', 'admin@bacamudah.test', '$2y$10$.WpoXb.bY.Yupe9ZRF3ZRuI3qjNjyn2eKDzFyWaie6YTDcFm17T3S', 'admin'),
(2, 'Pengguna Contoh', 'user@bacamudah.test', '$2y$10$60GXBad03.8xuThlx6WLwOx/5cRn/osZLPIoVwg41/jYjZOOl2oae', 'user');

INSERT INTO categories (id, nama_kategori, slug) VALUES
(1, 'Pendidikan', 'pendidikan'),
(2, 'Tips Membaca', 'tips-membaca'),
(3, 'Cerita Pendek', 'cerita-pendek');

INSERT INTO texts (user_id, category_id, visibilitas, judul, isi_teks) VALUES
(1, 1, 'umum', 'Mengenal SDG 4: Pendidikan Berkualitas',
'Tujuan Pembangunan Berkelanjutan atau SDGs adalah agenda bersama yang disepakati oleh negara-negara anggota Perserikatan Bangsa-Bangsa pada tahun 2015. Agenda ini memuat tujuh belas tujuan yang ingin dicapai pada tahun 2030.

Tujuan keempat berfokus pada pendidikan yang berkualitas. Intinya, setiap orang berhak mendapatkan pendidikan yang layak, adil, dan terbuka, tanpa tertinggal karena latar belakang atau kondisinya.

Salah satu kunci pendidikan yang adil adalah bahan belajar yang bisa diakses semua orang. Teks yang nyaman dibaca oleh seseorang belum tentu nyaman dibaca oleh orang lain. Karena itu, kemampuan mengatur tampilan teks membantu lebih banyak orang untuk belajar dengan caranya sendiri.

Teknologi web dapat berperan di sini. Hal sederhana seperti ukuran huruf, jarak antar huruf, jarak antar baris, dan pilihan warna dapat membuat sebuah halaman terasa jauh lebih ramah.'),
(1, 2, 'umum', 'Tips Membaca Lebih Nyaman di Layar',
'Membaca di layar bisa melelahkan mata, apalagi jika dilakukan dalam waktu lama. Ada beberapa kebiasaan sederhana yang bisa dicoba untuk mengurangi rasa lelah.

Pertama, atur ukuran huruf sampai Anda tidak perlu menyipitkan mata. Ukuran yang nyaman berbeda untuk tiap orang, jadi tidak ada angka yang paling benar.

Kedua, beri jarak yang cukup antar baris. Baris yang terlalu rapat membuat mata mudah berpindah ke baris yang salah.

Ketiga, pilih warna latar dan teks yang kontrasnya jelas. Sebagian orang lebih nyaman dengan latar krem dibandingkan putih terang.

Keempat, istirahatkan mata secara berkala. Setiap beberapa menit, alihkan pandangan ke benda yang jauh selama beberapa detik.'),
(1, 3, 'umum', 'Perpustakaan di Ujung Desa',
'Di ujung sebuah desa kecil, ada perpustakaan dengan satu rak buku dan dua kursi kayu. Setiap sore, anak-anak berkumpul di sana untuk membaca.

Raka adalah salah satu anak yang paling sering datang. Ia suka cerita, tetapi membaca terasa lambat baginya. Huruf-huruf di halaman sering terlihat berdempetan.

Suatu hari, pustakawan desa memberinya sebuah tablet. Di layarnya, huruf bisa dibesarkan dan dijarangkan sesuka hati. Raka menggeser pengaturannya pelan-pelan sampai kalimat di layar terasa tenang.

Sore itu, untuk pertama kalinya, Raka menyelesaikan satu cerita tanpa berhenti. Ia tersenyum, lalu mengambil cerita berikutnya.');

INSERT INTO reading_settings (user_id) VALUES (1), (2);
