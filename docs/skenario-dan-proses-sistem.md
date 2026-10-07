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
   - Pengunjung yang belum login melihat halaman perkenalan berisi buku yang paling sering
     dipinjam, cuplikan koleksi, dan cara meminjam, dengan tombol menuju halaman login dan daftar.
   - Pengguna baru mendaftar sebagai **Siswa** (mengisi NIS dan kelas) atau **Tenaga Pendidik**
     (mengisi NIP dan jabatan), beserta nama, email, nomor telepon dengan kode negara, dan
     password. Data tersimpan di tabel `users` dengan role Peminjam dan kolom `borrower_type`
     sesuai pilihan.
   - Pengguna (peminjam atau admin) login dengan NIS/NIP dan password. Sistem memverifikasi data
     pada tabel `users`, lalu membaca kolom `role` untuk mengarahkan admin ke Dashboard Admin dan
     peminjam ke halaman Katalog. Pilihan "Ingat saya" menyimpan `remember_token` agar pengguna
     tetap masuk.
   - Jika lupa password, pengguna dapat membuat password baru dengan memasukkan NIS/NIP dan email
     yang cocok dengan data di tabel `users`, lalu login kembali.
   - Setelah login, peminjam dapat memperbarui data diri (nama, email, nomor telepon, kelas atau
     jabatan) dan mengganti password di halaman Profil dengan memasukkan password lama.

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
     dihapus dari tabel `loans`. Notifikasi yang sudah terkirim tetap tersimpan, tetapi kolom
     `loan_id`-nya dikosongkan (NULL).

4. **Validasi oleh Admin**
   - Admin melihat daftar pengajuan berstatus Menunggu pada halaman Request Masuk, yang dapat
     dicari berdasarkan nama/NIS peminjam dan difilter per buku.
   - Admin memeriksa ketersediaan stok buku, lalu mengonfirmasi atau menolak pengajuan.
   - Jika dikonfirmasi, status di tabel `loans` menjadi **Dikonfirmasi**, kolom `approved_by` dan
     `approved_at` diisi, dan `available_stock` buku dikurangi satu. Proses ini berjalan dalam satu
     transaksi dengan penguncian baris, sehingga eksemplar terakhir tidak dapat dikonfirmasi untuk
     dua peminjam sekaligus.
   - Jika ditolak, status menjadi **Gagal** tanpa mengubah stok. Kolom `approved_by` dan
     `approved_at` tetap diisi sehingga admin yang menolak juga tercatat.

5. **Notifikasi kepada Peminjam**
   - Setiap perubahan status (pengajuan diterima, dikonfirmasi, dipinjam, dikembalikan, atau gagal)
     menghasilkan data baru di tabel `notifications` yang terhubung ke peminjaman terkait
     (`loan_id`).
   - Notifikasi yang belum dibaca (status "Belum Dibaca") muncul sebagai pop up saat peminjam
     membuka aplikasi, dan seluruh notifikasi dapat dilihat serta difilter per jenis di halaman
     Notifikasi. Notifikasi pengajuan dibuat oleh peminjam sendiri, sehingga langsung berstatus
     "Dibaca" dan tidak muncul di pop up.
   - Selama buku terlambat dan dendanya belum lunas, sistem mengirim satu notifikasi **Terlambat**
     setiap hari berisi jumlah hari keterlambatan dan denda terbaru (misalnya Rp2.000 hari ini,
     Rp3.000 besok). Pengingat dibuat saat peminjam membuka aplikasi dan berhenti setelah buku
     dikembalikan dan denda lunas.
   - Admin juga menerima notifikasi di tabel yang sama (kolom `user_id` berisi admin penerima):
     setiap pengajuan baru dikirim ke semua admin, dan setiap hari admin mendapat pengingat untuk
     setiap peminjaman yang terlambat dan dendanya belum lunas. Notifikasi admin muncul sebagai pop up
     di halaman admin dan dapat dilihat di menu Notifikasi admin.
   - Selain di dalam web, peminjam menerima **email pengingat** ke alamat email akunnya (login tetap
     memakai NIS/NIP): sehari sebelum batas kembali (H-1) dan sekali saat buku pertama kali terlambat.
     Email dikirim otomatis setiap pagi oleh penjadwal, dan setiap email yang terkirim juga dicatat
     sebagai notifikasi "Pengingat" di tabel `notifications` agar tidak terkirim dua kali.

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
     terhubung langsung ke WhatsApp, sebagai pengingat tambahan selain notifikasi harian.
   - Halaman Riwayat menampilkan seluruh peminjaman per status (Dikonfirmasi, Dipinjam, Gagal,
     Dikembalikan), dengan filter **Terlambat** untuk peminjaman yang terlambat dan dendanya belum
     lunas, serta pencarian berdasarkan nama atau NIS/NIP peminjam dan nomor peminjaman. Setiap baris
     menampilkan admin yang terakhir memproses status tersebut, jenis aksinya, dan
     waktunya, yang dibaca dari tabel `admin_logs`. Peminjaman yang gagal otomatis karena tidak
     diambil ditampilkan sebagai diproses oleh "Sistem".
   - Admin dapat menghapus riwayat peminjaman yang sudah selesai (Dikembalikan atau Gagal). Riwayat
     yang dendanya belum lunas tidak dapat dihapus. Saat riwayat dihapus, notifikasi milik peminjam
     tetap tersimpan dengan `loan_id` dikosongkan (NULL).

8. **Ulasan dan Rating**
   - Peminjam dapat memberikan rating (1–5 bintang) dan komentar untuk buku. Data tersimpan di tabel
     `reviews`, dengan batasan satu ulasan per peminjam untuk setiap buku.
   - Peminjam dapat mengubah atau menghapus ulasannya sendiri, sedangkan admin dapat menghapus
     ulasan yang tidak pantas melalui halaman Kelola Ulasan.

9. **Pengelolaan Data dan Pencatatan Aktivitas Admin**
   - Dashboard Admin menampilkan ringkasan jumlah buku aktif, kategori, penulis, peminjam,
     pengajuan yang menunggu, dan peminjaman terlambat. Halaman Data Peminjam menampilkan daftar
     peminjam yang dapat dicari berdasarkan NIS/NIP, nama, kelas, atau jabatan, beserta jumlah
     peminjaman aktif dan yang terlambat milik setiap peminjam.
   - Admin mengelola data buku (tambah, ubah, nonaktifkan, aktifkan kembali), kategori, dan
     penulis. Admin yang menambah dan terakhir mengubah buku tercatat di kolom `created_by` dan
     `updated_by` pada tabel `books`.
   - Akun admin tidak dapat dibuat dari aplikasi, karena form daftar publik selalu membuat akun
     Peminjam. Akun admin ditambahkan langsung ke tabel `users` di database dengan `role` = 'Admin'
     dan password dalam bentuk hash, sehingga hanya pengelola database yang dapat menambah admin.
   - Buku yang dihapus hanya dinonaktifkan (`is_active` = 0) agar riwayat peminjaman dan ulasan
     tetap tersimpan. Buku hanya dapat dihapus permanen jika belum pernah dipinjam maupun diulas.
   - Setiap aksi admin (konfirmasi, tolak, pengambilan, pengembalian, pelunasan denda, hapus riwayat,
     serta perubahan data buku, kategori, penulis, dan ulasan) dicatat di tabel
     `admin_logs` sebagai jejak audit.

## 2.2.3.2 Relasi Antar Entitas dalam Proses

- **Mendaftar dan Login:** Siswa dan tenaga pendidik terdaftar sebagai pengguna (`users`) dengan
  role Peminjam, sedangkan petugas perpustakaan terdaftar dengan role Admin yang akunnya
  ditambahkan langsung melalui database. Kedua peran berada dalam satu tabel dan dibedakan oleh
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
  peminjaman (1:N). Aksi admin berikutnya pada peminjaman yang sama (menandai Dipinjam,
  Dikembalikan, atau denda lunas) dicatat di `admin_logs` dengan `table_name` = 'loans' dan
  `record_id` = `loan_id`.
- **Memicu dan Menerima Notifikasi:** Setiap perubahan status peminjaman (`loans`) memicu
  notifikasi (`notifications`) yang diterima oleh peminjam (`users`). Satu peminjaman dan satu
  peminjam dapat memiliki banyak notifikasi (1:N). Admin juga menerima notifikasi untuk pengajuan
  baru dan denda yang belum lunas, sehingga `notifications.user_id` dapat merujuk peminjam maupun
  admin. Pemilik notifikasi disimpan di kolom `user_id`
  sendiri karena notifikasi tetap milik peminjam meskipun peminjamannya dibatalkan atau
  riwayatnya dihapus (`loan_id` menjadi NULL).
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
