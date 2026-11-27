-- =====================================================================
-- SMART READING ROOM - DATA WAREHOUSE (Kelompok G)
-- File 5 : VIEW DASHBOARD (lapisan data untuk halaman admin /admin/dw)
--
-- Aturan view ini:
--  * Hanya membaca basdat_dw (tabel fakta + dimensi). Tidak menyentuh basdat_v2.
--  * Setiap view berupa tabel datar yang bisa difilter: tahun, kuartal, kategori,
--    tingkat, kelas. Filter global cukup berupa WHERE di Laravel.
--  * Isi view hanya ukuran yang bisa dijumlah. Rata-rata dan persen dihitung di
--    Laravel sebagai SUM / SUM (jangan merata-ratakan rata-rata):
--      rata-rata rating   = SUM(total_rating) / SUM(jumlah_ulasan)
--      persen berhasil    = SUM(berhasil) / SUM(pengajuan)
--      persen terlambat   = SUM(terlambat) / SUM(kewajiban)
--      rata hari terlambat= SUM(total_hari_terlambat) / SUM(terlambat)
--  * Tanpa ROLLUP; subtotal dihitung di Laravel.
--  * Ukuran di-CAST ke SIGNED supaya terbaca sebagai angka (bukan string) di PHP/JSON.
--
-- Jalankan setelah basdat_dw terisi (basdat_dw_acuan.sql atau hasil Pentaho).
-- Aman dijalankan ulang (CREATE OR REPLACE VIEW).
-- =====================================================================

USE basdat_dw;

-- =====================================================================
-- DW 1 : PEMINJAMAN BUKU
-- =====================================================================

-- 1. Peminjaman per bulan x kategori x tingkat x kelas.
--    Melayani: tren bulanan, kategori x tingkat (rinci per kelas), kategori favorit.
--    Acuan: query 1.1, 1.3, 1.4.
CREATE OR REPLACE VIEW v_peminjaman_bulanan AS
SELECT w.tahun,
       w.kuartal,
       w.bulan,
       w.nama_bulan,
       b.nama_kategori AS kategori,
       p.tingkat,
       p.kelas,
       CAST(SUM(f.jumlah_pengajuan) AS SIGNED) AS pengajuan,
       CAST(SUM(f.jumlah_berhasil)  AS SIGNED) AS berhasil,
       CAST(SUM(f.jumlah_gagal)     AS SIGNED) AS gagal
FROM fact_peminjaman f
JOIN dim_waktu           w ON w.waktu_key           = f.waktu_pengajuan_key
JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, b.nama_kategori, p.tingkat, p.kelas;

-- 2. Buku populer per kuartal x tingkat x kelas.
--    Melayani: tabel 10 buku terpopuler yang bisa difilter. Acuan: query 1.2.
CREATE OR REPLACE VIEW v_buku_populer AS
SELECT b.book_id,
       b.judul,
       b.nama_kategori AS kategori,
       w.tahun,
       w.kuartal,
       p.tingkat,
       p.kelas,
       CAST(SUM(f.jumlah_berhasil) AS SIGNED) AS berhasil
FROM fact_peminjaman f
JOIN dim_waktu           w ON w.waktu_key           = f.waktu_pengajuan_key
JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
GROUP BY b.book_id, b.judul, b.nama_kategori, w.tahun, w.kuartal, p.tingkat, p.kelas;

-- =====================================================================
-- DW 2 : KEPUASAN PEMBACA
-- =====================================================================

-- 4. Ulasan per bulan x kategori x tingkat x kelas.
--    Melayani: rating per kategori, proporsi rating tinggi/rendah per tingkat dan kelas.
--    Acuan: query 2.1, 2.4.
CREATE OR REPLACE VIEW v_ulasan_bulanan AS
SELECT w.tahun,
       w.kuartal,
       w.bulan,
       w.nama_bulan,
       b.nama_kategori AS kategori,
       p.tingkat,
       p.kelas,
       CAST(SUM(f.jumlah_ulasan)       AS SIGNED) AS jumlah_ulasan,
       CAST(SUM(f.rating)              AS SIGNED) AS total_rating,
       CAST(SUM(f.flag_rating_tinggi)  AS SIGNED) AS rating_tinggi,
       CAST(SUM(f.flag_rating_rendah)  AS SIGNED) AS rating_rendah
FROM fact_ulasan f
JOIN dim_waktu       w ON w.waktu_key       = f.waktu_ulasan_key
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
JOIN dim_pengulas    p ON p.pengulas_key    = f.pengulas_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, b.nama_kategori, p.tingkat, p.kelas;

-- 5. Ulasan per buku x kuartal x tingkat x kelas.
--    Melayani: buku rating tertinggi (minimal 3 ulasan, dihitung di Laravel),
--    rating rendah terbanyak, dan "sering dipinjam vs rating" (cocokkan lewat book_id
--    dengan v_buku_populer). Acuan: query 2.2, 2.3, perbandingan antar-DW.
CREATE OR REPLACE VIEW v_ulasan_buku AS
SELECT b.book_id,
       b.judul,
       b.nama_kategori AS kategori,
       w.tahun,
       w.kuartal,
       p.tingkat,
       p.kelas,
       CAST(SUM(f.jumlah_ulasan)       AS SIGNED) AS jumlah_ulasan,
       CAST(SUM(f.rating)              AS SIGNED) AS total_rating,
       CAST(SUM(f.flag_rating_tinggi)  AS SIGNED) AS rating_tinggi,
       CAST(SUM(f.flag_rating_rendah)  AS SIGNED) AS rating_rendah
FROM fact_ulasan f
JOIN dim_waktu       w ON w.waktu_key       = f.waktu_ulasan_key
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
JOIN dim_pengulas    p ON p.pengulas_key    = f.pengulas_key
GROUP BY b.book_id, b.judul, b.nama_kategori, w.tahun, w.kuartal, p.tingkat, p.kelas;

-- 6. Sebaran ulasan menurut skala rating (dimensi khas dim_rating).
--    Acuan: query 2.5.
CREATE OR REPLACE VIEW v_rating_sebaran AS
SELECT r.nilai,
       r.label_rating,
       r.kelompok_rating,
       w.tahun,
       w.kuartal,
       b.nama_kategori AS kategori,
       p.tingkat,
       p.kelas,
       CAST(SUM(f.jumlah_ulasan) AS SIGNED) AS jumlah_ulasan
FROM fact_ulasan f
JOIN dim_rating      r ON r.rating_key      = f.rating_key
JOIN dim_waktu       w ON w.waktu_key       = f.waktu_ulasan_key
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
JOIN dim_pengulas    p ON p.pengulas_key    = f.pengulas_key
GROUP BY r.nilai, r.label_rating, r.kelompok_rating, w.tahun, w.kuartal,
         b.nama_kategori, p.tingkat, p.kelas;

-- =====================================================================
-- DW 3 : KETERLAMBATAN PENGEMBALIAN
-- =====================================================================

-- 7. Keterlambatan per bulan jatuh tempo x kategori x tingkat x kelas.
--    Melayani: % terlambat per bulan, per kategori, per kelompok, total denda,
--    rata-rata hari terlambat. Acuan: query 3.1 sampai 3.4.
CREATE OR REPLACE VIEW v_keterlambatan_bulanan AS
SELECT w.tahun,
       w.kuartal,
       w.bulan,
       w.nama_bulan,
       k.nama_kategori AS kategori,
       p.tingkat,
       p.kelas,
       CAST(COUNT(*)                AS SIGNED) AS kewajiban,
       CAST(SUM(f.flag_terlambat)   AS SIGNED) AS terlambat,
       CAST(SUM(f.flag_tepat_waktu) AS SIGNED) AS tepat_waktu,
       CAST(SUM(f.hari_terlambat)   AS SIGNED) AS total_hari_terlambat,
       CAST(SUM(f.nominal_denda)    AS SIGNED) AS total_denda
FROM fact_keterlambatan f
JOIN dim_waktu            w ON w.waktu_key            = f.waktu_jatuh_tempo_key
JOIN dim_kategori_buku    k ON k.kategori_key         = f.kategori_key
JOIN dim_peminjam_kembali p ON p.peminjam_kembali_key = f.peminjam_kembali_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, k.nama_kategori, p.tingkat, p.kelas;

-- 8. Sebaran kewajiban menurut status pengembalian dan denda (dimensi khas).
--    Acuan: query 3.5.
CREATE OR REPLACE VIEW v_keterlambatan_status AS
SELECT s.status_pengembalian,
       s.posisi_buku,
       s.status_denda,
       w.tahun,
       w.kuartal,
       k.nama_kategori AS kategori,
       p.tingkat,
       p.kelas,
       CAST(COUNT(*)              AS SIGNED) AS kewajiban,
       CAST(SUM(f.nominal_denda)  AS SIGNED) AS total_denda,
       CAST(SUM(f.hari_terlambat) AS SIGNED) AS total_hari
FROM fact_keterlambatan f
JOIN dim_status_kembali   s ON s.status_kembali_key   = f.status_kembali_key
JOIN dim_waktu            w ON w.waktu_key            = f.waktu_jatuh_tempo_key
JOIN dim_kategori_buku    k ON k.kategori_key         = f.kategori_key
JOIN dim_peminjam_kembali p ON p.peminjam_kembali_key = f.peminjam_kembali_key
GROUP BY s.status_pengembalian, s.posisi_buku, s.status_denda, w.tahun, w.kuartal,
         k.nama_kategori, p.tingkat, p.kelas;

-- =====================================================================
-- RINGKASAN: gabungan tiga DW per bulan lewat dim_waktu bersama
-- =====================================================================

-- 3. Satu baris per DW per bulan; kolom milik DW lain berisi 0 (UNION ALL, tanpa join
--    antar-fakta sehingga baris tidak menggandakan). Bulan mengikuti peran tiap DW:
--    tanggal pengajuan (DW 1), tanggal ulasan (DW 2), tanggal jatuh tempo (DW 3).
--    Melayani: 5 KPI dan grafik gabungan per bulan. Pakai SUM() di Laravel.
CREATE OR REPLACE VIEW v_ringkasan_bulanan AS
SELECT w.tahun, w.kuartal, w.bulan, w.nama_bulan,
       b.nama_kategori AS kategori, p.tingkat, p.kelas,
       CAST(SUM(f.jumlah_pengajuan) AS SIGNED) AS pengajuan,
       CAST(SUM(f.jumlah_berhasil)  AS SIGNED) AS berhasil,
       CAST(SUM(f.jumlah_gagal)     AS SIGNED) AS gagal,
       0 AS jumlah_ulasan,
       0 AS total_rating,
       0 AS kewajiban,
       0 AS terlambat,
       0 AS total_denda
FROM fact_peminjaman f
JOIN dim_waktu           w ON w.waktu_key           = f.waktu_pengajuan_key
JOIN dim_buku_pinjam     b ON b.buku_pinjam_key     = f.buku_pinjam_key
JOIN dim_peminjam_pinjam p ON p.peminjam_pinjam_key = f.peminjam_pinjam_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, b.nama_kategori, p.tingkat, p.kelas

UNION ALL

SELECT w.tahun, w.kuartal, w.bulan, w.nama_bulan,
       b.nama_kategori, p.tingkat, p.kelas,
       0, 0, 0,
       CAST(SUM(f.jumlah_ulasan) AS SIGNED),
       CAST(SUM(f.rating)        AS SIGNED),
       0, 0, 0
FROM fact_ulasan f
JOIN dim_waktu       w ON w.waktu_key       = f.waktu_ulasan_key
JOIN dim_buku_ulasan b ON b.buku_ulasan_key = f.buku_ulasan_key
JOIN dim_pengulas    p ON p.pengulas_key    = f.pengulas_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, b.nama_kategori, p.tingkat, p.kelas

UNION ALL

SELECT w.tahun, w.kuartal, w.bulan, w.nama_bulan,
       k.nama_kategori, p.tingkat, p.kelas,
       0, 0, 0, 0, 0,
       CAST(COUNT(*)              AS SIGNED),
       CAST(SUM(f.flag_terlambat) AS SIGNED),
       CAST(SUM(f.nominal_denda)  AS SIGNED)
FROM fact_keterlambatan f
JOIN dim_waktu            w ON w.waktu_key            = f.waktu_jatuh_tempo_key
JOIN dim_kategori_buku    k ON k.kategori_key         = f.kategori_key
JOIN dim_peminjam_kembali p ON p.peminjam_kembali_key = f.peminjam_kembali_key
GROUP BY w.tahun, w.kuartal, w.bulan, w.nama_bulan, k.nama_kategori, p.tingkat, p.kelas;

-- =====================================================================
-- CEK LOLOS: bandingkan dengan angka-acuan.md (TGL_ETL 22 November 2026)
-- Semua kolom "cocok" harus bernilai 1.
-- =====================================================================
SELECT 'v_peminjaman_bulanan' AS view_diuji,
       SUM(pengajuan) AS a, SUM(berhasil) AS b, SUM(gagal) AS c,
       (SUM(pengajuan) = 985 AND SUM(berhasil) = 905 AND SUM(gagal) = 74) AS cocok
FROM v_peminjaman_bulanan
UNION ALL
SELECT 'v_buku_populer (berhasil)', SUM(berhasil), NULL, NULL, SUM(berhasil) = 905
FROM v_buku_populer
UNION ALL
SELECT 'v_ulasan_bulanan', SUM(jumlah_ulasan), SUM(total_rating), SUM(rating_rendah),
       (SUM(jumlah_ulasan) = 389 AND SUM(total_rating) = 1439 AND SUM(rating_rendah) = 40)
FROM v_ulasan_bulanan
UNION ALL
SELECT 'v_ulasan_buku', SUM(jumlah_ulasan), SUM(total_rating), SUM(rating_rendah),
       (SUM(jumlah_ulasan) = 389 AND SUM(total_rating) = 1439 AND SUM(rating_rendah) = 40)
FROM v_ulasan_buku
UNION ALL
SELECT 'v_rating_sebaran (jumlah_ulasan)', SUM(jumlah_ulasan), NULL, NULL, SUM(jumlah_ulasan) = 389
FROM v_rating_sebaran
UNION ALL
SELECT 'v_keterlambatan_bulanan', SUM(kewajiban), SUM(terlambat), SUM(total_denda),
       (SUM(kewajiban) = 899 AND SUM(terlambat) = 188 AND SUM(total_denda) = 3836000)
FROM v_keterlambatan_bulanan
UNION ALL
SELECT 'v_keterlambatan_status', SUM(kewajiban), NULL, SUM(total_denda),
       (SUM(kewajiban) = 899 AND SUM(total_denda) = 3836000)
FROM v_keterlambatan_status
UNION ALL
SELECT 'v_ringkasan_bulanan', SUM(pengajuan), SUM(jumlah_ulasan), SUM(kewajiban),
       (SUM(pengajuan) = 985 AND SUM(berhasil) = 905 AND SUM(gagal) = 74
        AND SUM(jumlah_ulasan) = 389 AND SUM(total_rating) = 1439
        AND SUM(kewajiban) = 899 AND SUM(terlambat) = 188 AND SUM(total_denda) = 3836000)
FROM v_ringkasan_bulanan;

-- Cek pola dummy: kategori favorit per tingkat (query 1.3).
-- Harus: Kelas 10 Fiksi & Sastra, Kelas 11 Komputer & Teknologi, Kelas 12 Sains & Matematika.
SELECT tingkat, kategori, dipinjam
FROM (
  SELECT tingkat, kategori, SUM(berhasil) AS dipinjam,
         RANK() OVER (PARTITION BY tingkat ORDER BY SUM(berhasil) DESC) AS peringkat
  FROM v_peminjaman_bulanan
  GROUP BY tingkat, kategori
) x
WHERE peringkat = 1
ORDER BY tingkat;

-- Cek pola dummy: % terlambat per tingkat. Harus: Kelas 10 15,8 / Kelas 11 19,4 / Kelas 12 26,7.
SELECT tingkat,
       SUM(kewajiban) AS kewajiban,
       SUM(terlambat) AS terlambat,
       ROUND(100 * SUM(terlambat) / SUM(kewajiban), 1) AS persen_terlambat
FROM v_keterlambatan_bulanan
GROUP BY tingkat
ORDER BY tingkat;
