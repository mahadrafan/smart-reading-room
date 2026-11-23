-- Validasi tr_fact_ulasan (Isma - B2). Jalankan setelah transformasi selesai.
-- Acuan (angka-acuan.md): 389 baris; SUM jumlah_ulasan 389, rating 1.439 (rata-rata 3,70),
-- flag_rating_tinggi 349, flag_rating_rendah 40.
USE basdat_dw;

-- 1. Jumlah baris dan total ukuran (rata-rata = SUM/SUM, bukan rata-rata dari rata-rata)
SELECT COUNT(*)                 AS baris,       -- 389
       SUM(jumlah_ulasan)       AS ulasan,      -- 389
       SUM(rating)              AS total_rating,-- 1439
       ROUND(SUM(rating) / SUM(jumlah_ulasan), 2) AS rata_rata, -- 3.70
       SUM(flag_rating_tinggi)  AS tinggi,      -- 349
       SUM(flag_rating_rendah)  AS rendah       -- 40
FROM fact_ulasan;

-- 2. Sebaran dimensi khas dim_rating
SELECT r.rating_key, r.label_rating, r.kelompok_rating, COUNT(*) AS baris
FROM fact_ulasan f
JOIN dim_rating r ON r.rating_key = f.rating_key
GROUP BY r.rating_key, r.label_rating, r.kelompok_rating
ORDER BY r.rating_key;

-- 3. Konsistensi: rating_key = rating, dan tepat satu flag bernilai 1 (harus 0)
SELECT COUNT(*) AS baris_tidak_konsisten
FROM fact_ulasan
WHERE rating_key <> rating OR flag_rating_tinggi + flag_rating_rendah <> 1;

-- 4. FK waktu yang tidak ditemukan di dim_waktu (harus 0)
SELECT COUNT(*) AS waktu_tidak_ditemukan
FROM fact_ulasan f
LEFT JOIN dim_waktu w ON w.waktu_key = f.waktu_ulasan_key
WHERE w.waktu_key IS NULL;

-- 5. Pembanding dengan sumber khusus siswa dan rating 1-5 (harus sama dengan baris di nomor 1)
SELECT COUNT(*) AS sumber_siswa
FROM basdat_sumber.reviews
WHERE rating BETWEEN 1 AND 5
  AND user_id IN (SELECT user_id FROM basdat_sumber.users
                  WHERE role = 'Peminjam' AND COALESCE(borrower_type, 'Siswa') = 'Siswa');
