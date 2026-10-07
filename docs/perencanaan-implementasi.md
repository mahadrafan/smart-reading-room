# 2.3 Perencanaan Implementasi

Implementasi Smart Reading Room dibagi menjadi empat tahap, dimulai dari perancangan basis data
sampai sistem siap dipakai dan dirawat di lingkungan sekolah.

1. **Tahap 1 – Perancangan Basis Data**
   - Menganalisis kebutuhan data peminjaman buku sehingga diperoleh entitas utama: `users`,
     `categories`, `authors`, `books`, `book_authors`, `loans`, `notifications`, `reviews`, dan
     `admin_logs`.
   - Membuat ERD konseptual (notasi Chen) untuk menggambarkan entitas, atribut, dan relasinya,
     lalu menurunkannya menjadi ERD fisik lengkap dengan tipe data, primary key, dan foreign key.
   - Menormalisasi tabel hingga 3NF: kategori dan penulis dipisah menjadi tabel sendiri, relasi
     buku dengan penulis (M:N) dipecah melalui tabel penghubung `book_authors`, dan denda tidak
     disimpan sebagai kolom karena dapat dihitung dari `due_date` dan `return_date`.
   - Mengimplementasikan skema pada MySQL 8 (`basdat_final.sql`) dengan constraint yang menjaga
     integritas data: `UNIQUE` untuk NIS/NIP, email, nama kategori, nama penulis, serta satu ulasan
     per peminjam per buku; `FOREIGN KEY` dengan `RESTRICT` agar kategori, penulis, dan admin yang
     masih dipakai tidak terhapus; serta `ENUM` untuk role, jenis peminjam, dan status peminjaman.

2. **Tahap 2 – Pembuatan Aplikasi**
   - Mengembangkan aplikasi web dengan Laravel 12 (PHP 8.2) menggunakan pola MVC: model Eloquent
     untuk setiap tabel, controller terpisah untuk peminjam dan admin, serta tampilan Blade dengan
     HTML, CSS, dan JavaScript.
   - Menyamakan struktur database aplikasi dengan `basdat_final.sql` melalui migration, dan
     menyiapkan seeder untuk akun admin dan peminjam awal. Akun admin hanya ditambahkan melalui
     database (seeder atau langsung ke tabel `users`), tidak melalui form di aplikasi.
   - Mengimplementasikan autentikasi dan otorisasi: login dengan NIS/NIP, pilihan "Ingat saya",
     lupa password, pendaftaran Siswa/Tenaga Pendidik, serta middleware yang membatasi halaman
     sesuai role Admin atau Peminjam.
   - Mengimplementasikan fitur peminjam: katalog dengan pencarian, filter, dan pengurutan;
     pengajuan peminjaman beserta validasi aturannya (stok tersedia, paling banyak 3 peminjaman
     aktif, durasi sampai 14 hari, jeda pinjam ulang); pembatalan pengajuan; notifikasi dengan
     pop up, termasuk pengingat harian denda selama buku terlambat dan email pengingat H-1 serta saat
     terlambat; ulasan dan rating; serta halaman profil.
   - Mengimplementasikan fitur admin: Request Masuk untuk konfirmasi atau tolak, Riwayat untuk
     menandai buku dipinjam, dikembalikan, dan pelunasan denda; kelola buku, kategori, penulis,
     dan ulasan; Data Peminjam; notifikasi pop up untuk pengajuan baru dan denda yang belum lunas; serta Dashboard dengan daftar keterlambatan yang
     terhubung ke WhatsApp.
   - Menerapkan logika bisnis di sisi server: konfirmasi peminjaman berjalan dalam transaksi
     dengan penguncian baris (`lockForUpdate`) agar eksemplar terakhir tidak dikonfirmasi dua kali,
     pembatalan otomatis peminjaman yang tidak diambil dalam 2 hari beserta pengembalian stoknya,
     perhitungan denda Rp1.000 per hari, dan pencatatan setiap aksi admin ke `admin_logs`.
   - Menyamakan zona waktu aplikasi dan MySQL ke WIB (Asia/Jakarta, +07:00) agar tanggal
     pengajuan, batas kembali, dan denda mengikuti pergantian hari di Indonesia.

3. **Tahap 3 – Uji Coba & Validasi**
   - Menjalankan pengujian otomatis dengan PHPUnit (52 skenario uji) yang mencakup login dan
     pendaftaran, pengajuan sampai pengembalian, denda, gagal otomatis, kelola data,
     serta pemeriksaan bahwa migration menghasilkan tabel dan kolom yang sama dengan
     `basdat_final.sql`.
   - Melakukan uji coba manual dengan beberapa akun: peminjam Siswa, peminjam Tenaga Pendidik, dan
     beberapa akun admin, untuk memastikan setiap peran hanya dapat mengakses halamannya sendiri dan
     admin yang memproses peminjaman tercatat dengan benar.
   - Memvalidasi aturan data: stok tersedia tidak pernah minus, satu peminjam tidak bisa mengajukan
     buku yang sama selama peminjaman sebelumnya masih aktif, riwayat yang dendanya belum lunas tidak bisa dihapus, dan input di
     luar batas kolom MySQL (misalnya tahun terbit dan jumlah stok) ditolak dengan pesan yang jelas.
   - Melakukan debugging dan optimasi query: memuat relasi sekaligus (`with`, `withCount`) untuk
     menghindari query berulang (N+1), menggunakan paginasi pada daftar yang panjang, dan
     memanfaatkan index dari primary key, foreign key, dan unique key.

4. **Tahap 4 – Deployment & Maintenance**
   - Menyiapkan lingkungan server (Laragon atau server sekolah dengan PHP 8.2+, Composer, dan
     MySQL), kemudian menjalankan instalasi melalui script `setup-windows.ps1` / `setup-unix.sh`
     atau langkah manual di README (`composer install`, `migrate`, `storage:link`).
   - Mengatur konfigurasi produksi di `.env`: `APP_ENV=production`, `APP_DEBUG=false`, koneksi ke
     database MySQL, dan zona waktu WIB.
   - Pembatalan otomatis dan pengingat di dalam web diperiksa setiap kali halaman terkait dibuka.
     Email pengingat (H-1 dan saat terlambat) dikirim oleh perintah `php artisan pengingat:email`
     yang dijadwalkan setiap hari pukul 07.00, sehingga server perlu menjalankan penjadwal tugas
     (cron di Linux atau Task Scheduler di Windows) serta akun pengirim email (SMTP, misalnya Gmail).
   - Melakukan backup data secara berkala (harian atau mingguan) dengan `mysqldump` beserta folder
     sampul buku (`storage/app/public/covers`).
   - Melakukan maintenance berkala: memperbarui dependensi Laravel untuk keamanan, memantau
     `admin_logs` sebagai jejak audit, dan menambah atau menyesuaikan fitur sesuai masukan
     petugas perpustakaan dan peminjam.
