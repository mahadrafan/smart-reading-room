-- Validasi tr_fact_peminjaman (Isma - B2). Jalankan setelah transformasi selesai.
-- Acuan (angka-acuan.md, TGL_ETL 2026-11-22): 985 baris; SUM 985 / 905 / 74.
USE basdat_dw;

-- 1. Jumlah baris dan total ukuran
SELECT COUNT(*)              AS baris,            -- 985
       SUM(jumlah_pengajuan) AS pengajuan,        -- 985
       SUM(jumlah_berhasil)  AS berhasil,         -- 905
       SUM(jumlah_gagal)     AS gagal             -- 74
FROM fact_peminjaman;

-- 2. Sebaran dimensi khas (harus sama dengan status loans di angka-acuan.md:
--    Menunggu 1, Dikonfirmasi 5, Dipinjam 19, Dikembalikan 886, Gagal 74)
SELECT s.status_pinjam_key, s.nama_status, COUNT(*) AS baris
FROM fact_peminjaman f
JOIN dim_status_pinjam s ON s.status_pinjam_key = f.status_pinjam_key
GROUP BY s.status_pinjam_key, s.nama_status
ORDER BY s.status_pinjam_key;

-- 3. FK waktu yang tidak ditemukan di dim_waktu (harus 0)
SELECT COUNT(*) AS waktu_tidak_ditemukan
FROM fact_peminjaman f
LEFT JOIN dim_waktu w ON w.waktu_key = f.waktu_pengajuan_key
WHERE w.waktu_key IS NULL;

-- 4. Pembanding dengan sumber khusus siswa (harus sama dengan baris di nomor 1)
SELECT COUNT(*) AS sumber_siswa
FROM basdat_sumber.loans
WHERE user_id IN (SELECT user_id FROM basdat_sumber.users
                  WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa');
