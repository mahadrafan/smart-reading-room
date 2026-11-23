# Pemetaan rumus 03_etl_data_warehouse.sql ke step Pentaho (B2 - Isma)

Lingkup: tiga transformasi fakta di folder `pentaho/`. Dimensi (Irma) harus sudah terisi dan tabel fakta sudah
dikosongkan oleh job sebelum fakta dijalankan.
Variabel (kettle.properties): `DB_HOST DB_PORT DB_USER DB_PASS SRC_DB DW_DB TGL_ETL TARIF_DENDA`.
Koneksi bersama (sama persis dengan file Irma): `src_sumber` (database sumber) dan `dw_target` (basdat_dw).
Tidak ada nama database, path, atau password yang ditulis langsung di step.

## Status uji (23 November 2026)

Diuji dengan Pan (PDI 9.4) pada `basdat_sumber` dari `sql/02_basdat_sumber.sql`, TGL_ETL = 2026-11-22, TARIF_DENDA = 1000.
Dimensi diisi oleh `03_etl` SQL (belum dengan .ktr Irma). Setiap fakta dikosongkan lalu diisi Pentaho, kemudian
dibandingkan baris per baris dengan hasil acuan SQL (semua kolom kecuali surrogate key).

| Transformasi | Baris | Ukuran | Gagal lookup | Beda vs acuan | Status |
|---|---|---|---|---|---|
| tr_fact_peminjaman | 985 | 985 / 905 / 74 | 0 | 0 | lolos |
| tr_fact_ulasan | 389 | 389 / 1.439 / 349 / 40 (rata-rata 3,70) | 0 | 0 | lolos |
| tr_fact_keterlambatan | 899 | 188 / 711; 3.836 hari; Rp3.836.000; status 711/151/24/13/0 | 0 | 0 | lolos |

Query validasi: `pentaho/validasi_fact_*.sql`.

**Uji gabungan dengan dimensi Irma (23 November 2026).** Ke-13 tabel `basdat_dw` dikosongkan, lalu dijalankan
berurutan: `tr_dim_waktu` -> `tr_dim_dw1` -> `tr_dim_dw2` -> `tr_dim_dw3` (file Irma dari Drive, tidak diubah) ->
`tr_fact_peminjaman` -> `tr_fact_ulasan` -> `tr_fact_keterlambatan`. Semua tanpa error dan tanpa baris gagal lookup.
Setiap tabel dibandingkan baris per baris dengan hasil `03_etl` SQL:

| Tabel | Baris | Beda (semua kolom) | Beda (tanpa surrogate key) |
|---|---|---|---|
| dim_waktu | 1.462 | 0 | 0 |
| dim_buku_pinjam · dim_buku_ulasan | 40 · 40 | 0 | 0 |
| dim_peminjam_pinjam · dim_pengulas · dim_peminjam_kembali | 60 · 60 · 60 | 0 | 0 |
| dim_status_pinjam · dim_rating · dim_status_kembali | 5 · 5 · 5 | 0 | 0 |
| dim_kategori_buku | 9 | 2 (lihat catatan) | 0 |
| fact_peminjaman · fact_ulasan · fact_keterlambatan | 985 · 389 · 899 | 0 | 0 |

Tingkat, kelas, nama, dan NIS tiap siswa sama di ketiga dimensi peminjam (20 siswa per tingkat).

Catatan `dim_kategori_buku`: baris "Tanpa Kategori" mendapat `kategori_key` 9 di Pentaho tetapi 16 di acuan SQL.
Selisih ini berasal dari acuan: `INSERT ... SELECT` di InnoDB memesan blok AUTO_INCREMENT sehingga baris berikutnya
melompat ke 16. Nilai surrogate key tidak bermakna, baris ini tidak punya fakta, dan semua fakta tetap identik.
Hasil Pentaho (1-9 berurutan) justru lebih rapi. `sql/basdat_dw_acuan.sql` juga berisi key 16.

**sql/04_query_analisis.sql pada hasil Pentaho.** Dijalankan pada `basdat_dw` hasil uji gabungan dan pada
`sql/basdat_dw_acuan.sql` (dimuat ke database terpisah). Hasil semua query identik; hanya dua baris bernilai seri
yang urutannya tertukar. Query dimensi khas:

| Query | Hasil Pentaho (sama dengan acuan) |
|---|---|
| 1.5 status peminjaman | Menunggu 1 · Dikonfirmasi 5 · Dipinjam 19 · Dikembalikan 886 · Gagal 74 |
| 2.5 skala rating | 6 · 34 · 122 · 136 · 91 |
| 3.5 status kembali dan denda | 711 · 151 · 24 · 13 (key 5 tidak muncul karena 0 baris); denda 0 · 758.000 · 136.000 · 2.942.000 |

Query 1.1, 2.1, dan 3.1 memakai `GROUPING()` yang hanya ada di MySQL 8 sehingga error di XAMPP (MariaDB 10.4).
Untuk uji, `GROUPING(x)` diganti `(x IS NULL)` (aman karena kolom tahun, kuartal, bulan, dan kategori tidak pernah
NULL di DW); hasilnya juga identik dengan acuan.

## Pola umum

`Table input (src_sumber)` -> `Formula / Calculator` -> `Database lookup (dw_target)` -> `Filter rows`
(key kosong -> `Write to log`) -> `Select values` -> `Table output (dw_target)`.

- Key waktu (yyyyMMdd) dihitung langsung di SQL Table input dengan `DATE_FORMAT`, karena hasilnya harus identik
  dengan `dim_waktu.waktu_key`; tanggal kosong -> 0.
- Database lookup memakai cache (load all) dan **tidak** membuang baris gagal; `cek_key_lengkap` (Filter rows)
  menggantikan INNER JOIN di SQL dan mengirim baris gagal ke `log_gagal_lookup`.
- PK fakta (`*_key`) tidak dikirim di Table output (AUTO_INCREMENT). Truncate di Table output sengaja `N`.

## 1. tr_fact_peminjaman (DW 1) - sumber: `loans`

| Rumus di SQL | Step Pentaho | Isi |
|---|---|---|
| `FROM loans` semua status | `in_loans` (Table input) | `loan_id, book_id, user_id, status`, `waktu_pengajuan_key = DATE_FORMAT(request_date,'%Y%m%d')` |
| `jumlah_pengajuan = 1` | `hitung_ukuran` (Formula) | `1` |
| `jumlah_berhasil = status IN (Dipinjam, Dikembalikan)` | `hitung_ukuran` | `IF(OR([status]="Dipinjam";[status]="Dikembalikan");1;0)` |
| `jumlah_gagal = status = 'Gagal'` | `hitung_ukuran` | `IF([status]="Gagal";1;0)` |
| `JOIN dim_buku_pinjam ON book_id` | `lk_buku_pinjam` (Database lookup) | -> `buku_pinjam_key` |
| `JOIN dim_peminjam_pinjam ON user_id` | `lk_peminjam_pinjam` | -> `peminjam_pinjam_key` (membuang tenaga pendidik) |
| `JOIN dim_status_pinjam ON nama_status = status` | `lk_status_pinjam` | -> `status_pinjam_key` |
| INNER JOIN | `cek_key_lengkap` + `log_gagal_lookup` | ketiga key harus terisi |
| INSERT | `pilih_kolom_fakta` -> `out_fact_peminjaman` | |

## 2. tr_fact_ulasan (DW 2) - sumber: `reviews`

| Rumus di SQL | Step Pentaho | Isi |
|---|---|---|
| `WHERE rating BETWEEN 1 AND 5` | `in_reviews` (Table input) | 393 sumber -> 389 (4 tanpa rating dibuang) |
| `waktu_ulasan_key = COALESCE(DATE_FORMAT(created_at,'%Y%m%d'),0)` | `in_reviews` | |
| `rating_key = rating` | `hitung_ukuran` (Formula) | `[rating]` (tanpa lookup: key dim_rating = nilai) |
| `jumlah_ulasan = 1` | `hitung_ukuran` | `1` |
| `flag_rating_tinggi = rating >= 3` | `hitung_ukuran` | `IF([rating]>=3;1;0)` |
| `flag_rating_rendah = rating <= 2` | `hitung_ukuran` | `IF([rating]<=2;1;0)` |
| `JOIN dim_buku_ulasan ON book_id` | `lk_buku_ulasan` | -> `buku_ulasan_key` |
| `JOIN dim_pengulas ON user_id` | `lk_pengulas` | -> `pengulas_key` |
| INNER JOIN | `cek_key_lengkap` + `log_gagal_lookup` | |
| INSERT | `pilih_kolom_fakta` -> `out_fact_ulasan` | |

## 3. tr_fact_keterlambatan (DW 3) - sumber: `loans` JOIN `books` LEFT JOIN `categories`

| Rumus di SQL | Step Pentaho | Isi |
|---|---|---|
| Filter DW 3 | `in_loans_jatuh_tempo` (Table input, *Replace variables* aktif) | `due_date IS NOT NULL AND ((status='Dikembalikan' AND return_date IS NOT NULL) OR (status='Dipinjam' AND due_date < '${TGL_ETL}'))` |
| `waktu_jatuh_tempo_key` | `in_loans_jatuh_tempo` | `DATE_FORMAT(due_date,'%Y%m%d')` |
| kategori untuk lookup | `in_loans_jatuh_tempo` | `nama_kategori = COALESCE(category_name,'Tanpa Kategori')` |
| `return_date IS NOT NULL`, `fine_paid_at IS NOT NULL` | `in_loans_jatuh_tempo` | `sudah_kembali`, `sudah_bayar` (0/1) supaya Formula tidak perlu cek NULL |
| `@tgl_etl`, `@tarif_denda` | `ambil_variabel` (Get variables) | `tgl_etl` (Date, yyyy-MM-dd), `tarif_denda` (Integer) |
| `COALESCE(return_date, tgl_etl) - due_date` | `hitung_selisih_hari` (Calculator) | `tgl_acuan` = NVL(return_date, tgl_etl); `hari_mentah` = DATE_DIFF(tgl_acuan, due_date) |
| `GREATEST(..., 0)` | `hitung_hari` (Formula) | `hari_terlambat = IF([hari_mentah]<0;0;[hari_mentah])` |
| `status_kembali_key` CASE | `hitung_ukuran` (Formula) | `IF([hari_terlambat]=0;1;IF([sudah_kembali]=1;IF([sudah_bayar]=1;2;3);IF([sudah_bayar]=1;5;4)))` |
| `flag_terlambat`, `flag_tepat_waktu` | `hitung_ukuran` | `IF([hari_terlambat]>0;1;0)`, `IF([hari_terlambat]=0;1;0)` |
| `nominal_denda = hari * tarif` | `hitung_ukuran` | `[hari_terlambat]*[tarif_denda]` |
| `JOIN dim_kategori_buku ON category_id <=> category_id` | `lk_kategori_buku` | lookup lewat **nama_kategori**, karena lookup Pentaho tidak mencocokkan NULL |
| `JOIN dim_peminjam_kembali ON user_id` | `lk_peminjam_kembali` | -> `peminjam_kembali_key` |
| INNER JOIN | `cek_key_lengkap` + `log_gagal_lookup` | |
| INSERT | `pilih_kolom_fakta` -> `out_fact_keterlambatan` | |

## Catatan untuk kelompok

1. **TGL_ETL harus sama** dengan acuan (2026-11-22) saat validasi; kalau beda, DW 3 pasti beda.
2. Sebelum fakta dijalankan ulang, tabel fakta harus kosong (job Irma). Tanpa itu insert gagal karena `loan_id` /
   `review_id` unik.
3. XAMPP di laptop Isma memakai **MariaDB 10.4**, bukan MySQL 8. Baris `SET SESSION cte_max_recursion_depth` di
   `03_etl` error di MariaDB, tetapi hasil akhirnya tetap sama dengan acuan.
4. Pesan ERROR "Unexpected error registering jar plugin ... Dialog" saat menjalankan Pan hanya plugin UI yang tidak
   dimuat di mode tanpa jendela, tidak memengaruhi hasil.
5. `sql/04_query_analisis.sql` query 1.1, 2.1, 3.1 error di MariaDB karena `GROUPING()` (lihat di atas).
6. Spoon perlu Java 64-bit (libswt hanya `win64`). Bila `JAVA_HOME` menunjuk Java 32-bit, set `PENTAHO_JAVA_HOME`
   ke JDK 11 64-bit (keputusan D2).
