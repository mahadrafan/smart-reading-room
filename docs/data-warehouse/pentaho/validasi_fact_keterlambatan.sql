-- Validasi tr_fact_keterlambatan (Isma - B2). Jalankan setelah transformasi selesai.
-- Acuan (angka-acuan.md, TGL_ETL = 2026-11-22, TARIF_DENDA = 1000):
--   899 baris; SUM flag_terlambat 188, flag_tepat_waktu 711 (20,9% terlambat);
--   SUM hari_terlambat 3.836, nominal_denda 3.836.000;
--   status_kembali_key 1..5 = 711 / 151 / 24 / 13 / 0.
-- Bila TGL_ETL berbeda, angka DW 3 PASTI berbeda (buku yang belum kembali dihitung sampai TGL_ETL).
USE basdat_dw;

-- 1. Jumlah baris dan total ukuran
SELECT COUNT(*)              AS baris,          -- 899
       SUM(flag_terlambat)   AS terlambat,      -- 188
       SUM(flag_tepat_waktu) AS tepat_waktu,    -- 711
       ROUND(100 * SUM(flag_terlambat) / COUNT(*), 1) AS persen_terlambat, -- 20.9
       SUM(hari_terlambat)   AS total_hari,     -- 3836
       SUM(nominal_denda)    AS total_denda     -- 3836000
FROM fact_keterlambatan;

-- 2. Sebaran dimensi khas dim_status_kembali (711 / 151 / 24 / 13 / 0)
SELECT s.status_kembali_key, s.status_pengembalian, s.posisi_buku, s.status_denda,
       COUNT(f.loan_id) AS baris
FROM dim_status_kembali s
LEFT JOIN fact_keterlambatan f ON f.status_kembali_key = s.status_kembali_key
GROUP BY s.status_kembali_key, s.status_pengembalian, s.posisi_buku, s.status_denda
ORDER BY s.status_kembali_key;

-- 3. Konsistensi antarukuran (harus 0)
SELECT COUNT(*) AS baris_tidak_konsisten
FROM fact_keterlambatan
WHERE flag_terlambat + flag_tepat_waktu <> 1
   OR (flag_terlambat = 1) <> (hari_terlambat > 0)
   OR nominal_denda <> hari_terlambat * 1000      -- ganti 1000 bila TARIF_DENDA berubah
   OR (status_kembali_key = 1) <> (flag_tepat_waktu = 1);

-- 4. FK waktu yang tidak ditemukan di dim_waktu (harus 0)
SELECT COUNT(*) AS waktu_tidak_ditemukan
FROM fact_keterlambatan f
LEFT JOIN dim_waktu w ON w.waktu_key = f.waktu_jatuh_tempo_key
WHERE w.waktu_key IS NULL;

-- 5. Pembanding dengan sumber khusus siswa (harus sama dengan baris di nomor 1)
SELECT COUNT(*) AS sumber_siswa
FROM basdat_sumber.loans
WHERE user_id IN (SELECT user_id FROM basdat_sumber.users
                  WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa')
  AND due_date IS NOT NULL
  AND (   (status = 'Dikembalikan' AND return_date IS NOT NULL)
       OR (status = 'Dipinjam' AND due_date < DATE '2026-11-22'));  -- = TGL_ETL
