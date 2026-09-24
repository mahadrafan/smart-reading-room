-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: basdat
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_admin` (`admin_id`),
  CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'ACC','loans',1,'Menyetujui peminjaman \"23:59\" oleh ss','2026-09-23 08:14:42'),(2,1,'Tolak','loans',3,'Menolak peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss','2026-09-23 08:21:26'),(3,1,'ACC','loans',4,'Menyetujui peminjaman \"23:59\" oleh juliana martinelli','2026-09-23 14:55:04'),(4,1,'ACC','loans',5,'Menyetujui peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli','2026-09-23 14:55:07'),(5,1,'Kembalikan','loans',4,'Menandai pengembalian \"23:59\" oleh juliana martinelli','2026-09-23 14:55:54'),(6,1,'Tambah','books',5,'Menambahkan buku \"Seporsi Mie Ayam Sebelum Mati\"','2026-09-23 15:31:36'),(7,1,'Kembalikan','loans',1,'Menandai pengembalian \"23:59\" oleh ss','2026-09-23 15:33:50'),(8,1,'Hapus','loans',1,'Menghapus riwayat peminjaman \"23:59\" oleh ss','2026-09-23 15:44:22'),(9,1,'ACC','loans',6,'Menyetujui peminjaman \"Seporsi Mie Ayam Sebelum Mati\" oleh ss','2026-09-23 15:44:31'),(10,1,'Edit','books',4,'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"','2026-09-23 15:45:18'),(11,1,'Edit','books',4,'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"','2026-09-23 15:50:58'),(12,1,'Hapus','books',3,'Menonaktifkan buku \"Data Science dengan Python: Konsep dan Implementasi\"','2026-09-23 15:52:27'),(13,1,'Hapus','books',5,'Menonaktifkan buku \"Seporsi Mie Ayam Sebelum Mati\"','2026-09-23 15:54:18'),(14,1,'ACC','loans',7,'Menyetujui peminjaman \"23:59\" oleh ss','2026-09-24 07:35:59'),(15,1,'Kembalikan','loans',5,'Menandai pengembalian \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli','2026-09-24 07:36:09'),(16,1,'ACC','loans',8,'Menyetujui peminjaman \"Laut Bercerita\" oleh ss','2026-09-24 11:34:58'),(17,1,'Kembalikan','loans',8,'Menandai pengembalian \"Laut Bercerita\" oleh ss','2026-09-24 11:35:10'),(18,1,'Gagal Otomatis','loans',11,'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-24 16:41:34'),(19,1,'Gagal Otomatis','loans',13,'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-24 16:41:34'),(20,1,'Dipinjam','loans',6,'Menandai buku \"Seporsi Mie Ayam Sebelum Mati\" telah diambil manual oleh ss','2026-09-24 16:47:34'),(21,1,'Kembalikan','loans',6,'Menandai pengembalian \"Seporsi Mie Ayam Sebelum Mati\" oleh ss','2026-09-24 16:47:43'),(22,1,'Dipinjam','loans',7,'Menandai buku \"23:59\" telah diambil manual oleh ss','2026-09-24 16:47:48'),(23,1,'ACC','loans',14,'Mengonfirmasi peminjaman \"23:59\" oleh rafan','2026-09-24 17:01:57'),(24,1,'ACC','loans',9,'Mengonfirmasi peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss','2026-09-24 17:02:01'),(25,1,'Kembalikan','loans',7,'Menandai pengembalian \"23:59\" oleh ss','2026-09-24 17:04:16'),(26,1,'Dipinjam','loans',14,'Menandai buku \"23:59\" telah diambil manual oleh rafan','2026-09-24 17:04:23'),(27,1,'Dipinjam','loans',9,'Menandai buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" telah diambil manual oleh ss','2026-09-24 17:04:26'),(28,1,'Tambah','books',6,'Menambahkan buku \"Buku Uji Baru\"','2026-09-24 17:36:47'),(29,1,'Tambah','books',7,'Menambahkan buku \"oijoijoijoij\"','2026-09-24 17:40:09'),(30,1,'Edit','books',7,'Mengubah buku \"oijoijoijoij\"','2026-09-24 17:40:52'),(31,1,'ACC','loans',15,'Mengonfirmasi peminjaman \"23:59\" oleh Budi Santoso','2026-09-24 20:19:18'),(32,1,'ACC','loans',16,'Mengonfirmasi peminjaman \"oijoijoijoij\" oleh Budi Santoso','2026-09-24 20:43:59');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `author_id` int NOT NULL AUTO_INCREMENT,
  `author_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `biography` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authors`
--

LOCK TABLES `authors` WRITE;
/*!40000 ALTER TABLE `authors` DISABLE KEYS */;
INSERT INTO `authors` VALUES (1,'Leila S. Chudori','Penulis dan jurnalis senior Indonesia, penulis novel peraih penghargaan seperti Laut Bercerita dan Pulang.'),(2,'Brian Khrisna','Penulis novel populer Indonesia yang dikenal luas lewat karya-karya bertema romansa, kehidupan urban, dan komedi.'),(3,'Prof. Dr. Kristoko Dwi Hartomo, dkk.','Pakar dan akademisi senior ilmu komputer serta data science di Indonesia, aktif menulis buku referensi pemrograman dan sains data.'),(4,'Irmah Halimah Bachtiar, S.E., M.Si.','Dosen dan akademisi bidang akuntansi dan keuangan, aktif menulis buku panduan akuntansi dasar untuk mahasiswa dan pemula.'),(6,'vincent',NULL),(7,'Tim Smart Reading Room','Tim pengelola literasi Smart Reading Room.'),(8,'Tim QA Smart Reading Room','Penulis data pengujian sistem.');
/*!40000 ALTER TABLE `authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `publisher` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock` int DEFAULT '0',
  `available_stock` int DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`book_id`),
  KEY `category_id` (`category_id`),
  KEY `author_id` (`author_id`),
  KEY `fk_books_created_by` (`created_by`),
  KEY `fk_books_updated_by` (`updated_by`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  CONSTRAINT `books_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`),
  CONSTRAINT `fk_books_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_books_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,1,1,'Laut Bercerita','Kepustakaan Populer Gramedia (KPG)',2017,'Novel yang mengangkat kisah persahabatan, cinta, kekeluargaan, dan rasa kehilangan para aktivis mahasiswa di masa Orde Baru. Biru Laut menceritakan penyiksaan dan detik-detik terakhirnya sebelum ditenggelamkan ke dasar laut, sementara Asmara Jati berjuang mencari keadilan bagi para korban penghilangan paksa.','covers/laut_bercerita.jpg','Rak F-01',5,5,1,1,'2026-09-23 00:56:35',NULL,'2026-09-23 00:56:35'),(2,2,2,'23:59','Grasindo',2021,'Tentang kau yang tak pernah pulang dan aku yang selalu menunggu. Sebuah kisah refleksi tentang perjumpaan, kebersamaan, perpisahan, dan bagaimana waktu menguji perasaan manusia di penghujung hari.','covers/2359.jpg','Rak R-04',4,2,1,1,'2026-09-23 00:56:35',NULL,'2026-09-23 00:56:35'),(3,3,3,'Data Science dengan Python: Konsep dan Implementasi','Penerbit Gava Media',2021,'Membahas konsep fundamental data science mulai dari pengumpulan data, data preprocessing, eksplorasi data analisis (EDA), hingga pemodelan machine learning menggunakan bahasa pemrograman Python dan berbagai pustaka populernya seperti Pandas, NumPy, dan Scikit-Learn.','covers/data_science_python.png','Rak T-02',6,6,0,1,'2026-09-23 01:04:37',1,'2026-09-23 08:52:27'),(4,4,4,'Akuntansi Dasar: Buku Pintar untuk Pemula','Deepuyuy',2019,'Buku panduan praktis yang menyajikan konsep dasar akuntansi secara sistematis dan mudah dipahami, mulai dari siklus akuntansi, pencatatan transaksi jurnal umum, buku besar, neraca saldo, hingga penyusunan laporan keuangan untuk pemula.','covers/pFkUxgzeuzk3HjmgHbMg7OVxo9KsUtlhpY3lei6q.jpg','Rak E-05',6,5,1,1,'2026-09-23 01:04:37',1,'2026-09-23 08:50:58'),(5,1,2,'Seporsi Mie Ayam Sebelum Mati','Gramedia',2020,'Ale adalah seorang pria berusia 37 tahun yang bekerja sebagai pekerja kantoran (budak korporat) di ibu kota. Ia memiliki fisik yang besar, masalah bau badan, dan merasa hidupnya dipenuhi kesialan. Ale sering dibuli oleh lingkungan sekitar, tidak memiliki teman di kantor, serta tidak mendapatkan dukungan dari keluarganya sendiri.','covers/O5dFagH8CpTWsBYCZ03UARCu9oqExJsHbnfjY6hB.png','Rak B-5',7,7,0,1,'2026-09-23 15:31:36',1,'2026-09-23 08:54:18'),(7,6,6,'oijoijoijoij','ouihoujuhij',2016,'okjoniubgugtvyfrcuycyt','covers/VW83gwF9JnzeRYq7BqyTYjJeL4Hbi7qgESGMCINB.jpg','Rak E-05',18,17,1,1,'2026-09-24 17:40:09',1,'2026-09-24 10:40:52'),(8,8,8,'Buku Uji Denda dan Stok','Smart Reading Room',2026,'Buku khusus untuk menguji denda keterlambatan, penguncian stok, dan notifikasi email.',NULL,'Rak QA-01',1,0,1,1,'2026-09-24 20:47:17',NULL,NULL);
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Fiksi & Sastra','Koleksi novel fiksi, sastra, dan cerita inspiratif'),(2,'Romance & Drama','Koleksi novel percintaan, drama emosional, dan kehidupan'),(3,'Komputer & Teknologi','Buku ilmu komputer, pemrograman, data science, dan teknologi informasi modern'),(4,'Ekonomi & Akuntansi','Buku akuntansi, keuangan, manajemen bisnis, dan ekonomi'),(6,'horror',NULL),(7,'Pendidikan','Panduan belajar, literasi, dan pengembangan keterampilan.'),(8,'Pengujian Sistem','Data khusus untuk pengujian fitur aplikasi.');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ebooks`
--

DROP TABLE IF EXISTS `ebooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebooks` (
  `ebook_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `publisher` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `access_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ebook_id`),
  KEY `category_id` (`category_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `ebooks_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  CONSTRAINT `ebooks_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebooks`
--

LOCK TABLES `ebooks` WRITE;
/*!40000 ALTER TABLE `ebooks` DISABLE KEYS */;
INSERT INTO `ebooks` VALUES (1,7,7,'Panduan Membaca Efektif','Smart Reading Room',2026,'Panduan praktis untuk memahami, mengingat, dan menerapkan isi buku melalui strategi membaca aktif.','ebooks/D71qC27QqxWalAYKSk5oyJOpmtSyw91PAHnljxxz.pdf','Aktif');
/*!40000 ALTER TABLE `ebooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `loan_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `request_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `loan_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Menunggu','Dikonfirmasi','Dipinjam','Gagal','Dikembalikan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `fine_paid_at` timestamp NULL DEFAULT NULL,
  `fine_paid_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`loan_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  KEY `fk_loans_approved_by` (`approved_by`),
  CONSTRAINT `fk_loans_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (3,4,4,'2026-09-23 08:20:35','2026-09-23','2026-09-30',NULL,'Gagal',1,'2026-09-23 01:21:26',NULL,NULL),(4,5,2,'2026-09-23 14:48:10','2026-09-23','2026-10-18','2026-09-23','Dikembalikan',1,'2026-09-23 07:55:04',NULL,NULL),(5,5,4,'2026-09-23 14:52:16','2026-09-23','2026-09-28','2026-09-24','Dikembalikan',1,'2026-09-23 07:55:07',NULL,NULL),(6,4,5,'2026-09-23 15:32:48','2026-09-23','2026-09-30','2026-09-24','Dikembalikan',1,'2026-09-23 08:44:31',NULL,NULL),(7,4,2,'2026-09-24 07:32:35','2026-09-24','2026-10-01','2026-09-24','Dikembalikan',1,'2026-09-24 00:35:59',NULL,NULL),(8,4,1,'2026-09-24 11:34:27','2026-09-24','2026-10-04','2026-09-24','Dikembalikan',1,'2026-09-24 04:34:58',NULL,NULL),(9,4,4,'2026-09-24 12:05:50','2026-09-24','2026-10-01',NULL,'Dipinjam',1,'2026-09-24 10:02:01',NULL,NULL),(14,6,2,'2026-09-24 17:01:11','2026-09-24','2026-10-01',NULL,'Dipinjam',1,'2026-09-24 10:01:57',NULL,NULL),(15,3,2,'2026-09-24 20:18:43','2026-09-24','2026-10-01',NULL,'Dikonfirmasi',1,'2026-09-24 13:19:18',NULL,NULL),(16,3,7,'2026-09-24 20:43:46','2026-09-24','2026-09-25',NULL,'Dikonfirmasi',1,'2026-09-24 13:43:59',NULL,NULL),(17,3,8,'2026-09-24 20:47:17','2026-09-14','2026-09-21',NULL,'Dipinjam',1,'2026-09-14 13:47:17',NULL,NULL);
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_24_093221_update_status_enum_in_loans_table',1),(2,'0001_01_01_000000_create_users_table',1),(3,'0001_01_01_000003_create_library_tables',1),(4,'0001_01_01_000001_create_cache_table',2),(5,'0001_01_01_000002_create_jobs_table',2),(6,'2026_09_24_200000_create_digital_library_tables',3),(7,'2026_09_24_210000_add_fine_payment_to_loans_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `loan_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,3,16,'Pengajuan','Pengajuan peminjaman buku \"oijoijoijoij\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-09-24 13:43:47','Terkirim'),(2,3,16,'Dikonfirmasi','Peminjaman buku \"oijoijoijoij\" sudah dikonfirmasi. Ambil buku paling lambat 26/09/2026 13:43.','2026-09-24 13:43:59','Terkirim'),(3,3,17,'Terlambat','Buku \"Buku Uji Denda dan Stok\" terlambat 3 hari. Denda saat ini Rp3.000. Segera hubungi atau datangi perpustakaan.','2026-09-24 13:47:18','Terkirim');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,3,1,5,'Buku yang sangat emosional dan membuka mata tentang perjuangan kemanusiaan. Sangat direkomendasikan!','2026-09-23 00:56:35'),(2,3,2,5,'Gaya kepenulisan Brian Khrisna selalu berhasil menyentuh perasaan dan relate dengan kehidupan sehari-hari.','2026-09-23 00:56:35'),(3,3,3,5,'Penjelasan data science dan implementasi kodenya sangat runtut dan mudah dipraktikkan langsung di Python.','2026-09-23 01:04:37'),(4,3,4,5,'Sangat cocok untuk yang baru pertama kali belajar akuntansi. Disertai studi kasus pencatatan jurnal yang jelas.','2026-09-23 01:04:37');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `role` enum('Peminjam','Admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Peminjam',
  `nim_nip` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uk_users_nim_nip` (`nim_nip`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','ADM001','Admin Satu',NULL,'admin1@smartreadingroom.test','$2y$12$RUQsk1qjpI3riMatzNwtguFjyv58I/tK/Q/9my4aMs64G3Qfg32c.',NULL,'2026-09-21 17:38:16',NULL),(2,'Admin','ADM002','Admin Dua',NULL,'admin2@smartreadingroom.test','$2y$10$rl9w3q4Lnt8m3rRluEZbOOo4cpjGHa.UdDgPBXpl7w2baqtPETtI2',NULL,'2026-09-21 17:38:16',NULL),(3,'Peminjam','2024001','Budi Santoso','XII IPA 2','budi@test.com','$2y$12$f24EMQC2FjZVxHVRdLSlHe4JKoCBAwOS3BOydPCDvqjydCJesdTpq','1234567890','2026-09-21 18:27:39',NULL),(4,'Peminjam','s','ss','s','qsoudqh@gmail.com','$2y$12$1DqZtWBPPFs28SCXHz6pLuxQjVZAf.vfDKjnT23C41fwxyuKDBTv2','1','2026-09-22 08:03:31',NULL),(5,'Peminjam','1234','juliana martinelli','XII IPS 2','julianamartinelli@gmail.com','$2y$12$GcIUPGccnP9sTGsiwt5sCu.DHVPSIXFbGmjCGcrgP9R9TWnFAGot.','08123456789','2026-09-23 14:47:41',NULL),(6,'Peminjam','164241065','rafan','XII IPA 3','mmadanirafan@gmail.com','$2y$12$L0ECz5BFvSmyVpfg2/S.6e.EDlm0BgdPbXKa6JqIZ7ltLexNZSl1K','08117193666','2026-09-24 16:56:36',NULL),(9,'Peminjam','QASTOK','Penguji Stok','QA','qa.stok@smartreading.test','$2y$12$SyCnIGxGzRTEVA4ZnBe1M.NWAabsuk3Ns4MdkJl7eMBsm0v19I2vu','080000000003','2026-09-24 20:48:23',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'basdat'
--

--
-- Dumping routines for database 'basdat'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-24 21:20:37
