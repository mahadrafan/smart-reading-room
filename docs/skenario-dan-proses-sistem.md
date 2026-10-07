# 2.2.3 Skenario dan Proses Sistem

Smart Reading Room digunakan oleh siswa dan tenaga pendidik (guru/staf) sebagai peminjam, serta
petugas perpustakaan sebagai admin, untuk melakukan peminjaman buku perpustakaan sekolah secara
daring. Sistem ini dirancang agar proses peminjaman yang sebelumnya dicatat manual menjadi lebih
cepat, tertib, dan transparan. Peminjam dapat mencari buku dan melihat stok yang tersedia secara
langsung, mengajukan peminjaman dengan durasi yang dipilih sendiri, serta memantau status
pengajuannya melalui halaman Peminjaman Saya dan Notifikasi.

Admin dapat memvalidasi setiap pengajuan, memastikan stok buku masih tersedia, lalu
mengonfirmasi atau menolaknya. Sistem juga mencatat tanggal pengambilan dan pengembalian buku,
menghitung denda keterlambatan secara otomatis (Rp1.000 per hari), dan menyimpan setiap
aktivitas admin sebagai log. Setelah selesai membaca, peminjam dapat memberikan ulasan dan
rating yang membantu pembaca lain memilih buku.

## 2.2.3.1 Proses dan Alur

1. **Registrasi dan Login**
   - Pengguna baru mendaftar sebagai **Siswa** (mengisi NIS dan kelas) atau **Tenaga Pendidik**
     (mengisi NIP dan jabatan/mapel), beserta nama, email, nomor telepon dengan kode negara, dan
     password. Data tersimpan di tabel `users` dengan role Peminjam dan kolom `borrower_type`
     sesuai pilihan.
   - Pengguna (peminjam atau admin) login dengan NIS/NIP dan password. Sistem memverifikasi data
     pada tabel `users`, lalu membaca kolom `role` untuk mengarahkan admin ke Dashboard Admin dan
     peminjam ke halaman Katalog. Pilihan "Ingat saya" menyimpan `remember_token` agar pengguna
     tetap masuk.
   - Jika lupa password, pengguna dapat membuat password baru dengan memasukkan NIS/NIP dan email
     yang cocok dengan data di tabel `users`, lalu login kembali.

2. **Pencarian dan Pengecekan Ketersediaan Buku**
   - Peminjam membuka halaman Katalog yang menampilkan buku dari tabel `books` yang masih aktif
     (`is_active` = 1), lengkap dengan kategori (`categories`) dan penulis (`authors` melalui
     `book_authors`, berurutan sesuai kolom `urutan`).
   - Peminjam dapat mencari judul atau nama penulis, memfilter kategori, mengurutkan buku
     (judul, paling sering dipinjam, top review, terbaru), dan menampilkan buku yang tersedia saja.
   - Pada halaman detail buku, sistem menampilkan stok tersedia (`available_stock`), jumlah
     peminjaman dari tabel `loans`, serta rata-rata rating dan ulasan dari tabel `reviews`.

3. **Pengajuan Peminjaman**
   - Peminjam mengisi formulir peminjaman: memilih buku dan durasi pinjam (1, 3, 5, 7 hari, atau
     custom sampai 14 hari).
   - Sebelum disimpan, sistem memvalidasi aturan peminjaman:
     - buku masih aktif dan stoknya tersedia;
     - peminjam belum punya pengajuan atau peminjaman aktif untuk buku yang sama;
     - peminjam memiliki paling banyak 3 peminjaman aktif (Menunggu, Dikonfirmasi, atau Dipinjam);
     - buku yang baru dikembalikan belum bisa dipinjam lagi oleh peminjam yang sama pada hari
       pengembalian dan sehari setelahnya (jeda pinjam ulang), agar pembaca lain mendapat
       kesempatan.
   - Jika valid, data tersimpan di tabel `loans` dengan status **Menunggu**. Tanggal pengajuan
     (`request_date`) terisi otomatis, sedangkan tanggal pinjam (`loan_date`) masih kosong karena
     baru diisi saat buku diambil.
   - Selama status masih Menunggu, peminjam dapat membatalkan pengajuannya; data pengajuan tersebut
     dihapus dari tabel `loans`.

4. **Validasi oleh Admin**
   - Admin melihat daftar pengajuan berstatus Menunggu pada halaman Request Masuk, yang dapat
     dicari berdasarkan nama/NIS peminjam dan difilter per buku.
   - Admin memeriksa ketersediaan stok buku, lalu mengonfirmasi atau menolak pengajuan.
   - Jika dikonfirmasi, status di tabel `loans` menjadi **Dikonfirmasi**, kolom `approved_by` dan
     `approved_at` diisi, dan `available_stock` buku dikurangi satu. Proses ini berjalan dalam satu
     transaksi dengan penguncian baris, sehingga eksemplar terakhir tidak dapat dikonfirmasi untuk
     dua peminjam sekaligus.
   - Jika ditolak, status menjadi **Gagal**.

5. **Notifikasi kepada Peminjam**
   - Setiap perubahan status (pengajuan diterima, dikonfirmasi, dipinjam, dikembalikan, atau gagal)
     menghasilkan data baru di tabel `notifications` yang terhubung ke peminjaman terkait
     (`loan_id`).
   - Notifikasi yang belum dibaca (status "Belum Dibaca") muncul sebagai pop up saat peminjam
     membuka aplikasi, dan seluruh notifikasi dapat dilihat serta difilter per jenis di halaman
     Notifikasi.

6. **Pengambilan Buku**
   - Setelah dikonfirmasi, peminjam wajib mengambil buku di perpustakaan paling lambat **2 hari**
     sejak waktu konfirmasi (`approved_at`).
   - Saat buku diambil, admin menandai peminjaman menjadi **Dipinjam**. Sistem mengisi
     `loan_date` dengan tanggal pengambilan dan menggeser `due_date` agar durasi yang dipilih
     peminjam tetap utuh.
   - Jika buku tidak diambil dalam 2 hari, sistem otomatis mengubah status menjadi **Gagal** dan
     mengembalikan stok buku (`available_stock` bertambah satu).

7. **Pengembalian Buku dan Denda**
   - Peminjam mengembalikan buku ke perpustakaan, lalu admin menandai peminjaman menjadi
     **Dikembalikan** dan mengisi `return_date`. Stok tersedia buku bertambah kembali.
   - Jika pengembalian melewati `due_date`, sistem menghitung denda sebesar Rp1.000 per hari
     keterlambatan. Pengembalian buku yang terlambat dilakukan bersamaan dengan pelunasan denda;
     waktu pelunasan dan admin yang menerimanya tercatat di kolom `fine_paid_at` dan `fine_paid_by`.
   - Dashboard Admin menampilkan daftar peminjaman terlambat beserta nomor telepon peminjam yang
     terhubung langsung ke WhatsApp untuk pengingat.

8. **Ulasan dan Rating**
   - Peminjam dapat memberikan rating (1–5 bintang) dan komentar untuk buku. Data tersimpan di tabel
     `reviews`, dengan batasan satu ulasan per peminjam untuk setiap buku.
   - Peminjam dapat mengubah atau menghapus ulasannya sendiri, sedangkan admin dapat menghapus
     ulasan yang tidak pantas melalui halaman Kelola Ulasan.

9. **Pengelolaan Data dan Pencatatan Aktivitas Admin**
   - Admin mengelola data buku (tambah, ubah, nonaktifkan, aktifkan kembali), kategori, dan
     penulis. Admin yang menambah dan terakhir mengubah buku tercatat di kolom `created_by` dan
     `updated_by` pada tabel `books`.
   - Akun admin baru hanya dapat dibuat oleh admin melalui menu Kelola Admin, karena form daftar
     publik selalu membuat akun Peminjam. Admin juga dapat mengubah data dan mereset password admin
     lain; untuk mengganti password akunnya sendiri, admin wajib memasukkan password lama. Akun admin
     tidak dapat dihapus karena tercatat di `admin_logs`, `approved_by`, `fine_paid_by`, serta
     `created_by`/`updated_by` pada buku.
   - Buku yang dihapus hanya dinonaktifkan (`is_active` = 0) agar riwayat peminjaman dan ulasan
     tetap tersimpan. Buku hanya dapat dihapus permanen jika belum pernah dipinjam maupun diulas.
   - Setiap aksi admin (konfirmasi, tolak, pengambilan, pengembalian, pelunasan denda, serta
     perubahan data buku, kategori, penulis, ulasan, dan akun admin) dicatat di tabel `admin_logs` sebagai
     jejak audit.

## 2.2.3.2 Relasi Antar Entitas dalam Proses

- **Mendaftar dan Login:** Siswa dan tenaga pendidik terdaftar sebagai pengguna (`users`) dengan
  role Peminjam, sedangkan petugas perpustakaan terdaftar dengan role Admin yang akunnya dibuat oleh
  admin lain melalui menu Kelola Admin. Kedua peran berada dalam satu tabel dan dibedakan oleh
  kolom `role`.
- **Mengelompokkan dan Menulis:** Setiap buku (`books`) termasuk tepat satu kategori
  (`categories`), dan satu kategori dapat mengelompokkan banyak buku (1:N). Satu buku dapat ditulis
  oleh beberapa penulis dan satu penulis dapat menulis banyak buku (M:N), yang dicatat di tabel
  penghubung `book_authors` beserta urutan penulisnya.
- **Mengajukan dan Meminjam:** Peminjam (`users`) mengajukan peminjaman (`loans`); satu peminjam
  dapat memiliki banyak peminjaman, tetapi satu peminjaman hanya milik satu peminjam (1:N). Setiap
  peminjaman merujuk tepat satu buku (`books`), dan satu buku dapat dipinjam berkali-kali (1:N).
- **Memvalidasi dan Menyetujui:** Admin (`users` dengan role Admin) mengonfirmasi atau menolak
  peminjaman (`loans`), yang tercatat di kolom `approved_by`. Satu admin dapat memvalidasi banyak
  peminjaman (1:N).
- **Memicu dan Menerima Notifikasi:** Setiap perubahan status peminjaman (`loans`) memicu
  notifikasi (`notifications`) yang diterima oleh peminjam (`users`). Satu peminjaman dan satu
  peminjam dapat memiliki banyak notifikasi (1:N).
- **Mengembalikan dan Melunasi Denda:** Saat buku terlambat dikembalikan, admin (`users`) mencatat
  pelunasan denda pada peminjaman (`loans`) melalui kolom `fine_paid_by`. Besarnya denda dihitung
  dari selisih `due_date` dan `return_date`, sehingga tidak disimpan sebagai kolom tersendiri.
- **Menulis dan Mengulas:** Peminjam (`users`) menulis ulasan (`reviews`) untuk buku (`books`).
  Satu peminjam dapat mengulas banyak buku dan satu buku dapat memiliki banyak ulasan, tetapi satu
  peminjam hanya boleh memberi satu ulasan untuk setiap buku.
- **Mengelola dan Mencatat:** Admin (`users`) menambah dan mengubah data buku (`books`), yang
  tercatat di kolom `created_by` dan `updated_by`. Setiap aksi admin dicatat di tabel `admin_logs`;
  satu admin dapat memiliki banyak catatan log (1:N).
- **Menyelesaikan dan Menutup Proses:** Setelah buku dikembalikan dan denda (jika ada) lunas,
  status peminjaman menjadi Dikembalikan, stok buku bertambah kembali, dan peminjaman masuk ke
  riwayat. Peminjaman yang gagal juga ditutup: pengajuan yang ditolak tidak mengubah stok, sedangkan
  peminjaman yang sudah dikonfirmasi tetapi tidak diambil mengembalikan stok yang sempat dipesan.
