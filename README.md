# Smart Reading Room

Smart Reading Room adalah aplikasi perpustakaan berbasis Laravel 12 untuk
mengelola katalog, peminjaman, denda, ulasan, dan notifikasi.

Repository: <https://github.com/mahadrafan/smart-reading-room>

## Fitur

- Login satu pintu untuk Admin dan Peminjam
- Peminjam khusus siswa: daftar dengan NIS dan kelas
- Katalog, pencarian, kategori, penulis, stok, dan sampul buku
- Pengajuan, persetujuan, pengambilan, pengembalian, dan pembatalan peminjaman
- Penguncian otomatis ketika stok habis
- Tenggat pengambilan dan pembatalan otomatis
- Denda keterlambatan beserta status Belum Lunas/Lunas
- Data kontak peminjam (telepon/WhatsApp) untuk tindak lanjut admin
- Ulasan dan rating buku
- Notifikasi peminjaman di dalam aplikasi, dengan pop up untuk notifikasi baru
- Email pengingat pengembalian (H-1, hari batas kembali, terlambat, terlambat 7 hari)

## Kebutuhan sistem

- PHP 8.2 atau lebih baru
- Ekstensi PHP: `ctype`, `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo`,
  serta `pdo_sqlite` atau `pdo_mysql` (`gd` dibutuhkan untuk menjalankan semua tes)
- Composer 2
- Git

Di Windows bisa memakai Laragon atau XAMPP, asalkan versi PHP-nya 8.2 atau lebih
baru. XAMPP versi lama masih membawa PHP 8.0 dan tidak bisa menjalankan Laravel 12.

Periksa versi yang aktif:

```powershell
php -v
composer --version
git --version
```

## Instalasi otomatis di Windows

Pastikan PHP 8.2+, Composer, dan Git tersedia di `PATH`, kemudian jalankan:

```powershell
git clone https://github.com/mahadrafan/smart-reading-room.git
cd smart-reading-room
powershell -ExecutionPolicy Bypass -File scripts/setup-windows.ps1
php artisan serve
```

Buka <http://127.0.0.1:8000>.
Skrip otomatis memakai **SQLite dan akun demo hasil seeder** (lihat **Akun demo**).

## Instalasi otomatis di Linux/macOS

```bash
git clone https://github.com/mahadrafan/smart-reading-room.git
cd smart-reading-room
chmod +x scripts/setup-unix.sh
./scripts/setup-unix.sh
php artisan serve
```

Skrip otomatis memakai **SQLite dan akun demo hasil seeder**.

## Instalasi manual dengan SQLite

SQLite cocok untuk demo dan pengembangan karena tidak memerlukan server database.

### Windows PowerShell

```powershell
git clone https://github.com/mahadrafan/smart-reading-room.git
cd smart-reading-room
composer install
Copy-Item .env.example .env
New-Item database/database.sqlite -ItemType File -Force
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

### Linux/macOS

```bash
git clone https://github.com/mahadrafan/smart-reading-room.git
cd smart-reading-room
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

## Instalasi dengan MySQL

1. Buat database kosong bernama `basdat`.
2. Salin `.env.example` menjadi `.env`.
3. Ubah konfigurasi database di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basdat
DB_USERNAME=root
DB_PASSWORD=
```

4. Jalankan perintah berikut:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Untuk MySQL Laragon, `DB_USERNAME=root` dan password kosong biasanya merupakan
konfigurasi bawaan. Sesuaikan jika instalasi Anda berbeda.

## Menggunakan database terbaru dari repository

File `basdat_final.sql` berisi salinan database pengembangan terbaru, termasuk katalog,
akun, peminjaman, denda, ulasan, dan notifikasi. Gunakan cara ini jika
ingin memperoleh data yang sama seperti laptop pengembangan.

Dump ini hanya berisi 9 tabel aplikasi (`users`, `categories`, `authors`, `books`,
`book_authors`, `loans`, `reviews`, `notifications`, `admin_logs`) dan **tidak memuat
tabel `migrations`**. Tabel `book_authors` menghubungkan buku dengan penulisnya,
karena satu buku bisa ditulis lebih dari satu penulis. Karena itu, **jangan menjalankan `php artisan migrate`** pada
database hasil impor: Laravel akan mencoba membuat ulang semua tabel dan berhenti
dengan error *table already exists*. Perubahan struktur database dibagikan dengan
memperbarui `basdat_final.sql`, lalu setiap anggota mengimpor ulang file tersebut.
Migration di `database/migrations` (2 file) membuat 9 tabel yang sama persis dengan dump
ini; migration hanya dipakai untuk instalasi baru dengan data demo dan untuk pengujian.

Pastikan konfigurasi `.env` memakai MySQL seperti pada bagian sebelumnya, lalu
jalankan dari folder proyek:

```powershell
mysql -u root -p -e "DROP DATABASE IF EXISTS basdat; CREATE DATABASE basdat CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p -e "USE basdat; SOURCE basdat_final.sql;"
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Perintah pertama mengganti seluruh isi database `basdat`. Jika ada data penting,
ekspor database tersebut terlebih dahulu. Jika password MySQL kosong, tekan Enter
saat diminta.

Gambar sampul buku ikut tersimpan di repository pada `storage/app/public/covers`,
sehingga cukup menjalankan `php artisan storage:link` agar sampul tampil. Buku yang
file sampulnya tidak tersedia akan ditampilkan dengan inisial judul.

Akun yang sudah diverifikasi pada dump terbaru:

| Role | NIS / ID admin | Password |
| --- | --- | --- |
| Admin | `ADM001` | `admin12345` |
| Peminjam | `2024001` | `peminjam123` |
| Peminjam pengujian stok | `QASTOK` | `peminjam123` |

## Akun demo

Akun ini dibuat oleh `php artisan migrate --seed`:

| Role | NIS / ID admin | Password |
| --- | --- | --- |
| Admin | `ADM001` | `admin12345` |
| Peminjam (siswa) | `2024001` | `peminjam123` |

Ganti password akun demo sebelum aplikasi digunakan di lingkungan nyata.

## Login, pendaftaran, dan lupa password

- Di halaman login, isi kolom **NIS atau ID admin** (misalnya `2024001` atau
  `ADM001`), bukan alamat email atau nama.
- Pendaftaran hanya untuk **siswa**: nama, email, NIS, kelas, no. HP, dan password.
  Akun baru selalu menjadi Peminjam. Akun admin tidak bisa dibuat dari halaman daftar.
- **Lupa password** hanya untuk siswa (cocokkan NIS dan email). Password akun admin
  diubah langsung di database.

Kolom `borrower_type` dan `job_title` masih ada di tabel `users` untuk menjaga
struktur database, tetapi aplikasi selalu mengisi `borrower_type = 'Siswa'` dan
tidak memakai `job_title`.

## Jika NIS atau password dianggap salah

Pertama, pastikan jalur instalasi dan akun yang digunakan sesuai:

| Cara setup | `DB_CONNECTION` | ID admin | Password |
| --- | --- | --- | --- |
| Skrip otomatis atau `migrate --seed` | `sqlite`/`mysql` | `ADM001` | `admin12345` |
| Impor `basdat_final.sql` | `mysql` | `ADM001` | `admin12345` |

Jalankan perintah berikut dari folder proyek untuk melihat database yang
dipakai Laravel dan daftar NIS / ID admin di dalamnya:

```powershell
php artisan optimize:clear
php artisan tinker --execute="dump(config('database.default'), DB::table('users')->pluck('nim_nip'));"
```

Jika hasilnya `mysql` tetapi `ADM001` tidak ada, impor belum masuk ke database
yang dipakai aplikasi. Periksa kembali `DB_DATABASE` di `.env`, impor ulang ke
database itu, lalu jalankan `php artisan optimize:clear`.

## Memindahkan seluruh data dari laptop lama

Git menyimpan kode aplikasi, tetapi tidak menyimpan database lokal maupun file
unggahan. Untuk membuat laptop baru sama persis, pindahkan keduanya.

### 1. Ekspor database MySQL di laptop lama

```powershell
mysqldump -u root -p basdat > basdat_backup.sql
```

Jika password MySQL kosong, tekan Enter saat diminta. Alternatifnya, gunakan menu
Export di phpMyAdmin.

### 2. Impor di laptop baru

Buat database `basdat`, lalu jalankan:

```powershell
mysql -u root -p basdat < basdat_backup.sql
```

Jika backup lama belum memuat perubahan struktur terbaru, impor
`basdat_final.sql` (lihat bagian sebelumnya) alih-alih menjalankan
`php artisan migrate`.

### 3. Salin file unggahan

Salin isi folder sampul buku berikut ke lokasi yang sama di laptop baru:

- `storage/app/public/covers`

Kemudian jalankan:

```powershell
php artisan storage:link
```

## Notifikasi dan pengingat keterlambatan

Notifikasi peminjaman tampil di halaman **Notifikasi** di dalam web, dengan pop up
di halaman mana pun selama masih ada yang belum dibaca (status **Belum Dibaca** di
tabel `notifications`, menjadi **Dibaca** setelah tombol **Mengerti** ditekan atau
halaman Notifikasi dibuka).

- **Peminjam** menerima perubahan status peminjaman (dikonfirmasi, dipinjam,
  dikembalikan, ditolak, gagal otomatis) dan, selama buku terlambat dan denda belum
  lunas, satu pengingat **Terlambat** setiap hari dengan denda terbaru.
- **Admin** menerima notifikasi setiap ada pengajuan baru dan pengingat harian untuk
  setiap peminjaman yang terlambat dan dendanya belum lunas (menu **Notifikasi** di
  panel admin).

Pengingat di web dibuat saat halaman dibuka, jadi tidak membutuhkan scheduler.
Admin tetap bisa menghubungi peminjam lewat nomor telepon / WhatsApp di Dashboard.

## Email pengingat (H-1, hari batas kembali, terlambat, dan terlambat 7 hari)

Peminjam login memakai NIS, tetapi email pengingat dikirim ke kolom `email`
akunnya. Masing-masing hanya **sekali** per peminjaman:

- **H-1**: sehari sebelum batas kembali buku yang sedang dipinjam.
- **Hari batas kembali**: di hari masa pinjam habis, mengingatkan agar buku dikembalikan
  hari itu juga sebelum terkena denda.
- **Terlambat**: saat buku pertama kali melewati batas kembali.
- **Terlambat 7 hari**: saat buku sudah terlambat 7 hari dan denda masih belum lunas.

Setiap email yang berhasil terkirim juga muncul sebagai notifikasi di web (jenis
**Pengingat**, **Batas Kembali**, atau **Terlambat 7 Hari**). Notifikasi yang belum
dibaca ditutup otomatis saat buku dikembalikan atau denda lunas. Email yang gagal
terkirim dicoba lagi pada jadwal berikutnya.

### 1. Pengaturan pengirim (Gmail)

Bawaannya `MAIL_MAILER=log`: email hanya ditulis ke `storage/logs/laravel.log`.
Untuk benar-benar mengirim lewat Gmail:

1. Aktifkan **Verifikasi 2 Langkah** di akun Google pengirim.
2. Buat **App Password** di <https://myaccount.google.com/apppasswords>
   (16 huruf, bukan password Gmail biasa).
3. Ubah `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alamat.pengirim@gmail.com
MAIL_PASSWORD=app-password-16-huruf
MAIL_FROM_ADDRESS="alamat.pengirim@gmail.com"
MAIL_FROM_NAME="Smart Reading Room"
```

4. Jalankan `php artisan config:clear`, lalu coba kirim:

```powershell
php artisan pengingat:email
```

### 2. Menjalankan otomatis setiap hari (pukul 07.00 WIB)

Jadwal sudah terdaftar (`php artisan schedule:list`), tetapi perlu dipanggil oleh
sistem operasi:

- **Linux/macOS (cron)**: tambahkan lewat `crontab -e`

```bash
* * * * * cd /path/ke/smart-reading-room && php artisan schedule:run >> /dev/null 2>&1
```

- **Windows (Task Scheduler)**: buat tugas harian pukul 07.00 dengan program
  `php` dan argumen `artisan pengingat:email`, folder awal (*Start in*) diisi
  folder proyek.

## Aset frontend

Tampilan memakai CSS dan JavaScript biasa di `public/css` dan `public/js`, tanpa
proses build. Cukup ubah file tersebut lalu muat ulang halaman.

## Pengujian

```powershell
php artisan test
```

Tes memakai SQLite di memori, jadi tidak menyentuh database MySQL. Tes unggah
sampul buku membutuhkan ekstensi PHP `gd`; jika belum aktif (misalnya di XAMPP),
hapus tanda `;` di depan `extension=gd` pada `php.ini`.

## Data warehouse

Rancangan dan skrip data warehouse (tugas Basis Data) ada di `docs/data-warehouse`,
terpisah dari aplikasi:

| Berkas | Isi |
| --- | --- |
| `00_basdat_sumber.sql` | Database sumber dummy `basdat_sumber` (60 siswa, 985 peminjaman, 393 ulasan) |
| `01_ddl_data_warehouse.sql` | Struktur `basdat_dw`: 3 DW (fact constellation, `dim_waktu` bersama), 13 tabel |
| `03_etl_data_warehouse.sql` | ETL SQL acuan dari `basdat_sumber` ke `basdat_dw` |
| `04_query_analisis.sql` | Query analisis per DW |
| `basdat_dw_acuan.sql`, `angka-acuan.md` | `basdat_dw` yang sudah terisi dan angka untuk mencocokkan hasil Pentaho |
| `gambar/` | Skema setiap DW dan pembanding versi dinormalisasi |
| `Panduan-Pengerjaan-Implementasi-DW.pdf` | Pembagian tugas dan keputusan kelompok |

Urutan impor: `00` → `01` → (Pentaho atau `03`) → `04`. Database `basdat_sumber`
dan `basdat_dw` terpisah dari database aplikasi, jadi data aplikasi tidak berubah.

## Mengambil pembaruan dari GitHub

```powershell
git pull origin main
composer install
php artisan optimize:clear
```

Jika `basdat_final.sql` ikut berubah, impor ulang file tersebut seperti pada bagian
**Menggunakan database terbaru dari repository** (isi database lokal akan
diganti). Jangan menjalankan `php artisan migrate` pada database hasil impor.

## Perintah pemecahan masalah

### `php` masih mengarah ke XAMPP 8.0

Gunakan PHP Laragon secara langsung atau letakkan folder PHP Laragon lebih dahulu
di environment variable `PATH`.

```powershell
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe artisan serve
```

Nama folder versi PHP dapat berbeda di setiap laptop.

### `No application encryption key has been specified`

```powershell
php artisan key:generate
```

### Tabel database belum tersedia

Impor `basdat_final.sql` seperti pada bagian **Menggunakan database terbaru dari
repository**. Perintah `php artisan migrate --seed` hanya boleh dipakai pada
database yang benar-benar kosong (instalasi baru dengan data demo), tidak pada
database hasil impor.

### Sampul tidak tampil

```powershell
php artisan storage:link
```

### Perubahan `.env` belum terbaca

```powershell
php artisan optimize:clear
```

### Port 8000 sedang digunakan

```powershell
php artisan serve --port=8001
```

Lalu buka <http://127.0.0.1:8001>.

## Folder penting

| Lokasi | Isi |
| --- | --- |
| `app/Http/Controllers` | Logika halaman dan proses aplikasi |
| `app/Models` | Model database |
| `database/migrations` | Struktur database (2 file, sama dengan `basdat_final.sql`) |
| `database/seeders` | Data awal dan akun demo |
| `resources/views` | Tampilan Blade |
| `public/css` | CSS aplikasi |
| `public/js` | JavaScript filter daftar (ulasan & request masuk) |
| `routes/web.php` | Daftar route web |
| `tests/Feature` | Pengujian fitur |
| `docs/data-warehouse` | Rancangan, skrip, dan panduan data warehouse |

## Catatan keamanan repository

File berikut sengaja tidak dikirim ke GitHub:

- `.env`
- database SQLite lokal
- `vendor`
- log aplikasi
- `storage/app/private` (termasuk backup database lokal)

Sampul buku di `storage/app/public/covers` justru ikut disimpan di repository agar
tampil sama di setiap laptop. Gunakan `.env.example` sebagai templat konfigurasi pada setiap laptop.
