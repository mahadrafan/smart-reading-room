# Angka acuan data warehouse

Hasil `03_etl_data_warehouse.sql` (ETL SQL) pada database `basdat_sumber` dari `00_basdat_sumber.sql`,
dengan **TGL_ETL = 22 November 2026** dan **TARIF_DENDA = 1.000**. Diuji 22 November 2026.

Hasil Pentaho (Irma, Isma) dianggap benar bila angka-angkanya sama persis dengan tabel di bawah, dengan
`TGL_ETL` dan `TARIF_DENDA` yang sama. Isi lengkap `basdat_dw` hasil acuan ada di `basdat_dw_acuan.sql`.

## Urutan menjalankan

1. `00_basdat_sumber.sql` membuat database sumber dummy `basdat_sumber`.
2. `01_ddl_data_warehouse.sql` membuat `basdat_dw` (13 tabel, masih kosong).
3. Isi DW dengan salah satu cara:
   - Pentaho (job `job_etl_dw.kjb`), atau
   - `03_etl_data_warehouse.sql` sebagai acuan.
4. `04_query_analisis.sql` menjalankan query analisis.

Kalau hanya perlu DW yang sudah terisi (misalnya untuk dashboard), cukup impor `basdat_dw_acuan.sql`.

## Database sumber (`basdat_sumber`)

| Tabel | Baris | Keterangan |
|---|---|---|
| users | 62 | 2 admin + 60 siswa (20 per tingkat, 9 kelas); tanpa tenaga pendidik |
| categories | 8 | |
| books | 40 | 5 per kategori; 2 nonaktif sejak 1 Jan 2026 |
| authors · book_authors | 30 · 46 | 6 buku ditulis dua penulis |
| loans | 985 | 1 Jan 2025 s.d. 21 Nov 2026 |
| reviews | 393 | 4 tanpa rating, jadi dibuang oleh ETL |

Status peminjaman:

| Status | Jumlah |
|---|---|
| Dikembalikan | 886 |
| Gagal | 74 |
| Dipinjam | 19 |
| Dikonfirmasi | 5 |
| Menunggu | 1 |

## Jumlah baris `basdat_dw`

| Tabel | Baris |
|---|---|
| dim_waktu (bersama) | 1.462 |
| dim_buku_pinjam · dim_buku_ulasan | 40 · 40 |
| dim_kategori_buku | 9 (8 + Tanpa Kategori) |
| dim_peminjam_pinjam · dim_pengulas · dim_peminjam_kembali | 60 · 60 · 60 |
| dim_status_pinjam · dim_rating · dim_status_kembali | 5 · 5 · 5 |
| fact_peminjaman | 985 |
| fact_ulasan | 389 |
| fact_keterlambatan | 899 |

## Total ukuran

| Fakta | Ukuran | Nilai |
|---|---|---|
| fact_peminjaman | SUM jumlah_pengajuan · jumlah_berhasil · jumlah_gagal | 985 · 905 · 74 |
| fact_ulasan | SUM jumlah_ulasan · rating | 389 · 1.439 (rata-rata 3,70) |
| fact_ulasan | SUM flag_rating_tinggi · flag_rating_rendah | 349 · 40 |
| fact_keterlambatan | SUM flag_terlambat · flag_tepat_waktu | 188 · 711 (20,9% terlambat) |
| fact_keterlambatan | SUM hari_terlambat · nominal_denda | 3.836 · 3.836.000 |

Sebaran `status_kembali_key`:

| Key | Status | Baris |
|---|---|---|
| 1 | Tepat waktu | 711 |
| 2 | Terlambat, sudah kembali, lunas | 151 |
| 3 | Terlambat, sudah kembali, belum lunas | 24 |
| 4 | Terlambat, belum kembali, belum lunas | 13 |
| 5 | Terlambat, belum kembali, lunas | 0 |

## Pola yang sengaja ada di data dummy

Pola ini berguna untuk memastikan dashboard menampilkan hasil yang masuk akal:

- **Kategori favorit per tingkat:**
  - Kelas 10: Fiksi & Sastra
  - Kelas 11: Komputer & Teknologi
  - Kelas 12: Sains & Matematika
- **Keterlambatan meningkat menurut tingkat:** Kelas 10 15,8%, Kelas 11 19,4%, Kelas 12 26,7%.
- **Peminjaman sepi pada Juni, Juli, dan Desember** (libur sekolah). Tahun 2026 lebih ramai daripada 2025 (515 vs 470).
- **Tiga buku sengaja diberi banyak rating rendah:** book_id 9, 18, 27.
- **Siswa Kelas 10 baru terdaftar mulai Juli 2025**, sehingga peminjaman mereka baru muncul setelah bulan itu.

Kalau `buat_basdat_sumber.py` dijalankan ulang tanpa mengubah seed, hasilnya tetap sama.
