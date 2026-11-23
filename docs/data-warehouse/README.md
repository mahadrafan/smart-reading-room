# Data Warehouse Smart Reading Room (Kelompok G)

Tiga data warehouse untuk admin perpustakaan, disusun sebagai **fact constellation**:
`fact_peminjaman` (DW 1), `fact_ulasan` (DW 2), dan `fact_keterlambatan` (DW 3) berbagi
satu dimensi `dim_waktu`. Peminjam yang dianalisis hanya siswa (tingkat → kelas).
Pembagian tugas dan keputusan kelompok ada di `laporan/Panduan-Pengerjaan-Implementasi-DW.pdf`.

## Struktur folder

| Lokasi | Isi | PIC |
|---|---|---|
| `sql/` | `01_ddl` (13 tabel `basdat_dw`), `03_etl` (ETL SQL acuan), `04_query_analisis`; nanti `05_view_dashboard.sql` | Rafan, Vincent |
| `sumber-dummy/` | `02_basdat_sumber.sql` dan generatornya, `basdat_dw_acuan.sql`, `angka-acuan.md` | Rafan |
| `pentaho/` | `tr_dim_waktu`, `tr_dim_dw1`–`tr_dim_dw3`; nanti `tr_fact_*`, `job_etl_dw.kjb`, `kettle.properties.contoh` | Irma, Isma |
| `dashboard/` | Berkas atau kode dashboard admin | Vincent, Mako |
| `laporan/` | Panduan pengerjaan, laporan DW, tangkapan layar, dan skrip pembuat dokumen | Mako |
| `gambar/`, `gambar-tanpa-judul/` | Skema setiap DW, fact constellation, dan pembanding versi dinormalisasi (`buat_gambar_*.py`) | – |
| `log-uji.xlsx` | Log uji bersama: satu baris per pengujian | Semua |

## Urutan menjalankan

1. Impor `sql/01_ddl_data_warehouse.sql` (membuat `basdat_dw`, 13 tabel kosong).
2. Impor `sumber-dummy/02_basdat_sumber.sql` (membuat `basdat_sumber`). Langkah 1 dan 2 boleh tertukar.
3. Isi DW lewat Pentaho, atau jalankan `sql/03_etl_data_warehouse.sql` sebagai acuan.
4. Jalankan `sql/04_query_analisis.sql`.
5. Cocokkan hasilnya dengan `sumber-dummy/angka-acuan.md`, lalu catat di `log-uji.xlsx`.

Untuk dashboard tanpa menunggu Pentaho, cukup impor `sumber-dummy/basdat_dw_acuan.sql`
(jangan di laptop yang dipakai untuk menguji Pentaho, karena nama databasenya sama).
