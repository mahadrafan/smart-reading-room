-- =====================================================================
-- SMART READING ROOM - DATA WAREHOUSE (Kelompok G)   [REVISI 2]
-- File 4 dari 4 : QUERY ANALISIS (strategic information)
-- Jalankan setelah 03_etl_data_warehouse.sql. Satu query per pertanyaan bisnis.
-- Setiap DW hanya memakai tabel dimensinya sendiri.
-- Peminjam hanya siswa. Kolom tingkat: Kelas 10, Kelas 11, Kelas 12, dan
-- Lainnya (akun yang isi kelasnya tidak dapat dikenali, agar total tetap cocok).
-- Rincian per rombel memakai kolom kelas (hierarki tingkat -> kelas).
-- =====================================================================

USE basdat_dw;

-- =====================================================================
-- DW 1 : ANALISIS PEMINJAMAN BUKU
-- fact_peminjaman + dim_buku_pinjam, dim_waktu, dim_peminjam_pinjam, dim_status_pinjam
-- =====================================================================

-- 1.1 Information package: peminjaman berhasil per kuartal x kategori,
--     kolom = tingkat siswa, plus subtotal per kuartal (ROLLUP).
SELECT IF(GROUPING(w.tahun), 'TOTAL', w.tahun) AS tahun,
       IF(GROUPING(w.kuartal), 'Semua kuartal', w.kuartal) AS kuartal,
       IF(GROUPING(b.nama_kategori), 'Semua kategori', b.nama_kategori) AS kategori,
       SUM(CASE WHEN p.tingkat = 'Kelas 10'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_10,
       SUM(CASE WHEN p.tingkat = 'Kelas 11'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_11,
       SUM(CASE WHEN p.tingkat = 'Kelas 12'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_12,
       SUM(CASE WHEN p.tingkat = 'Lainnya'         THEN f.jumlah_berhasil ELSE 0 END) AS lainnya,
       SUM(f.jumlah_berhasil) AS total
FROM fact_peminjaman f
JOIN dim_waktu w ON w.waktu_key = f.waktu_pengajuan_key
JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
GROUP BY w.tahun, w.kuartal, b.nama_kategori WITH ROLLUP;

-- 1.2 Sepuluh buku terpopuler beserta tingkat peminjamnya
SELECT b.judul, b.nama_kategori,
       SUM(CASE WHEN p.tingkat = 'Kelas 10'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_10,
       SUM(CASE WHEN p.tingkat = 'Kelas 11'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_11,
       SUM(CASE WHEN p.tingkat = 'Kelas 12'        THEN f.jumlah_berhasil ELSE 0 END) AS kelas_12,
       SUM(CASE WHEN p.tingkat = 'Lainnya'         THEN f.jumlah_berhasil ELSE 0 END) AS lainnya,
       SUM(f.jumlah_berhasil) AS total_dipinjam
FROM fact_peminjaman f
JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
GROUP BY b.buku_pinjam_key, b.judul, b.nama_kategori
ORDER BY total_dipinjam DESC, b.judul
LIMIT 10;

-- 1.3 Kategori favorit tiap tingkat (peringkat 1 per tingkat)
SELECT tingkat, nama_kategori, dipinjam
FROM (
  SELECT p.tingkat, b.nama_kategori, SUM(f.jumlah_berhasil) AS dipinjam,
         RANK() OVER (PARTITION BY p.tingkat ORDER BY SUM(f.jumlah_berhasil) DESC) AS peringkat
  FROM fact_peminjaman f
  JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
  JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
  GROUP BY p.tingkat, b.nama_kategori
) x
WHERE peringkat = 1
ORDER BY tingkat;

-- 1.4 Tren pengajuan per bulan dan tingkat keberhasilannya
SELECT w.tahun, w.bulan, w.nama_bulan,
       SUM(f.jumlah_pengajuan) AS pengajuan,
       SUM(f.jumlah_berhasil)  AS berhasil,
       SUM(f.jumlah_gagal)     AS gagal,
       ROUND(100 * SUM(f.jumlah_berhasil) / SUM(f.jumlah_pengajuan), 1) AS persen_berhasil
FROM fact_peminjaman f
JOIN dim_waktu w ON w.waktu_key = f.waktu_pengajuan_key
GROUP BY w.tahun, w.bulan, w.nama_bulan
ORDER BY w.tahun, w.bulan;

-- =====================================================================
-- DW 2 : ANALISIS KEPUASAN PEMBACA
-- fact_ulasan + dim_buku_ulasan, dim_waktu, dim_pengulas, dim_rating
-- Rata-rata rating dihitung saat query: SUM(rating) / SUM(jumlah_ulasan).
-- =====================================================================

-- 2.1 Information package: rata-rata rating per kuartal x kategori,
--     kolom = tingkat siswa, plus subtotal per kuartal (ROLLUP).
SELECT IF(GROUPING(w.tahun), 'TOTAL', w.tahun) AS tahun,
       IF(GROUPING(w.kuartal), 'Semua kuartal', w.kuartal) AS kuartal,
       IF(GROUPING(b.nama_kategori), 'Semua kategori', b.nama_kategori) AS kategori,
       ROUND(AVG(CASE WHEN p.tingkat = 'Kelas 10'        THEN f.rating END), 2) AS kelas_10,
       ROUND(AVG(CASE WHEN p.tingkat = 'Kelas 11'        THEN f.rating END), 2) AS kelas_11,
       ROUND(AVG(CASE WHEN p.tingkat = 'Kelas 12'        THEN f.rating END), 2) AS kelas_12,
       ROUND(AVG(CASE WHEN p.tingkat = 'Lainnya'         THEN f.rating END), 2) AS lainnya,
       SUM(f.jumlah_ulasan) AS jumlah_ulasan,
       ROUND(SUM(f.rating) / SUM(f.jumlah_ulasan), 2) AS rata_rating
FROM fact_ulasan f
JOIN dim_waktu w ON w.waktu_key = f.waktu_ulasan_key
JOIN dim_buku_ulasan  b ON b.buku_ulasan_key  = f.buku_ulasan_key
JOIN dim_pengulas     p ON p.pengulas_key     = f.pengulas_key
GROUP BY w.tahun, w.kuartal, b.nama_kategori WITH ROLLUP;

-- 2.2 Buku dengan kepuasan tertinggi (minimal 3 ulasan agar adil)
SELECT b.judul, b.nama_kategori,
       SUM(f.jumlah_ulasan) AS jumlah_ulasan,
       ROUND(SUM(f.rating) / SUM(f.jumlah_ulasan), 2) AS rata_rating,
       SUM(f.flag_rating_tinggi) AS rating_tinggi,
       SUM(f.flag_rating_rendah) AS rating_rendah
FROM fact_ulasan f
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
GROUP BY b.buku_ulasan_key, b.judul, b.nama_kategori
HAVING jumlah_ulasan >= 3
ORDER BY rata_rating DESC, jumlah_ulasan DESC
LIMIT 10;

-- 2.3 Buku yang paling banyak mendapat rating rendah (kandidat tidak dibeli lagi)
SELECT b.judul, b.nama_kategori,
       SUM(f.jumlah_ulasan) AS jumlah_ulasan,
       SUM(f.flag_rating_rendah) AS rating_rendah,
       ROUND(100 * SUM(f.flag_rating_rendah) / SUM(f.jumlah_ulasan), 1) AS persen_rating_rendah,
       ROUND(SUM(f.rating) / SUM(f.jumlah_ulasan), 2) AS rata_rating
FROM fact_ulasan f
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
GROUP BY b.buku_ulasan_key, b.judul, b.nama_kategori
HAVING rating_rendah > 0
ORDER BY persen_rating_rendah DESC, rating_rendah DESC;

-- 2.4 Kepuasan per tingkat dan kelas pengulas
SELECT p.tingkat, p.kelas,
       SUM(f.jumlah_ulasan) AS jumlah_ulasan,
       ROUND(SUM(f.rating) / SUM(f.jumlah_ulasan), 2) AS rata_rating,
       ROUND(100 * SUM(f.flag_rating_tinggi) / SUM(f.jumlah_ulasan), 1) AS persen_rating_tinggi,
       ROUND(100 * SUM(f.flag_rating_rendah) / SUM(f.jumlah_ulasan), 1) AS persen_rating_rendah
FROM fact_ulasan f
JOIN dim_pengulas p ON p.pengulas_key = f.pengulas_key
GROUP BY p.tingkat, p.kelas WITH ROLLUP;  -- baris kelas NULL = subtotal tingkat

-- =====================================================================
-- DW 3 : ANALISIS KETERLAMBATAN PENGEMBALIAN
-- fact_keterlambatan + dim_waktu, dim_kategori_buku, dim_peminjam_kembali, dim_status_kembali
-- =====================================================================

-- 3.1 Information package: kasus terlambat per bulan jatuh tempo,
--     kolom = tingkat siswa, plus subtotal per kuartal (ROLLUP).
SELECT IF(GROUPING(w.tahun), 'TOTAL', w.tahun) AS tahun,
       IF(GROUPING(w.kuartal), 'Semua kuartal', w.kuartal) AS kuartal,
       IF(GROUPING(w.bulan), 'Semua bulan', MAX(w.nama_bulan)) AS bulan_jatuh_tempo,
       SUM(CASE WHEN p.tingkat = 'Kelas 10'        THEN f.flag_terlambat ELSE 0 END) AS kelas_10,
       SUM(CASE WHEN p.tingkat = 'Kelas 11'        THEN f.flag_terlambat ELSE 0 END) AS kelas_11,
       SUM(CASE WHEN p.tingkat = 'Kelas 12'        THEN f.flag_terlambat ELSE 0 END) AS kelas_12,
       SUM(CASE WHEN p.tingkat = 'Lainnya'         THEN f.flag_terlambat ELSE 0 END) AS lainnya,
       SUM(f.flag_terlambat) AS total_terlambat,
       COUNT(*) AS kewajiban_kembali,
       ROUND(100 * SUM(f.flag_terlambat) / COUNT(*), 1) AS persen_terlambat,
       SUM(f.nominal_denda) AS total_denda
FROM fact_keterlambatan f
JOIN dim_waktu w ON w.waktu_key = f.waktu_jatuh_tempo_key
JOIN dim_peminjam_kembali  p ON p.peminjam_kembali_key  = f.peminjam_kembali_key
GROUP BY w.tahun, w.kuartal, w.bulan WITH ROLLUP;

-- 3.2 Bulan dengan keterlambatan terbanyak (peringkat)
SELECT w.tahun, w.nama_bulan,
       SUM(f.flag_terlambat) AS terlambat,
       COUNT(*) AS kewajiban_kembali,
       ROUND(100 * SUM(f.flag_terlambat) / COUNT(*), 1) AS persen_terlambat,
       SUM(f.hari_terlambat) AS total_hari_terlambat
FROM fact_keterlambatan f
JOIN dim_waktu w ON w.waktu_key = f.waktu_jatuh_tempo_key
GROUP BY w.tahun, w.bulan, w.nama_bulan
ORDER BY terlambat DESC, persen_terlambat DESC
LIMIT 5;

-- 3.3 Kategori buku yang paling sering terlambat
SELECT k.nama_kategori,
       COUNT(*) AS kewajiban_kembali,
       SUM(f.flag_terlambat) AS terlambat,
       ROUND(100 * SUM(f.flag_terlambat) / COUNT(*), 1) AS persen_terlambat,
       ROUND(AVG(CASE WHEN f.flag_terlambat = 1 THEN f.hari_terlambat END), 1) AS rata_hari_terlambat,
       SUM(f.nominal_denda) AS total_denda
FROM fact_keterlambatan f
JOIN dim_kategori_buku k ON k.kategori_key = f.kategori_key
GROUP BY k.kategori_key, k.nama_kategori
ORDER BY persen_terlambat DESC, terlambat DESC;

-- 3.4 Tingkat dan kelas yang paling sering terlambat
SELECT p.tingkat, p.kelas,
       COUNT(*) AS kewajiban_kembali,
       SUM(f.flag_terlambat) AS terlambat,
       ROUND(100 * SUM(f.flag_terlambat) / COUNT(*), 1) AS persen_terlambat,
       ROUND(AVG(CASE WHEN f.flag_terlambat = 1 THEN f.hari_terlambat END), 1) AS rata_hari_terlambat,
       SUM(f.nominal_denda) AS total_denda
FROM fact_keterlambatan f
JOIN dim_peminjam_kembali p ON p.peminjam_kembali_key = f.peminjam_kembali_key
GROUP BY p.tingkat, p.kelas WITH ROLLUP;  -- baris kelas NULL = subtotal tingkat

-- =====================================================================
-- PERBANDINGAN ANTAR-DW PER BUKU
-- Karena setiap DW punya dimensinya sendiri, hasil tiap DW diringkas dulu per
-- buku lalu dicocokkan lewat kunci asli book_id (bukan lewat tabel dimensi bersama).
-- DW 3 dianalisis per kategori, jadi hanya DW 1 dan DW 2 yang dibandingkan per judul.
-- =====================================================================
SELECT p.judul, p.nama_kategori, p.dipinjam, u.rata_rating, u.jumlah_ulasan
FROM (SELECT b.book_id, b.judul, b.nama_kategori, SUM(f.jumlah_berhasil) AS dipinjam
      FROM fact_peminjaman f JOIN dim_buku_pinjam b ON b.buku_pinjam_key = f.buku_pinjam_key
      GROUP BY b.book_id, b.judul, b.nama_kategori) p
LEFT JOIN (SELECT b.book_id, ROUND(SUM(f.rating) / SUM(f.jumlah_ulasan), 2) AS rata_rating,
                  SUM(f.jumlah_ulasan) AS jumlah_ulasan
           FROM fact_ulasan f JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
           GROUP BY b.book_id) u ON u.book_id = p.book_id
ORDER BY p.dipinjam DESC;

-- =====================================================================
-- ANALISIS DIMENSI KHAS SETIAP DW
-- =====================================================================

-- 1.5 (DW 1) Sebaran pengajuan menurut status peminjaman
SELECT s.nama_status, s.kelompok_status,
       SUM(f.jumlah_pengajuan) AS pengajuan,
       ROUND(100 * SUM(f.jumlah_pengajuan) / (SELECT COUNT(*) FROM fact_peminjaman), 1) AS persen
FROM fact_peminjaman f
JOIN dim_status_pinjam s ON s.status_pinjam_key = f.status_pinjam_key
GROUP BY s.status_pinjam_key, s.nama_status, s.kelompok_status
ORDER BY s.status_pinjam_key;

-- 2.5 (DW 2) Sebaran ulasan menurut skala rating
SELECT r.label_rating, r.nilai, r.kelompok_rating,
       SUM(f.jumlah_ulasan) AS jumlah_ulasan,
       ROUND(100 * SUM(f.jumlah_ulasan) / (SELECT COUNT(*) FROM fact_ulasan), 1) AS persen
FROM fact_ulasan f
JOIN dim_rating r ON r.rating_key = f.rating_key
GROUP BY r.rating_key, r.label_rating, r.nilai, r.kelompok_rating
ORDER BY r.rating_key;

-- 3.5 (DW 3) Sebaran kewajiban menurut status pengembalian dan denda
SELECT s.status_pengembalian, s.posisi_buku, s.status_denda,
       COUNT(*) AS kewajiban_kembali,
       SUM(f.nominal_denda) AS total_denda,
       ROUND(AVG(f.hari_terlambat), 1) AS rata_hari_terlambat
FROM fact_keterlambatan f
JOIN dim_status_kembali s ON s.status_kembali_key = f.status_kembali_key
GROUP BY s.status_kembali_key, s.status_pengembalian, s.posisi_buku, s.status_denda
ORDER BY s.status_kembali_key;
