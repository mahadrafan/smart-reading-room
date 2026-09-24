-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Sep 2026 pada 13.54
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `basdat`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `table_name`, `record_id`, `description`, `created_at`) VALUES
(1, 1, 'ACC', 'loans', 1, 'Menyetujui peminjaman \"23:59\" oleh ss', '2026-09-23 08:14:42'),
(2, 1, 'Tolak', 'loans', 3, 'Menolak peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss', '2026-09-23 08:21:26'),
(3, 1, 'ACC', 'loans', 4, 'Menyetujui peminjaman \"23:59\" oleh juliana martinelli', '2026-09-23 14:55:04'),
(4, 1, 'ACC', 'loans', 5, 'Menyetujui peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli', '2026-09-23 14:55:07'),
(5, 1, 'Kembalikan', 'loans', 4, 'Menandai pengembalian \"23:59\" oleh juliana martinelli', '2026-09-23 14:55:54'),
(6, 1, 'Tambah', 'books', 5, 'Menambahkan buku \"Seporsi Mie Ayam Sebelum Mati\"', '2026-09-23 15:31:36'),
(7, 1, 'Kembalikan', 'loans', 1, 'Menandai pengembalian \"23:59\" oleh ss', '2026-09-23 15:33:50'),
(8, 1, 'Hapus', 'loans', 1, 'Menghapus riwayat peminjaman \"23:59\" oleh ss', '2026-09-23 15:44:22'),
(9, 1, 'ACC', 'loans', 6, 'Menyetujui peminjaman \"Seporsi Mie Ayam Sebelum Mati\" oleh ss', '2026-09-23 15:44:31'),
(10, 1, 'Edit', 'books', 4, 'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"', '2026-09-23 15:45:18'),
(11, 1, 'Edit', 'books', 4, 'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"', '2026-09-23 15:50:58'),
(12, 1, 'Hapus', 'books', 3, 'Menonaktifkan buku \"Data Science dengan Python: Konsep dan Implementasi\"', '2026-09-23 15:52:27'),
(13, 1, 'Hapus', 'books', 5, 'Menonaktifkan buku \"Seporsi Mie Ayam Sebelum Mati\"', '2026-09-23 15:54:18'),
(14, 1, 'ACC', 'loans', 7, 'Menyetujui peminjaman \"23:59\" oleh ss', '2026-09-24 07:35:59'),
(15, 1, 'Kembalikan', 'loans', 5, 'Menandai pengembalian \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli', '2026-09-24 07:36:09'),
(16, 1, 'ACC', 'loans', 8, 'Menyetujui peminjaman \"Laut Bercerita\" oleh ss', '2026-09-24 11:34:58'),
(17, 1, 'Kembalikan', 'loans', 8, 'Menandai pengembalian \"Laut Bercerita\" oleh ss', '2026-09-24 11:35:10'),
(18, 1, 'Gagal Otomatis', 'loans', 11, 'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.', '2026-09-24 16:41:34'),
(19, 1, 'Gagal Otomatis', 'loans', 13, 'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.', '2026-09-24 16:41:34'),
(20, 1, 'Dipinjam', 'loans', 6, 'Menandai buku \"Seporsi Mie Ayam Sebelum Mati\" telah diambil manual oleh ss', '2026-09-24 16:47:34'),
(21, 1, 'Kembalikan', 'loans', 6, 'Menandai pengembalian \"Seporsi Mie Ayam Sebelum Mati\" oleh ss', '2026-09-24 16:47:43'),
(22, 1, 'Dipinjam', 'loans', 7, 'Menandai buku \"23:59\" telah diambil manual oleh ss', '2026-09-24 16:47:48'),
(23, 1, 'ACC', 'loans', 14, 'Mengonfirmasi peminjaman \"23:59\" oleh rafan', '2026-09-24 17:01:57'),
(24, 1, 'ACC', 'loans', 9, 'Mengonfirmasi peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss', '2026-09-24 17:02:01'),
(25, 1, 'Kembalikan', 'loans', 7, 'Menandai pengembalian \"23:59\" oleh ss', '2026-09-24 17:04:16'),
(26, 1, 'Dipinjam', 'loans', 14, 'Menandai buku \"23:59\" telah diambil manual oleh rafan', '2026-09-24 17:04:23'),
(27, 1, 'Dipinjam', 'loans', 9, 'Menandai buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" telah diambil manual oleh ss', '2026-09-24 17:04:26'),
(28, 1, 'Tambah', 'books', 6, 'Menambahkan buku \"Buku Uji Baru\"', '2026-09-24 17:36:47'),
(29, 1, 'Tambah', 'books', 7, 'Menambahkan buku \"oijoijoijoij\"', '2026-09-24 17:40:09'),
(30, 1, 'Edit', 'books', 7, 'Mengubah buku \"oijoijoijoij\"', '2026-09-24 17:40:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `authors`
--

CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `biography` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `authors`
--

INSERT INTO `authors` (`author_id`, `author_name`, `biography`) VALUES
(1, 'Leila S. Chudori', 'Penulis dan jurnalis senior Indonesia, penulis novel peraih penghargaan seperti Laut Bercerita dan Pulang.'),
(2, 'Brian Khrisna', 'Penulis novel populer Indonesia yang dikenal luas lewat karya-karya bertema romansa, kehidupan urban, dan komedi.'),
(3, 'Prof. Dr. Kristoko Dwi Hartomo, dkk.', 'Pakar dan akademisi senior ilmu komputer serta data science di Indonesia, aktif menulis buku referensi pemrograman dan sains data.'),
(4, 'Irmah Halimah Bachtiar, S.E., M.Si.', 'Dosen dan akademisi bidang akuntansi dan keuangan, aktif menulis buku panduan akuntansi dasar untuk mahasiswa dan pemula.'),
(6, 'vincent', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `available_stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`book_id`, `category_id`, `author_id`, `title`, `publisher`, `publication_year`, `description`, `cover_image`, `location`, `stock`, `available_stock`, `is_active`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(1, 1, 1, 'Laut Bercerita', 'Kepustakaan Populer Gramedia (KPG)', '2017', 'Novel yang mengangkat kisah persahabatan, cinta, kekeluargaan, dan rasa kehilangan para aktivis mahasiswa di masa Orde Baru. Biru Laut menceritakan penyiksaan dan detik-detik terakhirnya sebelum ditenggelamkan ke dasar laut, sementara Asmara Jati berjuang mencari keadilan bagi para korban penghilangan paksa.', 'covers/laut_bercerita.jpg', 'Rak F-01', 5, 5, 1, 1, '2026-09-23 00:56:35', NULL, '2026-09-23 00:56:35'),
(2, 2, 2, '23:59', 'Grasindo', '2021', 'Tentang kau yang tak pernah pulang dan aku yang selalu menunggu. Sebuah kisah refleksi tentang perjumpaan, kebersamaan, perpisahan, dan bagaimana waktu menguji perasaan manusia di penghujung hari.', 'covers/2359.jpg', 'Rak R-04', 4, 3, 1, 1, '2026-09-23 00:56:35', NULL, '2026-09-23 00:56:35'),
(3, 3, 3, 'Data Science dengan Python: Konsep dan Implementasi', 'Penerbit Gava Media', '2021', 'Membahas konsep fundamental data science mulai dari pengumpulan data, data preprocessing, eksplorasi data analisis (EDA), hingga pemodelan machine learning menggunakan bahasa pemrograman Python dan berbagai pustaka populernya seperti Pandas, NumPy, dan Scikit-Learn.', 'covers/data_science_python.png', 'Rak T-02', 6, 6, 0, 1, '2026-09-23 01:04:37', 1, '2026-09-23 08:52:27'),
(4, 4, 4, 'Akuntansi Dasar: Buku Pintar untuk Pemula', 'Deepuyuy', '2019', 'Buku panduan praktis yang menyajikan konsep dasar akuntansi secara sistematis dan mudah dipahami, mulai dari siklus akuntansi, pencatatan transaksi jurnal umum, buku besar, neraca saldo, hingga penyusunan laporan keuangan untuk pemula.', 'covers/pFkUxgzeuzk3HjmgHbMg7OVxo9KsUtlhpY3lei6q.jpg', 'Rak E-05', 6, 5, 1, 1, '2026-09-23 01:04:37', 1, '2026-09-23 08:50:58'),
(5, 1, 2, 'Seporsi Mie Ayam Sebelum Mati', 'Gramedia', '2020', 'Ale adalah seorang pria berusia 37 tahun yang bekerja sebagai pekerja kantoran (budak korporat) di ibu kota. Ia memiliki fisik yang besar, masalah bau badan, dan merasa hidupnya dipenuhi kesialan. Ale sering dibuli oleh lingkungan sekitar, tidak memiliki teman di kantor, serta tidak mendapatkan dukungan dari keluarganya sendiri.', 'covers/O5dFagH8CpTWsBYCZ03UARCu9oqExJsHbnfjY6hB.png', 'Rak B-5', 7, 7, 0, 1, '2026-09-23 15:31:36', 1, '2026-09-23 08:54:18'),
(7, 6, 6, 'oijoijoijoij', 'ouihoujuhij', '2016', 'okjoniubgugtvyfrcuycyt', 'covers/VW83gwF9JnzeRYq7BqyTYjJeL4Hbi7qgESGMCINB.jpg', 'Rak E-05', 18, 18, 1, 1, '2026-09-24 17:40:09', 1, '2026-09-24 10:40:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Fiksi & Sastra', 'Koleksi novel fiksi, sastra, dan cerita inspiratif'),
(2, 'Romance & Drama', 'Koleksi novel percintaan, drama emosional, dan kehidupan'),
(3, 'Komputer & Teknologi', 'Buku ilmu komputer, pemrograman, data science, dan teknologi informasi modern'),
(4, 'Ekonomi & Akuntansi', 'Buku akuntansi, keuangan, manajemen bisnis, dan ekonomi'),
(6, 'horror', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `ebooks`
--

CREATE TABLE `ebooks` (
  `ebook_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `access_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `loans`
--

CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `loan_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Menunggu','Dikonfirmasi','Dipinjam','Gagal','Dikembalikan') NOT NULL DEFAULT 'Menunggu',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loans`
--

INSERT INTO `loans` (`loan_id`, `user_id`, `book_id`, `request_date`, `loan_date`, `due_date`, `return_date`, `status`, `approved_by`, `approved_at`) VALUES
(3, 4, 4, '2026-09-23 08:20:35', '2026-09-23', '2026-09-30', NULL, 'Gagal', 1, '2026-09-23 01:21:26'),
(4, 5, 2, '2026-09-23 14:48:10', '2026-09-23', '2026-10-18', '2026-09-23', 'Dikembalikan', 1, '2026-09-23 07:55:04'),
(5, 5, 4, '2026-09-23 14:52:16', '2026-09-23', '2026-09-28', '2026-09-24', 'Dikembalikan', 1, '2026-09-23 07:55:07'),
(6, 4, 5, '2026-09-23 15:32:48', '2026-09-23', '2026-09-30', '2026-09-24', 'Dikembalikan', 1, '2026-09-23 08:44:31'),
(7, 4, 2, '2026-09-24 07:32:35', '2026-09-24', '2026-10-01', '2026-09-24', 'Dikembalikan', 1, '2026-09-24 00:35:59'),
(8, 4, 1, '2026-09-24 11:34:27', '2026-09-24', '2026-10-04', '2026-09-24', 'Dikembalikan', 1, '2026-09-24 04:34:58'),
(9, 4, 4, '2026-09-24 12:05:50', '2026-09-24', '2026-10-01', NULL, 'Dipinjam', 1, '2026-09-24 10:02:01'),
(14, 6, 2, '2026-09-24 17:01:11', '2026-09-24', '2026-10-01', NULL, 'Dipinjam', 1, '2026-09-24 10:01:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_09_24_093221_update_status_enum_in_loans_table', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `book_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 3, 1, 5, 'Buku yang sangat emosional dan membuka mata tentang perjuangan kemanusiaan. Sangat direkomendasikan!', '2026-09-23 00:56:35'),
(2, 3, 2, 5, 'Gaya kepenulisan Brian Khrisna selalu berhasil menyentuh perasaan dan relate dengan kehidupan sehari-hari.', '2026-09-23 00:56:35'),
(3, 3, 3, 5, 'Penjelasan data science dan implementasi kodenya sangat runtut dan mudah dipraktikkan langsung di Python.', '2026-09-23 01:04:37'),
(4, 3, 4, 5, 'Sangat cocok untuk yang baru pertama kali belajar akuntansi. Disertai studi kasus pencatatan jurnal yang jelas.', '2026-09-23 01:04:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` enum('Peminjam','Admin') NOT NULL DEFAULT 'Peminjam',
  `nim_nip` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `remember_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `role`, `nim_nip`, `name`, `class`, `email`, `password`, `phone`, `created_at`, `remember_token`) VALUES
(1, 'Admin', 'ADM001', 'Admin Satu', NULL, 'admin1@smartreadingroom.test', '$2y$12$O8UHP6tzVcwElzLdadLMouFlWXci2T8chkGcYY6gr7TqV07q7gQ9m', NULL, '2026-09-21 17:38:16', NULL),
(2, 'Admin', 'ADM002', 'Admin Dua', NULL, 'admin2@smartreadingroom.test', '$2y$10$rl9w3q4Lnt8m3rRluEZbOOo4cpjGHa.UdDgPBXpl7w2baqtPETtI2', NULL, '2026-09-21 17:38:16', NULL),
(3, 'Peminjam', '2024001', 'Budi Santoso', 'XII IPA 2', 'budi@test.com', '$2y$12$MXXuAG2WeBX1Yx7IZZ4SWebbW9faJGkjPtSznHgaoPW/Rd996Z7IK', '1234567890', '2026-09-21 18:27:39', NULL),
(4, 'Peminjam', 's', 'ss', 's', 'qsoudqh@gmail.com', '$2y$12$1DqZtWBPPFs28SCXHz6pLuxQjVZAf.vfDKjnT23C41fwxyuKDBTv2', '1', '2026-09-22 08:03:31', NULL),
(5, 'Peminjam', '1234', 'juliana martinelli', 'XII IPS 2', 'julianamartinelli@gmail.com', '$2y$12$GcIUPGccnP9sTGsiwt5sCu.DHVPSIXFbGmjCGcrgP9R9TWnFAGot.', '08123456789', '2026-09-23 14:47:41', NULL),
(6, 'Peminjam', '164241065', 'rafan', 'XII IPA 3', 'mmadanirafan@gmail.com', '$2y$12$L0ECz5BFvSmyVpfg2/S.6e.EDlm0BgdPbXKa6JqIZ7ltLexNZSl1K', '08117193666', '2026-09-24 16:56:36', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_admin` (`admin_id`);

--
-- Indeks untuk tabel `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`author_id`);

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `fk_books_created_by` (`created_by`),
  ADD KEY `fk_books_updated_by` (`updated_by`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indeks untuk tabel `ebooks`
--
ALTER TABLE `ebooks`
  ADD PRIMARY KEY (`ebook_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indeks untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `fk_loans_approved_by` (`approved_by`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uk_users_nim_nip` (`nim_nip`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `authors`
--
ALTER TABLE `authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `ebooks`
--
ALTER TABLE `ebooks`
  MODIFY `ebook_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `loans`
--
ALTER TABLE `loans`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`),
  ADD CONSTRAINT `fk_books_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_books_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `ebooks`
--
ALTER TABLE `ebooks`
  ADD CONSTRAINT `ebooks_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `ebooks_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`);

--
-- Ketidakleluasaan untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`);

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
