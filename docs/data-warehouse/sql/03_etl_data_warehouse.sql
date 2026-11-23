-- =====================================================================
-- SMART READING ROOM - DATA WAREHOUSE (Kelompok G)   [REVISI 2]
-- File 3 dari 4 : ETL (Extract - Transform - Load) dari basdat_sumber -> basdat_dw
--
-- Strategi : FULL REFRESH. Semua tabel DW dikosongkan lalu diisi ulang dari
--            database sumber, jadi aman dijalankan berulang kali.
-- Sumber   : basdat_sumber (database sumber dummy dari 02_basdat_sumber.sql, D3).
--            Untuk memakai database aplikasi asli, ganti semua kata
--            "basdat_sumber" menjadi "basdat_github" (Find & Replace).
-- Dimensi  : SCD Tipe 1 (data lama ditimpa, riwayat perubahan tidak disimpan).
--            dim_waktu dipakai bersama ketiga fakta; dimensi lain milik tiap DW.
-- Denda    : hari terlambat x tarif per hari (aturan aplikasi, Loan.php). Nominal
--            denda tidak tersimpan di database sumber, jadi dihitung di sini.
-- =====================================================================

USE basdat_dw;

-- Parameter ETL ---------------------------------------------------------
SET @tarif_denda := 1000;        -- sama dengan LIBRARY_FINE_PER_DAY di .env / config/app.php
SET @tgl_etl     := DATE '2026-11-22';  -- tanggal acuan (TGL_ETL) untuk peminjaman yang belum kembali.
                                       -- Tetap agar hasil sama di semua laptop; data dummy berakhir 21 Nov 2026.
                                       -- Untuk data aplikasi yang terus berjalan, ganti dengan CURDATE().
SET SESSION cte_max_recursion_depth = 5000;

-- Kosongkan seluruh tabel DW (fakta dulu, lalu dimensi) -------------------
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE fact_peminjaman;
TRUNCATE TABLE fact_ulasan;
TRUNCATE TABLE fact_keterlambatan;
TRUNCATE TABLE dim_waktu;
TRUNCATE TABLE dim_buku_pinjam;
TRUNCATE TABLE dim_peminjam_pinjam;  TRUNCATE TABLE dim_status_pinjam;
TRUNCATE TABLE dim_buku_ulasan;
TRUNCATE TABLE dim_pengulas;         TRUNCATE TABLE dim_rating;
TRUNCATE TABLE dim_kategori_buku;
TRUNCATE TABLE dim_peminjam_kembali; TRUNCATE TABLE dim_status_kembali;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- A. TRANSFORMASI BERSAMA (tabel sementara, bukan bagian DW)
-- Aturan kalender dan kelompok peminjam ditulis sekali di sini lalu dimuat
-- ke dimensi masing-masing DW, sehingga hasilnya konsisten walau tabelnya terpisah.
-- =====================================================================

-- A1. Kalender 1 Jan 2025 - 31 Des 2028
DROP TEMPORARY TABLE IF EXISTS tmp_kalender;
CREATE TEMPORARY TABLE tmp_kalender AS
WITH RECURSIVE d AS (
  SELECT DATE '2025-01-01' AS tgl
  UNION ALL
  SELECT tgl + INTERVAL 1 DAY FROM d WHERE tgl < DATE '2028-12-31'
)
SELECT CAST(DATE_FORMAT(tgl, '%Y%m%d') AS UNSIGNED) AS waktu_key,
       tgl AS tanggal,
       ELT(WEEKDAY(tgl) + 1, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') AS nama_hari,
       MONTH(tgl) AS bulan,
       ELT(MONTH(tgl), 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
           'Agustus', 'September', 'Oktober', 'November', 'Desember') AS nama_bulan,
       CONCAT('Q', QUARTER(tgl)) AS kuartal,
       YEAR(tgl) AS tahun
FROM d;

-- Dimensi bersama dim_waktu (dipakai ketiga fakta) ------------------------
INSERT INTO dim_waktu (waktu_key, tanggal, nama_hari, bulan, nama_bulan, kuartal, tahun)
SELECT waktu_key, tanggal, nama_hari, bulan, nama_bulan, kuartal, tahun FROM tmp_kalender;
INSERT INTO dim_waktu VALUES (0, NULL, 'Tidak diketahui', 0, 'Tidak diketahui', '-', 0);  -- tanggal kosong

-- A2. Peminjam = SISWA saja (role Peminjam, borrower_type Siswa atau kosong).
--     Tenaga pendidik tidak dimuat, sehingga transaksinya juga tidak masuk fakta
--     (fakta memakai INNER JOIN ke dimensi peminjam).
--     tingkat dari kolom kelas: "XII IPA 2", "XII-1", "12 IPS" -> Kelas 12;
--     "XI-2" -> Kelas 11; "X-1" -> Kelas 10. Pola XII dicek lebih dulu.
DROP TEMPORARY TABLE IF EXISTS tmp_peminjam;
CREATE TEMPORARY TABLE tmp_peminjam AS
SELECT u.user_id, u.nim_nip AS nis, u.name AS nama,
       COALESCE(NULLIF(TRIM(u.class), ''), 'Tidak diketahui') AS kelas,
       CASE
         WHEN UPPER(TRIM(u.class)) REGEXP '^(XII|12)([^0-9A-Z]|$)' THEN 'Kelas 12'
         WHEN UPPER(TRIM(u.class)) REGEXP '^(XI|11)([^0-9A-Z]|$)'  THEN 'Kelas 11'
         WHEN UPPER(TRIM(u.class)) REGEXP '^(X|10)([^0-9A-Z]|$)'   THEN 'Kelas 10'
         ELSE 'Lainnya'
       END AS tingkat
FROM basdat_sumber.users u
WHERE u.role = 'Peminjam'
  AND COALESCE(u.borrower_type, 'Siswa') = 'Siswa';

-- =====================================================================
-- B. DW 1 : ANALISIS PEMINJAMAN BUKU
-- =====================================================================
INSERT INTO dim_buku_pinjam (book_id, judul, nama_kategori, status_aktif)
SELECT b.book_id, b.title, COALESCE(c.category_name, 'Tanpa Kategori'),
       IF(b.is_active = 1, 'Aktif', 'Nonaktif')
FROM basdat_sumber.books b
LEFT JOIN basdat_sumber.categories c ON c.category_id = b.category_id;


INSERT INTO dim_peminjam_pinjam (user_id, nis, nama, kelas, tingkat)
SELECT user_id, nis, nama, kelas, tingkat FROM tmp_peminjam;

-- nilai tetap dari ENUM loans.status
INSERT INTO dim_status_pinjam (status_pinjam_key, nama_status, kelompok_status) VALUES
  (1, 'Menunggu',     'Dalam Proses'),
  (2, 'Dikonfirmasi', 'Dalam Proses'),
  (3, 'Dipinjam',     'Berhasil'),
  (4, 'Dikembalikan', 'Berhasil'),
  (5, 'Gagal',        'Gagal');

INSERT INTO fact_peminjaman
  (loan_id, buku_pinjam_key, waktu_pengajuan_key, peminjam_pinjam_key, status_pinjam_key,
   jumlah_pengajuan, jumlah_berhasil, jumlah_gagal)
SELECT l.loan_id,
       db.buku_pinjam_key,
       CAST(DATE_FORMAT(l.request_date, '%Y%m%d') AS UNSIGNED),
       dp.peminjam_pinjam_key,
       ds.status_pinjam_key,
       1,
       l.status IN ('Dipinjam', 'Dikembalikan'),
       l.status = 'Gagal'
FROM basdat_sumber.loans l
JOIN dim_buku_pinjam     db ON db.book_id = l.book_id
JOIN dim_peminjam_pinjam dp ON dp.user_id = l.user_id
JOIN dim_status_pinjam   ds ON ds.nama_status = l.status COLLATE utf8mb4_general_ci;

-- =====================================================================
-- C. DW 2 : ANALISIS KEPUASAN PEMBACA
-- =====================================================================
INSERT INTO dim_buku_ulasan (book_id, judul, penulis, penerbit, tahun_terbit, nama_kategori)
SELECT b.book_id, b.title,
       COALESCE(LEFT(GROUP_CONCAT(a.author_name ORDER BY ba.urutan SEPARATOR ', '), 255), 'Tidak diketahui'),
       b.publisher, b.publication_year,
       COALESCE(c.category_name, 'Tanpa Kategori')
FROM basdat_sumber.books b
LEFT JOIN basdat_sumber.categories   c  ON c.category_id = b.category_id
LEFT JOIN basdat_sumber.book_authors ba ON ba.book_id = b.book_id
LEFT JOIN basdat_sumber.authors      a  ON a.author_id = ba.author_id
GROUP BY b.book_id, b.title, b.publisher, b.publication_year, c.category_name;


INSERT INTO dim_pengulas (user_id, nama, kelas, tingkat)
SELECT user_id, nama, kelas, tingkat FROM tmp_peminjam;

INSERT INTO dim_rating (rating_key, nilai, label_rating, kelompok_rating) VALUES
  (1, 1, 'Sangat Kecewa', 'Rendah'),
  (2, 2, 'Kecewa',        'Rendah'),
  (3, 3, 'Cukup',         'Tinggi'),
  (4, 4, 'Puas',          'Tinggi'),
  (5, 5, 'Sangat Puas',   'Tinggi');

INSERT INTO fact_ulasan
  (review_id, buku_ulasan_key, waktu_ulasan_key, pengulas_key, rating_key,
   rating, jumlah_ulasan, flag_rating_tinggi, flag_rating_rendah)
SELECT r.review_id,
       db.buku_ulasan_key,
       COALESCE(CAST(DATE_FORMAT(r.created_at, '%Y%m%d') AS UNSIGNED), 0),
       dp.pengulas_key,
       r.rating,
       r.rating,
       1,
       r.rating >= 3,
       r.rating <= 2
FROM basdat_sumber.reviews r
JOIN dim_buku_ulasan db ON db.book_id = r.book_id
JOIN dim_pengulas    dp ON dp.user_id = r.user_id
WHERE r.rating BETWEEN 1 AND 5;   -- ulasan tanpa rating / rating tidak valid dibuang

-- =====================================================================
-- D. DW 3 : ANALISIS KETERLAMBATAN PENGEMBALIAN
-- =====================================================================
INSERT INTO dim_kategori_buku (category_id, nama_kategori)
SELECT category_id, category_name FROM basdat_sumber.categories;
INSERT INTO dim_kategori_buku (category_id, nama_kategori) VALUES (NULL, 'Tanpa Kategori');


INSERT INTO dim_peminjam_kembali (user_id, nis, nama, kelas, tingkat)
SELECT user_id, nis, nama, kelas, tingkat FROM tmp_peminjam;

INSERT INTO dim_status_kembali (status_kembali_key, status_pengembalian, posisi_buku, status_denda) VALUES
  (1, 'Tepat Waktu', 'Sudah Kembali', 'Tidak Ada Denda'),
  (2, 'Terlambat',   'Sudah Kembali', 'Lunas'),
  (3, 'Terlambat',   'Sudah Kembali', 'Belum Lunas'),
  (4, 'Terlambat',   'Belum Kembali', 'Belum Lunas'),
  (5, 'Terlambat',   'Belum Kembali', 'Lunas');

INSERT INTO fact_keterlambatan
  (loan_id, waktu_jatuh_tempo_key, kategori_key, peminjam_kembali_key, status_kembali_key,
   flag_terlambat, flag_tepat_waktu, hari_terlambat, nominal_denda)
SELECT x.loan_id,
       CAST(DATE_FORMAT(x.due_date, '%Y%m%d') AS UNSIGNED),
       dk.kategori_key,
       dp.peminjam_kembali_key,
       CASE
         WHEN x.hari = 0                                                  THEN 1
         WHEN x.return_date IS NOT NULL AND x.fine_paid_at IS NOT NULL    THEN 2
         WHEN x.return_date IS NOT NULL                                   THEN 3
         WHEN x.fine_paid_at IS NULL                                      THEN 4
         ELSE 5
       END,
       x.hari > 0,
       x.hari = 0,
       x.hari,
       x.hari * @tarif_denda
FROM (
  SELECT l.*, b.category_id,
         IF(l.return_date IS NOT NULL,
            GREATEST(DATEDIFF(l.return_date, l.due_date), 0),
            GREATEST(DATEDIFF(@tgl_etl, l.due_date), 0)) AS hari
  FROM basdat_sumber.loans l
  JOIN basdat_sumber.books b ON b.book_id = l.book_id
  WHERE l.due_date IS NOT NULL
    AND ( (l.status = 'Dikembalikan' AND l.return_date IS NOT NULL)
       OR (l.status = 'Dipinjam' AND l.due_date < @tgl_etl) )
) x
JOIN dim_kategori_buku    dk ON dk.category_id <=> x.category_id
JOIN dim_peminjam_kembali dp ON dp.user_id = x.user_id;

DROP TEMPORARY TABLE IF EXISTS tmp_kalender, tmp_peminjam;

-- =====================================================================
-- E. REKONSILIASI: bandingkan jumlah baris sumber (khusus siswa) dan DW
-- =====================================================================
-- siswa = users dengan role Peminjam dan borrower_type Siswa (atau kosong)
SELECT 'fact_peminjaman (DW 1)' AS tabel,
       (SELECT COUNT(*) FROM basdat_sumber.loans WHERE user_id IN (SELECT user_id FROM basdat_sumber.users WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa')) AS sumber,
       COUNT(*) AS dw FROM fact_peminjaman
UNION ALL SELECT 'fact_ulasan (DW 2)',
       (SELECT COUNT(*) FROM basdat_sumber.reviews
         WHERE rating BETWEEN 1 AND 5 AND user_id IN (SELECT user_id FROM basdat_sumber.users WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa')),
       COUNT(*) FROM fact_ulasan
UNION ALL SELECT 'fact_keterlambatan (DW 3)',
       (SELECT COUNT(*) FROM basdat_sumber.loans
         WHERE user_id IN (SELECT user_id FROM basdat_sumber.users WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa')
           AND due_date IS NOT NULL
           AND ((status = 'Dikembalikan' AND return_date IS NOT NULL)
             OR (status = 'Dipinjam' AND due_date < @tgl_etl))),
       COUNT(*) FROM fact_keterlambatan
UNION ALL SELECT 'dim_buku_pinjam / dim_buku_ulasan', (SELECT COUNT(*) FROM basdat_sumber.books), COUNT(*) FROM dim_buku_pinjam
UNION ALL SELECT 'dim_peminjam_pinjam / dim_pengulas / dim_peminjam_kembali (siswa)',
       (SELECT COUNT(*) FROM (SELECT user_id FROM basdat_sumber.users WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa') s), COUNT(*) FROM dim_peminjam_pinjam;

-- Transaksi yang sengaja tidak dimuat karena milik tenaga pendidik
SELECT 'peminjaman tenaga pendidik' AS dibuang, COUNT(*) AS jumlah
FROM basdat_sumber.loans l JOIN basdat_sumber.users u ON u.user_id = l.user_id
WHERE u.borrower_type = 'Tenaga Pendidik'
UNION ALL
SELECT 'ulasan tenaga pendidik', COUNT(*)
FROM basdat_sumber.reviews r JOIN basdat_sumber.users u ON u.user_id = r.user_id
WHERE u.borrower_type = 'Tenaga Pendidik';

-- Siswa yang kelasnya tidak dikenali (perlu dirapikan di data sumber)
SELECT nis, nama, kelas FROM dim_peminjam_pinjam WHERE tingkat = 'Lainnya';

