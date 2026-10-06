-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: basdat_github
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
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` int DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_admin` (`admin_id`),
  CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'ACC','loans',1,'Menyetujui peminjaman \"23:59\" oleh ss','2026-09-23 08:14:42'),(2,1,'Tolak','loans',3,'Menolak peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss','2026-09-23 08:21:26'),(3,1,'ACC','loans',4,'Menyetujui peminjaman \"23:59\" oleh juliana martinelli','2026-09-23 14:55:04'),(4,1,'ACC','loans',5,'Menyetujui peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli','2026-09-23 14:55:07'),(5,1,'Kembalikan','loans',4,'Menandai pengembalian \"23:59\" oleh juliana martinelli','2026-09-23 14:55:54'),(6,1,'Tambah','books',5,'Menambahkan buku \"Seporsi Mie Ayam Sebelum Mati\"','2026-09-23 15:31:36'),(7,1,'Kembalikan','loans',1,'Menandai pengembalian \"23:59\" oleh ss','2026-09-23 15:33:50'),(8,1,'Hapus','loans',1,'Menghapus riwayat peminjaman \"23:59\" oleh ss','2026-09-23 15:44:22'),(9,1,'ACC','loans',6,'Menyetujui peminjaman \"Seporsi Mie Ayam Sebelum Mati\" oleh ss','2026-09-23 15:44:31'),(10,1,'Edit','books',4,'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"','2026-09-23 15:45:18'),(11,1,'Edit','books',4,'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"','2026-09-23 15:50:58'),(12,1,'Hapus','books',3,'Menonaktifkan buku \"Data Science dengan Python: Konsep dan Implementasi\"','2026-09-23 15:52:27'),(13,1,'Hapus','books',5,'Menonaktifkan buku \"Seporsi Mie Ayam Sebelum Mati\"','2026-09-23 15:54:18'),(14,1,'ACC','loans',7,'Menyetujui peminjaman \"23:59\" oleh ss','2026-09-24 07:35:59'),(15,1,'Kembalikan','loans',5,'Menandai pengembalian \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh juliana martinelli','2026-09-24 07:36:09'),(16,1,'ACC','loans',8,'Menyetujui peminjaman \"Laut Bercerita\" oleh ss','2026-09-24 11:34:58'),(17,1,'Kembalikan','loans',8,'Menandai pengembalian \"Laut Bercerita\" oleh ss','2026-09-24 11:35:10'),(18,1,'Gagal Otomatis','loans',11,'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-24 16:41:34'),(19,1,'Gagal Otomatis','loans',13,'Peminjaman \"Laut Bercerita\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-24 16:41:34'),(20,1,'Dipinjam','loans',6,'Menandai buku \"Seporsi Mie Ayam Sebelum Mati\" telah diambil manual oleh ss','2026-09-24 16:47:34'),(21,1,'Kembalikan','loans',6,'Menandai pengembalian \"Seporsi Mie Ayam Sebelum Mati\" oleh ss','2026-09-24 16:47:43'),(22,1,'Dipinjam','loans',7,'Menandai buku \"23:59\" telah diambil manual oleh ss','2026-09-24 16:47:48'),(23,1,'ACC','loans',14,'Mengonfirmasi peminjaman \"23:59\" oleh rafan','2026-09-24 17:01:57'),(24,1,'ACC','loans',9,'Mengonfirmasi peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss','2026-09-24 17:02:01'),(25,1,'Kembalikan','loans',7,'Menandai pengembalian \"23:59\" oleh ss','2026-09-24 17:04:16'),(26,1,'Dipinjam','loans',14,'Menandai buku \"23:59\" telah diambil manual oleh rafan','2026-09-24 17:04:23'),(27,1,'Dipinjam','loans',9,'Menandai buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" telah diambil manual oleh ss','2026-09-24 17:04:26'),(28,1,'Tambah','books',6,'Menambahkan buku \"Buku Uji Baru\"','2026-09-24 17:36:47'),(29,1,'Tambah','books',7,'Menambahkan buku \"oijoijoijoij\"','2026-09-24 17:40:09'),(30,1,'Edit','books',7,'Mengubah buku \"oijoijoijoij\"','2026-09-24 17:40:52'),(31,1,'ACC','loans',15,'Mengonfirmasi peminjaman \"23:59\" oleh Budi Santoso','2026-09-24 20:19:18'),(32,1,'ACC','loans',16,'Mengonfirmasi peminjaman \"oijoijoijoij\" oleh Budi Santoso','2026-09-24 20:43:59'),(33,1,'Gagal Otomatis','loans',15,'Peminjaman \"23:59\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-27 19:04:30'),(34,1,'Gagal Otomatis','loans',16,'Peminjaman \"oijoijoijoij\" oleh Budi Santoso otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-09-27 19:04:30'),(35,1,'Edit','books',2,'Mengubah buku \"23:59\"','2026-09-27 19:18:25'),(36,1,'Edit','books',2,'Mengubah buku \"23:59\"','2026-09-27 19:18:31'),(37,1,'Edit','books',4,'Mengubah buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\"','2026-09-27 19:19:20'),(38,1,'Hapus','books',8,'Menonaktifkan buku \"Buku Uji Denda dan Stok\"','2026-09-27 19:25:28'),(39,1,'ACC','loans',19,'Mengonfirmasi peminjaman \"23:59\" oleh ss','2026-10-02 18:04:15'),(40,1,'Tambah','books',12,'Menambahkan buku \"Computer Networking: A Top-Down Approach\"','2026-10-02 18:08:42'),(41,1,'Denda Lunas','loans',9,'Mengubah status denda peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss menjadi Lunas.','2026-10-02 18:30:06'),(42,1,'Dipinjam','loans',19,'Menandai buku \"23:59\" telah diambil manual oleh ss','2026-10-02 18:30:31'),(43,1,'Denda Belum Lunas','loans',9,'Mengubah status denda peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss menjadi Belum Lunas.','2026-10-02 18:31:15'),(44,1,'Denda Lunas','loans',9,'Mengubah status denda peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss menjadi Lunas.','2026-10-02 18:31:19'),(45,1,'Edit','books',2,'Mengubah buku \"23:59\"','2026-10-02 18:51:51'),(46,1,'ACC','loans',20,'Mengonfirmasi peminjaman \"Computer Networking: A Top-Down Approach\" oleh ss','2026-10-02 19:04:40'),(47,1,'Dipinjam','loans',20,'Menandai buku \"Computer Networking: A Top-Down Approach\" telah diambil manual oleh ss','2026-10-02 19:08:22'),(48,1,'Kembalikan','loans',20,'Menandai pengembalian \"Computer Networking: A Top-Down Approach\" oleh ss','2026-10-02 19:08:27'),(49,1,'Hapus','loans',20,'Menghapus riwayat peminjaman \"Computer Networking: A Top-Down Approach\" oleh ss','2026-10-02 19:08:31'),(50,1,'ACC','loans',21,'Mengonfirmasi peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss','2026-10-02 19:12:34'),(51,1,'Kembalikan','loans',19,'Menandai pengembalian \"23:59\" oleh ss','2026-10-02 19:21:49'),(52,1,'Kembalikan','loans',9,'Menandai pengembalian \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh ss','2026-10-02 19:43:42'),(53,1,'Dipinjam','loans',21,'Menandai buku \"Langsung Mahir Python untuk Pemula\" telah diambil manual oleh ss','2026-10-02 20:06:07'),(54,1,'Denda Lunas','loans',21,'Mengubah status denda peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss menjadi Lunas.','2026-10-02 20:38:36'),(55,1,'Denda Lunas','loans',14,'Mengubah status denda peminjaman \"23:59\" oleh rafan menjadi Lunas dan menandai buku sudah dikembalikan.','2026-10-02 20:50:09'),(56,1,'Denda Belum Lunas','loans',21,'Mengubah status denda peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss menjadi Belum Lunas.','2026-10-02 20:50:21'),(57,1,'Denda Lunas','loans',21,'Mengubah status denda peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss menjadi Lunas dan menandai buku sudah dikembalikan.','2026-10-02 20:50:30'),(58,1,'Denda Belum Lunas','loans',21,'Mengubah status denda peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss menjadi Belum Lunas.','2026-10-02 20:50:37'),(59,1,'Denda Lunas','loans',21,'Mengubah status denda peminjaman \"Langsung Mahir Python untuk Pemula\" oleh ss menjadi Lunas.','2026-10-02 20:50:40'),(60,1,'Denda Lunas','loans',17,'Mengubah status denda peminjaman \"Buku Uji Denda dan Stok\" oleh Budi Santoso menjadi Lunas dan menandai buku sudah dikembalikan.','2026-10-02 20:51:06'),(61,1,'Edit','books',1,'Mengubah buku \"Laut Bercerita\"','2026-10-02 20:58:19'),(62,1,'ACC','loans',24,'Mengonfirmasi peminjaman \"23:59\" oleh ss','2026-10-03 11:14:19'),(63,1,'Dipinjam','loans',24,'Menandai buku \"23:59\" telah diambil manual oleh ss','2026-10-03 11:14:58'),(64,1,'ACC','loans',25,'Mengonfirmasi peminjaman \"23:59\" oleh rafan','2026-10-03 11:36:13'),(65,1,'ACC','loans',26,'Mengonfirmasi peminjaman \"Computer Networking: A Top-Down Approach\" oleh rafan','2026-10-03 11:36:16'),(66,1,'Dipinjam','loans',26,'Menandai buku \"Computer Networking: A Top-Down Approach\" telah diambil manual oleh rafan','2026-10-03 11:36:26'),(67,1,'ACC','loans',27,'Mengonfirmasi peminjaman \"oijoijoijoij\" oleh ss','2026-10-03 12:05:17'),(68,1,'Dipinjam','loans',27,'Menandai buku \"oijoijoijoij\" telah diambil manual oleh ss','2026-10-03 12:07:30'),(69,1,'ACC','loans',28,'Mengonfirmasi peminjaman \"Laut Bercerita\" oleh ss','2026-10-04 13:42:47'),(70,1,'Kembalikan','loans',26,'Menandai pengembalian \"Computer Networking: A Top-Down Approach\" oleh rafan','2026-10-04 13:42:54'),(71,1,'Tambah','books',13,'Menambahkan buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\"','2026-10-04 13:48:53'),(72,1,'Edit','books',13,'Mengubah buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\"','2026-10-04 13:49:23'),(73,1,'ACC','loans',29,'Mengonfirmasi peminjaman \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" oleh rafan','2026-10-04 13:50:53'),(74,1,'Dipinjam','loans',29,'Menandai buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" telah diambil manual oleh rafan','2026-10-04 13:50:59'),(75,1,'Kembalikan','loans',29,'Menandai pengembalian \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" oleh rafan','2026-10-04 13:54:40'),(76,1,'ACC','loans',30,'Mengonfirmasi peminjaman \"Akuntansi Dasar: Buku Pintar untuk Pemula\" oleh rafan','2026-10-04 14:07:58'),(77,1,'Dipinjam','loans',30,'Menandai buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" telah diambil manual oleh rafan','2026-10-04 14:08:06'),(78,1,'Hapus','authors',7,'Menghapus penulis \"Tim Smart Reading Room\"','2026-10-04 18:19:54'),(79,1,'Hapus','categories',7,'Menghapus kategori \"Pendidikan\"','2026-10-04 18:20:05'),(80,1,'Hapus','books',2,'Menonaktifkan buku \"23:59\"','2026-10-04 18:23:59'),(81,1,'Aktifkan','books',2,'Mengaktifkan kembali buku \"23:59\"','2026-10-04 18:31:24'),(82,1,'Aktifkan','books',3,'Mengaktifkan kembali buku \"Data Science dengan Python: Konsep dan Implementasi\"','2026-10-04 18:33:01'),(83,1,'Aktifkan','books',5,'Mengaktifkan kembali buku \"Seporsi Mie Ayam Sebelum Mati\"','2026-10-04 18:33:04'),(85,1,'Denda Lunas','loans',32,'Mengubah status denda peminjaman \"Pemrograman Python untuk Pemula\" oleh Budi Santoso menjadi Lunas dan menandai buku sudah dikembalikan.','2026-10-04 21:59:00'),(86,1,'Edit','books',2,'Mengubah buku \"23:59\"','2026-10-04 22:05:46'),(87,1,'Gagal Otomatis','loans',25,'Peminjaman \"23:59\" oleh rafan otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-10-06 14:39:32'),(88,1,'Gagal Otomatis','loans',28,'Peminjaman \"Laut Bercerita\" oleh ss otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.','2026-10-06 14:39:32'),(89,1,'ACC','loans',34,'Mengonfirmasi peminjaman \"Computer Networking: A Top-Down Approach\" oleh rafan','2026-10-06 15:05:06'),(90,1,'Edit','books',2,'Mengubah buku \"23:59\"','2026-10-06 16:23:14');
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
  `author_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `biography` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authors`
--

LOCK TABLES `authors` WRITE;
/*!40000 ALTER TABLE `authors` DISABLE KEYS */;
INSERT INTO `authors` VALUES (1,'Leila S. Chudori','Penulis dan jurnalis senior Indonesia, penulis novel peraih penghargaan seperti Laut Bercerita dan Pulang.'),(2,'Brian Khrisna','Penulis novel populer Indonesia yang dikenal luas lewat karya-karya bertema romansa, kehidupan urban, dan komedi.'),(3,'Prof. Dr. Kristoko Dwi Hartomo, dkk.','Pakar dan akademisi senior ilmu komputer serta data science di Indonesia, aktif menulis buku referensi pemrograman dan sains data.'),(4,'Irmah Halimah Bachtiar, S.E., M.Si.','Dosen dan akademisi bidang akuntansi dan keuangan, aktif menulis buku panduan akuntansi dasar untuk mahasiswa dan pemula.'),(6,'vincent',NULL),(8,'Tim QA Smart Reading Room','Penulis data pengujian sistem.'),(9,'Jubilee Enterprise','Tim penulis buku komputer dan teknologi informasi yang produktif, banyak menerbitkan buku panduan pemrograman melalui Elex Media Komputindo.'),(10,'Sugeng Winardi, S.Kom., M.T.','Dosen dan akademisi bidang ilmu komputer, penulis buku ajar pemrograman yang diterbitkan oleh Deepublish.'),(11,'Aris Defiana, S.Kom','Guru berlatar belakang teknik informatika yang berpengalaman menulis buku, termasuk buku pelajaran TIK.'),(12,'Jim Kurose',NULL),(13,'Prof. Drs. Ec. Ir. Riyanarto Sarno, M.Sc., Ph.D.,',NULL),(14,'Keith Ross',NULL);
/*!40000 ALTER TABLE `authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_authors`
--

DROP TABLE IF EXISTS `book_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_authors` (
  `book_id` int NOT NULL,
  `author_id` int NOT NULL,
  `urutan` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`book_id`,`author_id`),
  KEY `book_authors_author_id_foreign` (`author_id`),
  CONSTRAINT `book_authors_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE RESTRICT,
  CONSTRAINT `book_authors_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_authors`
--

LOCK TABLES `book_authors` WRITE;
/*!40000 ALTER TABLE `book_authors` DISABLE KEYS */;
INSERT INTO `book_authors` VALUES (1,1,1),(2,2,1),(2,4,2),(3,3,1),(4,4,1),(5,2,1),(7,6,1),(8,8,1),(9,9,1),(10,10,1),(11,11,1),(12,12,1),(12,14,2),(13,13,1);
/*!40000 ALTER TABLE `book_authors` ENABLE KEYS */;
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
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `publisher` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock` int DEFAULT '0',
  `available_stock` int DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`book_id`),
  KEY `category_id` (`category_id`),
  KEY `fk_books_created_by` (`created_by`),
  KEY `fk_books_updated_by` (`updated_by`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  CONSTRAINT `fk_books_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_books_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,1,'Laut Bercerita','Kepustakaan Populer Gramedia (KPG)',2017,'Novel yang mengangkat kisah persahabatan, cinta, kekeluargaan, dan rasa kehilangan para aktivis mahasiswa di masa Orde Baru. Biru Laut menceritakan penyiksaan dan detik-detik terakhirnya sebelum ditenggelamkan ke dasar laut, sementara Asmara Jati berjuang mencari keadilan bagi para korban penghilangan paksa.','covers/yhm1rHPwvYPpzroAykzIrzC7xGF1w6D8iyuSYHrx.jpg','Rak F-01',5,5,1,1,'2026-09-23 00:56:35',1,'2026-10-02 20:58:19'),(2,2,'23:59','Grasindo',2021,'Tentang kau yang tak pernah pulang dan aku yang selalu menunggu. Sebuah kisah refleksi tentang perjumpaan, kebersamaan, perpisahan, dan bagaimana waktu menguji perasaan manusia di penghujung hari.','covers/z35FZRahIKtQQx4JLFFjLoemyeKjHmSo09VWXG6M.jpg','Rak R-04',2,1,1,1,'2026-09-23 00:56:35',1,'2026-10-06 16:23:14'),(3,3,'Data Science dengan Python: Konsep dan Implementasi','Penerbit Gava Media',2021,'Membahas konsep fundamental data science mulai dari pengumpulan data, data preprocessing, eksplorasi data analisis (EDA), hingga pemodelan machine learning menggunakan bahasa pemrograman Python dan berbagai pustaka populernya seperti Pandas, NumPy, dan Scikit-Learn.','covers/data_science_python.png','Rak T-02',6,6,1,1,'2026-09-23 01:04:37',1,'2026-10-04 18:33:01'),(4,4,'Akuntansi Dasar: Buku Pintar untuk Pemula','Deepuyuy',2019,'Buku panduan praktis yang menyajikan konsep dasar akuntansi secara sistematis dan mudah dipahami, mulai dari siklus akuntansi, pencatatan transaksi jurnal umum, buku besar, neraca saldo, hingga penyusunan laporan keuangan untuk pemula.','covers/zPeL4Wnc0d5Hnue4iwf3rg8O7eVpVl2vZUXCg5pD.jpg','Rak E-05',6,5,1,1,'2026-09-23 01:04:37',1,'2026-09-27 19:19:20'),(5,1,'Seporsi Mie Ayam Sebelum Mati','Gramedia',2020,'Ale adalah seorang pria berusia 37 tahun yang bekerja sebagai pekerja kantoran (budak korporat) di ibu kota. Ia memiliki fisik yang besar, masalah bau badan, dan merasa hidupnya dipenuhi kesialan. Ale sering dibuli oleh lingkungan sekitar, tidak memiliki teman di kantor, serta tidak mendapatkan dukungan dari keluarganya sendiri.','covers/O5dFagH8CpTWsBYCZ03UARCu9oqExJsHbnfjY6hB.png','Rak B-5',7,7,1,1,'2026-09-23 15:31:36',1,'2026-10-04 18:33:04'),(7,6,'oijoijoijoij','ouihoujuhij',2016,'okjoniubgugtvyfrcuycyt','covers/VW83gwF9JnzeRYq7BqyTYjJeL4Hbi7qgESGMCINB.jpg','Rak E-05',18,17,1,1,'2026-09-24 17:40:09',1,'2026-09-24 17:40:52'),(8,8,'Buku Uji Denda dan Stok','Smart Reading Room',2026,'Buku khusus untuk menguji denda keterlambatan, penguncian stok, dan notifikasi email.',NULL,'Rak QA-01',1,1,0,1,'2026-09-24 20:47:17',1,'2026-09-27 19:25:28'),(9,3,'Python untuk Programmer Pemula','Elex Media Komputindo',2019,'Buku bergizi untuk programmer pemula yang ingin mempelajari Python, bahasa pemrograman yang mudah dipelajari dan menjanjikan untuk karier masa depan. Membahas cara instalasi tools, struktur Python, fungsi, percabangan dan perulangan, penggunaan modul, hingga pemrograman database menggunakan Python dan MySQL.','covers/python_untuk_programmer_pemula.jpg','Rak T-03',5,5,1,1,'2026-09-27 19:29:57',NULL,NULL),(10,3,'Pemrograman Python untuk Pemula','Deepublish',2023,'Buku pengantar pemrograman Python yang disusun bertahap dari materi paling mudah hingga yang lebih kompleks. Selain menyajikan contoh-contoh program, setiap bagian dilengkapi evaluasi agar pembaca dapat mengukur pemahamannya sendiri.','covers/pemrograman_python_untuk_pemula.webp','Rak T-03',5,5,1,1,'2026-09-27 19:29:57',NULL,NULL),(11,3,'Langsung Mahir Python untuk Pemula','Anak Hebat Indonesia',2024,'Panduan praktis memahami dasar pemrograman Python lengkap dengan contoh kode dan output-nya. Membahas instalasi, variabel, tipe data, percabangan, perulangan, fungsi, modul, list, tuple, dictionary, set, operasi file, penanganan exception, hingga pemrograman berorientasi objek (OOP).','covers/langsung_mahir_python_untuk_pemula.jpg','Rak T-03',5,5,1,1,'2026-09-27 19:29:57',NULL,NULL),(12,3,'Computer Networking: A Top-Down Approach','Pearson',2002,'Computer Networking continues with an early emphasis on application-layer paradigms and application programming interfaces (the top layer), encouraging a hands-on experience with protocols and networking concepts, before working down the protocol stack to more abstract layers.','covers/Ooud8r7aji6G5mEdtt162wcaXOurMklwOk1ycxez.jpg','Rak R-04',5,4,1,1,'2026-10-02 18:08:42',NULL,NULL),(13,3,'Machine Learning dan Deep Learning: Konsep dan Pemrograman Python','Penerbit Andi',2023,'Buku ini untuk pembelajaran Machine Learning (ML) dan Deep Learning (DL) secara\r\nkonsep dan pemrograman. Pembahasan konsep memberi landasan keilmuan ML dan DL,\r\nkemudian pemaparan dasar pemrograman untuk memandu penulisan program ML dan DL secara mudah dimengerti. Aplikasi ML dan DL juga dibahas untuk memandu pembuatan program yang lebih lengkap. Pada setiap materi akan disajikan contoh dan program python sehingga dapat memudahkan Anda untuk mengimplementasikan secara langsung.','covers/1CWu7FfmRtCYKuqb1o4MieZ49BOFiBSjNUH2BFx0.jpg','Rak A-1',1,1,1,1,'2026-10-04 13:48:53',1,'2026-10-04 13:49:23');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Fiksi & Sastra','Koleksi novel fiksi, sastra, dan cerita inspiratif'),(2,'Romance & Drama','Koleksi novel percintaan, drama emosional, dan kehidupan'),(3,'Komputer & Teknologi','Buku ilmu komputer, pemrograman, data science, dan teknologi informasi modern'),(4,'Ekonomi & Akuntansi','Buku akuntansi, keuangan, manajemen bisnis, dan ekonomi'),(6,'horror',NULL),(8,'Pengujian Sistem','Data khusus untuk pengujian fitur aplikasi.');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
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
  `status` enum('Menunggu','Dikonfirmasi','Dipinjam','Gagal','Dikembalikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `fine_paid_at` timestamp NULL DEFAULT NULL,
  `fine_paid_by` int DEFAULT NULL,
  PRIMARY KEY (`loan_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  KEY `fk_loans_approved_by` (`approved_by`),
  KEY `fk_loans_fine_paid_by` (`fine_paid_by`),
  CONSTRAINT `fk_loans_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_loans_fine_paid_by` FOREIGN KEY (`fine_paid_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (3,4,4,'2026-09-23 08:20:35','2026-09-23','2026-09-30',NULL,'Gagal',1,'2026-09-23 08:21:26',NULL,NULL),(4,5,2,'2026-09-23 14:48:10','2026-09-23','2026-10-18','2026-09-23','Dikembalikan',1,'2026-09-23 14:55:04',NULL,NULL),(5,5,4,'2026-09-23 14:52:16','2026-09-23','2026-09-28','2026-09-24','Dikembalikan',1,'2026-09-23 14:55:07',NULL,NULL),(6,4,5,'2026-09-23 15:32:48','2026-09-23','2026-09-30','2026-09-24','Dikembalikan',1,'2026-09-23 15:44:31',NULL,NULL),(7,4,2,'2026-09-24 07:32:35','2026-09-24','2026-10-01','2026-09-24','Dikembalikan',1,'2026-09-24 07:35:59',NULL,NULL),(8,4,1,'2026-09-24 11:34:27','2026-09-24','2026-10-04','2026-09-24','Dikembalikan',1,'2026-09-24 11:34:58',NULL,NULL),(9,4,4,'2026-09-24 12:05:50','2026-09-24','2026-10-01','2026-10-02','Dikembalikan',1,'2026-09-24 17:02:01','2026-10-02 11:31:19',1),(14,6,2,'2026-09-24 17:01:11','2026-09-24','2026-10-01','2026-10-02','Dikembalikan',1,'2026-09-24 17:01:57','2026-10-02 13:50:09',1),(15,3,2,'2026-09-24 20:18:43','2026-09-24','2026-10-01',NULL,'Gagal',1,'2026-09-24 20:19:18',NULL,NULL),(16,3,7,'2026-09-24 20:43:46','2026-09-24','2026-09-25',NULL,'Gagal',1,'2026-09-24 20:43:59',NULL,NULL),(17,3,8,'2026-09-24 20:47:17','2026-09-14','2026-09-21','2026-10-02','Dikembalikan',1,'2026-09-14 20:47:17','2026-10-02 13:51:06',1),(19,4,2,'2026-09-27 20:59:07','2026-09-27','2026-10-04','2026-10-02','Dikembalikan',1,'2026-10-02 18:04:15',NULL,NULL),(21,4,11,'2026-09-25 08:00:00','2026-09-25','2026-09-30','2026-10-02','Dikembalikan',1,'2026-09-25 16:00:00','2026-10-02 13:50:40',1),(24,4,2,'2026-10-03 11:12:49','2026-10-03','2026-10-10',NULL,'Dipinjam',1,'2026-10-03 11:14:19',NULL,NULL),(25,6,2,'2026-10-03 11:19:22',NULL,'2026-10-04',NULL,'Gagal',1,'2026-10-03 11:36:13',NULL,NULL),(26,6,12,'2026-10-03 11:21:31','2026-10-03','2026-10-10','2026-10-04','Dikembalikan',1,'2026-10-03 11:36:16',NULL,NULL),(27,4,7,'2026-10-03 12:04:38','2026-10-03','2026-10-10',NULL,'Dipinjam',1,'2026-10-03 12:05:17',NULL,NULL),(28,4,1,'2026-10-03 13:17:33',NULL,'2026-10-08',NULL,'Gagal',1,'2026-10-04 13:42:47',NULL,NULL),(29,6,13,'2026-10-04 13:50:31','2026-10-04','2026-10-11','2026-10-04','Dikembalikan',1,'2026-10-04 13:50:53',NULL,NULL),(30,6,4,'2026-10-04 14:06:57','2026-10-04','2026-10-11',NULL,'Dipinjam',1,'2026-10-04 14:07:58',NULL,NULL),(32,3,10,'2026-09-23 09:15:00','2026-09-24','2026-10-01','2026-10-04','Dikembalikan',1,'2026-09-23 10:30:00','2026-10-04 14:59:00',1),(34,6,12,'2026-10-06 15:04:24',NULL,'2026-10-20',NULL,'Dikonfirmasi',1,'2026-10-06 15:05:06',NULL,NULL),(35,6,2,'2026-10-06 16:53:17',NULL,'2026-10-16',NULL,'Menunggu',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
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
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,3,16,'Pengajuan','Pengajuan peminjaman buku \"oijoijoijoij\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-09-24 20:43:47','Dibaca'),(2,3,16,'Dikonfirmasi','Peminjaman buku \"oijoijoijoij\" sudah dikonfirmasi. Ambil buku paling lambat 26/09/2026 13:43.','2026-09-24 20:43:59','Dibaca'),(3,3,17,'Terlambat','Buku \"Buku Uji Denda dan Stok\" terlambat 3 hari. Denda saat ini Rp3.000. Segera hubungi atau datangi perpustakaan.','2026-09-24 20:47:18','Dibaca'),(4,3,15,'Gagal','Peminjaman buku \"23:59\" dibatalkan otomatis karena melewati tenggat pengambilan.','2026-09-27 19:04:30','Dibaca'),(5,3,16,'Gagal','Peminjaman buku \"oijoijoijoij\" dibatalkan otomatis karena melewati tenggat pengambilan.','2026-09-27 19:04:30','Dibaca'),(6,4,NULL,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-09-27 20:08:19','Dibaca'),(7,4,19,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-09-27 20:59:07','Dibaca'),(8,4,19,'Dikonfirmasi','Peminjaman buku \"23:59\" sudah dikonfirmasi. Ambil buku paling lambat 04/10/2026 11:04.','2026-10-02 18:04:15','Dibaca'),(9,4,19,'Dipinjam','Buku \"23:59\" tercatat sudah diambil. Batas pengembalian: 04/10/2026.','2026-10-02 18:30:31','Dibaca'),(10,4,NULL,'Pengajuan','Pengajuan peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-02 19:03:17','Dibaca'),(11,4,NULL,'Dikonfirmasi','Peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah dikonfirmasi. Ambil buku paling lambat 04/10/2026 12:04.','2026-10-02 19:04:40','Dibaca'),(12,4,NULL,'Dipinjam','Buku \"Computer Networking: A Top-Down Approach\" tercatat sudah diambil. Batas pengembalian: 11/10/2026.','2026-10-02 19:08:22','Dibaca'),(13,4,NULL,'Dikembalikan','Pengembalian buku \"Computer Networking: A Top-Down Approach\" sudah tercatat. Terima kasih.','2026-10-02 19:08:27','Dibaca'),(14,4,21,'Pengajuan','Pengajuan peminjaman buku \"Langsung Mahir Python untuk Pemula\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-02 19:11:48','Dibaca'),(15,4,21,'Dikonfirmasi','Peminjaman buku \"Langsung Mahir Python untuk Pemula\" sudah dikonfirmasi. Ambil buku paling lambat 04/10/2026 12:12.','2026-10-02 19:12:34','Dibaca'),(16,4,19,'Dikembalikan','Pengembalian buku \"23:59\" sudah tercatat. Terima kasih.','2026-10-02 19:21:49','Dibaca'),(17,4,9,'Dikembalikan','Pengembalian buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" sudah tercatat. Terima kasih.','2026-10-02 19:43:42','Dibaca'),(18,4,NULL,'Pengajuan','Pengajuan peminjaman buku \"Laut Bercerita\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-02 19:55:50','Dibaca'),(19,4,21,'Dipinjam','Buku \"Langsung Mahir Python untuk Pemula\" tercatat sudah diambil. Batas pengembalian: 09/10/2026.','2026-10-02 20:06:07','Dibaca'),(20,4,NULL,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-02 20:13:13','Dibaca'),(21,6,14,'Dikembalikan','Denda Rp1.000 untuk buku \"23:59\" sudah lunas dan pengembalian buku sudah tercatat. Terima kasih.','2026-10-02 20:50:10','Dibaca'),(22,4,21,'Dikembalikan','Denda Rp2.000 untuk buku \"Langsung Mahir Python untuk Pemula\" sudah lunas dan pengembalian buku sudah tercatat. Terima kasih.','2026-10-02 20:50:30','Dibaca'),(23,3,17,'Dikembalikan','Denda Rp11.000 untuk buku \"Buku Uji Denda dan Stok\" sudah lunas dan pengembalian buku sudah tercatat. Terima kasih.','2026-10-02 20:51:06','Dibaca'),(24,4,24,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-03 11:12:49','Dibaca'),(25,4,24,'Dikonfirmasi','Peminjaman buku \"23:59\" sudah dikonfirmasi. Ambil buku paling lambat 05/10/2026 04:14.','2026-10-03 11:14:19','Dibaca'),(26,4,24,'Dipinjam','Buku \"23:59\" tercatat sudah diambil. Batas pengembalian: 10/10/2026.','2026-10-03 11:14:58','Dibaca'),(27,6,25,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-03 11:19:22','Dibaca'),(28,6,26,'Pengajuan','Pengajuan peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-03 11:21:31','Dibaca'),(29,6,25,'Dikonfirmasi','Peminjaman buku \"23:59\" sudah dikonfirmasi. Ambil buku paling lambat 05/10/2026 04:36.','2026-10-03 11:36:13','Dibaca'),(30,6,26,'Dikonfirmasi','Peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah dikonfirmasi. Ambil buku paling lambat 05/10/2026 04:36.','2026-10-03 11:36:16','Dibaca'),(31,6,26,'Dipinjam','Buku \"Computer Networking: A Top-Down Approach\" tercatat sudah diambil. Batas pengembalian: 10/10/2026.','2026-10-03 11:36:26','Dibaca'),(32,4,27,'Pengajuan','Pengajuan peminjaman buku \"oijoijoijoij\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-03 12:04:38','Dibaca'),(33,4,27,'Dikonfirmasi','Peminjaman buku \"oijoijoijoij\" sudah dikonfirmasi. Ambil buku paling lambat 05/10/2026 05:05.','2026-10-03 12:05:17','Dibaca'),(34,4,27,'Dipinjam','Buku \"oijoijoijoij\" tercatat sudah diambil. Batas pengembalian: 10/10/2026.','2026-10-03 12:07:30','Dibaca'),(35,4,28,'Pengajuan','Pengajuan peminjaman buku \"Laut Bercerita\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-03 13:17:33','Dibaca'),(36,4,28,'Dikonfirmasi','Peminjaman buku \"Laut Bercerita\" sudah dikonfirmasi. Ambil buku paling lambat 06/10/2026 06:42.','2026-10-04 13:42:47','Dibaca'),(37,6,26,'Dikembalikan','Pengembalian buku \"Computer Networking: A Top-Down Approach\" sudah tercatat. Terima kasih.','2026-10-04 13:42:54','Dibaca'),(38,6,29,'Pengajuan','Pengajuan peminjaman buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-04 13:50:32','Dibaca'),(39,6,29,'Dikonfirmasi','Peminjaman buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" sudah dikonfirmasi. Ambil buku paling lambat 06/10/2026 06:50.','2026-10-04 13:50:53','Dibaca'),(40,6,29,'Dipinjam','Buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" tercatat sudah diambil. Batas pengembalian: 11/10/2026.','2026-10-04 13:50:59','Dibaca'),(41,6,29,'Dikembalikan','Pengembalian buku \"Machine Learning dan Deep Learning: Konsep dan Pemrograman Python\" sudah tercatat. Terima kasih.','2026-10-04 13:54:40','Dibaca'),(42,6,30,'Pengajuan','Pengajuan peminjaman buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-04 14:06:57','Dibaca'),(43,6,30,'Dikonfirmasi','Peminjaman buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" sudah dikonfirmasi. Ambil buku paling lambat 06/10/2026 14:07.','2026-10-04 14:07:58','Dibaca'),(44,6,30,'Dipinjam','Buku \"Akuntansi Dasar: Buku Pintar untuk Pemula\" tercatat sudah diambil. Batas pengembalian: 11/10/2026.','2026-10-04 14:08:06','Dibaca'),(45,3,32,'Dikembalikan','Denda Rp3.000 untuk buku \"Pemrograman Python untuk Pemula\" sudah lunas dan pengembalian buku sudah tercatat. Terima kasih.','2026-10-04 21:59:01','Dibaca'),(46,6,NULL,'Pengajuan','Pengajuan peminjaman buku \"Laut Bercerita\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-04 22:51:46','Dibaca'),(47,6,25,'Gagal','Peminjaman buku \"23:59\" dibatalkan otomatis karena melewati tenggat pengambilan.','2026-10-06 14:39:32','Dibaca'),(48,4,28,'Gagal','Peminjaman buku \"Laut Bercerita\" dibatalkan otomatis karena melewati tenggat pengambilan.','2026-10-06 14:39:32','Dibaca'),(49,6,34,'Pengajuan','Pengajuan peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-06 15:04:24','Dibaca'),(50,6,34,'Dikonfirmasi','Peminjaman buku \"Computer Networking: A Top-Down Approach\" sudah dikonfirmasi. Ambil buku paling lambat 08/10/2026 15:05.','2026-10-06 15:05:06','Dibaca'),(51,6,35,'Pengajuan','Pengajuan peminjaman buku \"23:59\" sudah diterima dan sedang menunggu konfirmasi admin.','2026-10-06 16:53:17','Dibaca');
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
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,3,1,5,'Buku yang sangat emosional dan membuka mata tentang perjuangan kemanusiaan. Sangat direkomendasikan!','2026-09-23 00:56:35'),(2,3,2,5,'Gaya kepenulisan Brian Khrisna selalu berhasil menyentuh perasaan dan relate dengan kehidupan sehari-hari.','2026-09-23 00:56:35'),(3,3,3,5,'Penjelasan data science dan implementasi kodenya sangat runtut dan mudah dipraktikkan langsung di Python.','2026-09-23 01:04:37'),(4,3,4,5,'Sangat cocok untuk yang baru pertama kali belajar akuntansi. Disertai studi kasus pencatatan jurnal yang jelas.','2026-09-23 01:04:37'),(6,4,2,5,'adadlakjdlakdj','2026-10-02 19:35:00'),(7,4,11,5,'bukunya membantu','2026-10-03 10:35:12'),(8,4,4,4,'kurang','2026-10-03 11:06:10'),(9,6,11,5,'mantap','2026-10-03 11:21:14'),(10,6,9,4,'bagus','2026-10-03 11:21:24'),(12,6,12,4,'saya lupa pembuatnya antek antek asing','2026-10-03 11:22:32');
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
  `role` enum('Peminjam','Admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Peminjam',
  `nim_nip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
INSERT INTO `users` VALUES (1,'Admin','ADM001','Admin Satu',NULL,'admin1@smartreadingroom.test','$2y$12$RUQsk1qjpI3riMatzNwtguFjyv58I/tK/Q/9my4aMs64G3Qfg32c.',NULL,'2026-09-21 17:38:16',NULL),(2,'Admin','ADM002','Admin Dua',NULL,'admin2@smartreadingroom.test','$2y$10$rl9w3q4Lnt8m3rRluEZbOOo4cpjGHa.UdDgPBXpl7w2baqtPETtI2',NULL,'2026-09-21 17:38:16',NULL),(3,'Peminjam','2024001','Budi Santoso','XII IPA 2','budi@test.com','$2y$12$f24EMQC2FjZVxHVRdLSlHe4JKoCBAwOS3BOydPCDvqjydCJesdTpq','1234567890','2026-09-21 18:27:39',NULL),(4,'Peminjam','s','ss','s','qsoudqh@gmail.com','$2y$12$1DqZtWBPPFs28SCXHz6pLuxQjVZAf.vfDKjnT23C41fwxyuKDBTv2','1','2026-09-22 08:03:31',NULL),(5,'Peminjam','1234','juliana martinelli','XII IPS 2','julianamartinelli@gmail.com','$2y$12$GcIUPGccnP9sTGsiwt5sCu.DHVPSIXFbGmjCGcrgP9R9TWnFAGot.','08123456789','2026-09-23 14:47:41',NULL),(6,'Peminjam','164241065','rafan','XII IPA 3','mmadanirafan@gmail.com','$2y$12$zWsrXovOBDm0wqYKUHEjtuh6CIUrwCTodukKmTxQhh2lDuqNt4vl6','08117193666','2026-09-24 16:56:36','R3lXm35XisUD0fv3zCVsAKwq7KD4MMYTriQc9PsnDcpXENltgb6TnpuMaYuS'),(9,'Peminjam','QASTOK','Penguji Stok','QA','qa.stok@smartreading.test','$2y$12$SyCnIGxGzRTEVA4ZnBe1M.NWAabsuk3Ns4MdkJl7eMBsm0v19I2vu','080000000003','2026-09-24 20:48:23',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-06 17:00:50
