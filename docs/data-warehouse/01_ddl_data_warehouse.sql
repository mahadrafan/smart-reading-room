-- =====================================================================
-- SMART READING ROOM - DATA WAREHOUSE (Kelompok G)   [REVISI 2]
-- File 1 dari 4 : DDL (membuat database dan tabel data warehouse)
--
-- Tiga data warehouse dalam satu database `basdat_dw`, disusun sebagai
-- FACT CONSTELLATION (galaxy schema): tiga tabel fakta berbagi SATU tabel
-- dimensi bersama, yaitu dim_waktu. Dimensi lainnya milik masing-masing fakta.
--
--   Dimensi bersama : dim_waktu (role-playing dimension)
--   DW 1  Analisis Peminjaman Buku   fact_peminjaman
--         dim_buku_pinjam, dim_waktu (peran: tanggal pengajuan), dim_peminjam_pinjam, dim_status_pinjam
--   DW 2  Analisis Kepuasan Pembaca  fact_ulasan
--         dim_buku_ulasan, dim_waktu (peran: tanggal ulasan), dim_pengulas, dim_rating
--   DW 3  Analisis Keterlambatan     fact_keterlambatan
--         dim_kategori_buku, dim_waktu (peran: tanggal jatuh tempo), dim_peminjam_kembali, dim_status_kembali
--
-- Nama kolom FK di tiap fakta tetap menyebut perannya (waktu_pengajuan_key,
-- waktu_ulasan_key, waktu_jatuh_tempo_key), walau semuanya menunjuk dim_waktu.
--
-- Peminjam yang dianalisis HANYA SISWA (dikelompokkan per tingkat dan kelas);
-- transaksi milik tenaga pendidik tidak dimuat ke data warehouse.
--
-- Setiap tabel fakta memakai surrogate key AUTO_INCREMENT sebagai primary key.
-- Nomor transaksi asli (loan_id / review_id) tetap disimpan sebagai kolom biasa
-- dengan indeks unik, hanya untuk penelusuran ke database sumber.
--
-- Cara pakai (phpMyAdmin > tab SQL, atau terminal mysql):
--   00_basdat_sumber (database sumber dummy) -> 01_ddl -> 03_etl -> 04_query_analisis
-- Script ini aman dijalankan ulang (tabel dihapus lalu dibuat lagi).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS basdat_dw
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE basdat_dw;

SET FOREIGN_KEY_CHECKS = 0;
-- tabel versi revisi 2 dan semua versi sebelumnya ikut dihapus bila masih ada
DROP TABLE IF EXISTS
  fact_peminjaman, fact_ulasan, fact_keterlambatan,
  dim_buku_pinjam, dim_waktu_pengajuan, dim_peminjam_pinjam, dim_status_pinjam,
  dim_buku_ulasan, dim_waktu_ulasan, dim_pengulas, dim_rating,
  dim_kategori_buku, dim_waktu_jatuh_tempo, dim_peminjam_kembali, dim_status_kembali,
  dim_buku, dim_waktu, dim_peminjam,
  fact_pengembalian_denda, fact_aktivitas_peminjam, bridge_buku_penulis, dim_penulis,
  dim_kategori, dim_admin, dim_status_peminjaman, dim_status_pelunasan, dim_bulan;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DIMENSI BERSAMA : dim_waktu
-- Dipakai ketiga fakta. Hierarki: tanggal -> bulan -> kuartal -> tahun.
-- key = YYYYMMDD; key 0 = tanggal tidak diketahui.
-- =====================================================================
CREATE TABLE dim_waktu (
  waktu_key  INT         NOT NULL,
  tanggal    DATE        NULL,
  nama_hari  VARCHAR(16) NOT NULL,
  bulan      TINYINT     NOT NULL,
  nama_bulan VARCHAR(16) NOT NULL,
  kuartal    CHAR(2)     NOT NULL,
  tahun      SMALLINT    NOT NULL,
  PRIMARY KEY (waktu_key)
) ENGINE=InnoDB;

-- =====================================================================
-- DW 1 : ANALISIS PEMINJAMAN BUKU
-- Pertanyaan : buku/kategori mana yang paling sering dipinjam, dan oleh kelompok mana?
-- Grain      : 1 baris = 1 pengajuan peminjaman, semua status.
-- =====================================================================

-- Buku yang dipinjam. Kategori menjadi atribut (hierarki judul -> kategori).
CREATE TABLE dim_buku_pinjam (
  buku_pinjam_key INT          NOT NULL AUTO_INCREMENT,
  book_id         INT          NOT NULL,          -- kunci asli (books.book_id)
  judul           VARCHAR(200) NOT NULL,
  nama_kategori   VARCHAR(100) NOT NULL,
  status_aktif    VARCHAR(12)  NOT NULL,          -- 'Aktif' / 'Nonaktif'
  PRIMARY KEY (buku_pinjam_key),
  UNIQUE KEY uk_buku_pinjam_asli (book_id)
) ENGINE=InnoDB;


-- Siswa yang mengajukan. Peminjam hanya siswa (tenaga pendidik tidak dianalisis).
-- Hierarki: tingkat -> kelas -> siswa. tingkat = Kelas 10 / Kelas 11 / Kelas 12
-- (Lainnya bila kelas tidak dikenali). Email, password, telepon tidak dibawa.
CREATE TABLE dim_peminjam_pinjam (
  peminjam_pinjam_key INT          NOT NULL AUTO_INCREMENT,
  user_id             INT          NOT NULL,      -- kunci asli (users.user_id)
  nis                 VARCHAR(20)  NOT NULL,      -- dari users.nim_nip
  nama                VARCHAR(100) NOT NULL,
  kelas               VARCHAR(20)  NOT NULL,      -- rombel, mis. X-1, XI IPA 2
  tingkat             VARCHAR(20)  NOT NULL,      -- Kelas 10 / 11 / 12
  PRIMARY KEY (peminjam_pinjam_key),
  UNIQUE KEY uk_peminjam_pinjam_asli (user_id)
) ENGINE=InnoDB;

-- Status pengajuan (dimensi khas DW 1).
CREATE TABLE dim_status_pinjam (
  status_pinjam_key TINYINT     NOT NULL,
  nama_status       VARCHAR(20) NOT NULL,         -- Menunggu, Dikonfirmasi, Dipinjam, Dikembalikan, Gagal
  kelompok_status   VARCHAR(20) NOT NULL,         -- Dalam Proses / Berhasil / Gagal
  PRIMARY KEY (status_pinjam_key)
) ENGINE=InnoDB;

CREATE TABLE fact_peminjaman (
  peminjaman_key      INT      NOT NULL AUTO_INCREMENT,
  loan_id             INT      NOT NULL,          -- nomor transaksi asli, untuk penelusuran
  buku_pinjam_key     INT      NOT NULL,
  waktu_pengajuan_key INT      NOT NULL,
  peminjam_pinjam_key INT      NOT NULL,
  status_pinjam_key   TINYINT  NOT NULL,
  jumlah_pengajuan    TINYINT  NOT NULL DEFAULT 1,
  jumlah_berhasil     TINYINT  NOT NULL DEFAULT 0, -- status Dipinjam / Dikembalikan
  jumlah_gagal        TINYINT  NOT NULL DEFAULT 0, -- status Gagal
  PRIMARY KEY (peminjaman_key),
  UNIQUE KEY uk_fp_loan (loan_id),
  CONSTRAINT fk_fp_buku     FOREIGN KEY (buku_pinjam_key)     REFERENCES dim_buku_pinjam (buku_pinjam_key),
  CONSTRAINT fk_fp_waktu    FOREIGN KEY (waktu_pengajuan_key) REFERENCES dim_waktu (waktu_key),
  CONSTRAINT fk_fp_peminjam FOREIGN KEY (peminjam_pinjam_key) REFERENCES dim_peminjam_pinjam (peminjam_pinjam_key),
  CONSTRAINT fk_fp_status   FOREIGN KEY (status_pinjam_key)   REFERENCES dim_status_pinjam (status_pinjam_key)
) ENGINE=InnoDB;

-- =====================================================================
-- DW 2 : ANALISIS KEPUASAN PEMBACA
-- Pertanyaan : seberapa puas pembaca terhadap buku, dan bagaimana penilaian tiap kelompok?
-- Grain      : 1 baris = 1 ulasan yang memiliki rating 1-5.
-- =====================================================================

-- Buku yang diulas. Penulis dan penerbit ikut dibawa karena kepuasan dinilai per judul.
CREATE TABLE dim_buku_ulasan (
  buku_ulasan_key INT          NOT NULL AUTO_INCREMENT,
  book_id         INT          NOT NULL,          -- kunci asli (books.book_id)
  judul           VARCHAR(200) NOT NULL,
  penulis         VARCHAR(255) NOT NULL,          -- nama penulis digabung, "Tidak diketahui" bila kosong
  penerbit        VARCHAR(100) NULL,
  tahun_terbit    SMALLINT     NULL,
  nama_kategori   VARCHAR(100) NOT NULL,
  PRIMARY KEY (buku_ulasan_key),
  UNIQUE KEY uk_buku_ulasan_asli (book_id)
) ENGINE=InnoDB;


-- Siswa yang memberi ulasan. Hierarki: tingkat -> kelas -> siswa.
CREATE TABLE dim_pengulas (
  pengulas_key      INT          NOT NULL AUTO_INCREMENT,
  user_id           INT          NOT NULL,        -- kunci asli (users.user_id)
  nama              VARCHAR(100) NOT NULL,
  kelas             VARCHAR(20)  NOT NULL,
  tingkat           VARCHAR(20)  NOT NULL,
  PRIMARY KEY (pengulas_key),
  UNIQUE KEY uk_pengulas_asli (user_id)
) ENGINE=InnoDB;

-- Skala rating (dimensi khas DW 2). rating_key = nilai rating 1-5.
CREATE TABLE dim_rating (
  rating_key      TINYINT     NOT NULL,
  nilai           TINYINT     NOT NULL,
  label_rating    VARCHAR(20) NOT NULL,           -- Sangat Kecewa .. Sangat Puas
  kelompok_rating VARCHAR(10) NOT NULL,           -- 'Tinggi' (>= 3) / 'Rendah' (<= 2)
  PRIMARY KEY (rating_key)
) ENGINE=InnoDB;

CREATE TABLE fact_ulasan (
  ulasan_key         INT      NOT NULL AUTO_INCREMENT,
  review_id          INT      NOT NULL,           -- nomor ulasan asli, untuk penelusuran
  buku_ulasan_key    INT      NOT NULL,
  waktu_ulasan_key   INT      NOT NULL,
  pengulas_key       INT      NOT NULL,
  rating_key         TINYINT  NOT NULL,
  rating             TINYINT  NOT NULL,           -- 1 sampai 5 (ukuran; dijumlah untuk rata-rata)
  jumlah_ulasan      TINYINT  NOT NULL DEFAULT 1,
  flag_rating_tinggi TINYINT  NOT NULL,           -- 1 bila rating 3 sampai 5
  flag_rating_rendah TINYINT  NOT NULL,           -- 1 bila rating 1 atau 2
  PRIMARY KEY (ulasan_key),
  UNIQUE KEY uk_fu_review (review_id),
  CONSTRAINT fk_fu_buku     FOREIGN KEY (buku_ulasan_key)  REFERENCES dim_buku_ulasan (buku_ulasan_key),
  CONSTRAINT fk_fu_waktu    FOREIGN KEY (waktu_ulasan_key) REFERENCES dim_waktu (waktu_key),
  CONSTRAINT fk_fu_pengulas FOREIGN KEY (pengulas_key)     REFERENCES dim_pengulas (pengulas_key),
  CONSTRAINT fk_fu_rating   FOREIGN KEY (rating_key)       REFERENCES dim_rating (rating_key)
) ENGINE=InnoDB;

-- =====================================================================
-- DW 3 : ANALISIS KETERLAMBATAN PENGEMBALIAN
-- Pertanyaan : bulan apa keterlambatan paling banyak, dan kategori/kelompok mana penyebabnya?
-- Grain      : 1 baris = 1 kewajiban pengembalian yang sudah jatuh tempo (Dikembalikan,
--              atau Dipinjam yang sudah lewat jatuh tempo). Baris tepat waktu ikut disimpan.
-- =====================================================================

-- Kategori buku (DW 3 dianalisis per kategori, bukan per judul).
CREATE TABLE dim_kategori_buku (
  kategori_key  INT          NOT NULL AUTO_INCREMENT,
  category_id   INT          NULL,                -- kunci asli (categories.category_id); NULL = Tanpa Kategori
  nama_kategori VARCHAR(100) NOT NULL,
  PRIMARY KEY (kategori_key),
  UNIQUE KEY uk_kategori_asli (category_id)
) ENGINE=InnoDB;


-- Siswa yang wajib mengembalikan. Hierarki: tingkat -> kelas -> siswa.
CREATE TABLE dim_peminjam_kembali (
  peminjam_kembali_key INT          NOT NULL AUTO_INCREMENT,
  user_id              INT          NOT NULL,     -- kunci asli (users.user_id)
  nis                  VARCHAR(20)  NOT NULL,
  nama                 VARCHAR(100) NOT NULL,
  kelas                VARCHAR(20)  NOT NULL,
  tingkat              VARCHAR(20)  NOT NULL,
  PRIMARY KEY (peminjam_kembali_key),
  UNIQUE KEY uk_peminjam_kembali_asli (user_id)
) ENGINE=InnoDB;

-- Status pengembalian dan denda (dimensi khas DW 3).
CREATE TABLE dim_status_kembali (
  status_kembali_key  TINYINT     NOT NULL,
  status_pengembalian VARCHAR(15) NOT NULL,       -- Tepat Waktu / Terlambat
  posisi_buku         VARCHAR(15) NOT NULL,       -- Sudah Kembali / Belum Kembali
  status_denda        VARCHAR(20) NOT NULL,       -- Tidak Ada Denda / Lunas / Belum Lunas
  PRIMARY KEY (status_kembali_key)
) ENGINE=InnoDB;

CREATE TABLE fact_keterlambatan (
  keterlambatan_key     INT      NOT NULL AUTO_INCREMENT,
  loan_id               INT      NOT NULL,        -- nomor transaksi asli, untuk penelusuran
  waktu_jatuh_tempo_key INT      NOT NULL,
  kategori_key          INT      NOT NULL,
  peminjam_kembali_key  INT      NOT NULL,
  status_kembali_key    TINYINT  NOT NULL,
  flag_terlambat        TINYINT  NOT NULL,
  flag_tepat_waktu      TINYINT  NOT NULL,
  hari_terlambat        SMALLINT NOT NULL DEFAULT 0,
  nominal_denda         INT      NOT NULL DEFAULT 0, -- hari_terlambat x tarif per hari
  PRIMARY KEY (keterlambatan_key),
  UNIQUE KEY uk_fk_loan (loan_id),
  CONSTRAINT fk_fk_waktu    FOREIGN KEY (waktu_jatuh_tempo_key) REFERENCES dim_waktu (waktu_key),
  CONSTRAINT fk_fk_kategori FOREIGN KEY (kategori_key)          REFERENCES dim_kategori_buku (kategori_key),
  CONSTRAINT fk_fk_peminjam FOREIGN KEY (peminjam_kembali_key)  REFERENCES dim_peminjam_kembali (peminjam_kembali_key),
  CONSTRAINT fk_fk_status   FOREIGN KEY (status_kembali_key)    REFERENCES dim_status_kembali (status_kembali_key)
) ENGINE=InnoDB;
