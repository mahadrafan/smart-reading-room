# Smart Reading Room

Smart Reading Room adalah aplikasi perpustakaan berbasis Laravel 12 untuk
mengelola katalog, peminjaman, denda, ulasan, e-book, dan notifikasi.

Repository: <https://github.com/mahadrafan/smart-reading-room>

## Fitur

- Login satu pintu untuk Admin dan Peminjam
- Katalog, pencarian, kategori, penulis, stok, dan sampul buku
- Pengajuan, persetujuan, pengambilan, pengembalian, dan pembatalan peminjaman
- Penguncian otomatis ketika stok habis
- Tenggat pengambilan dan pembatalan otomatis
- Denda keterlambatan beserta status Belum Lunas/Lunas
- Data kontak peminjam untuk tindak lanjut admin
- Ulasan dan rating buku
- Unggah serta unduh e-book PDF
- Notifikasi dalam aplikasi dan email
- Pengingat keterlambatan terjadwal

## Kebutuhan sistem

- PHP 8.2 atau lebih baru
- Ekstensi PHP: `ctype`, `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo`,
  serta `pdo_sqlite` atau `pdo_mysql`
- Composer 2
- Git
- Node.js dan npm hanya jika aset Vite ingin dikembangkan ulang

Untuk Windows, Laragon direkomendasikan. Jangan memakai PHP 8.0 dari XAMPP
karena Laravel 12 membutuhkan PHP 8.2+.

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

## Instalasi otomatis di Linux/macOS

```bash
git clone https://github.com/mahadrafan/smart-reading-room.git
cd smart-reading-room
chmod +x scripts/setup-unix.sh
./scripts/setup-unix.sh
php artisan serve
```

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
akun, peminjaman, denda, ulasan, e-book, dan notifikasi. Gunakan cara ini jika
ingin memperoleh data yang sama seperti laptop pengembangan.

Pastikan konfigurasi `.env` memakai MySQL seperti pada bagian sebelumnya, lalu
jalankan dari folder proyek:

```powershell
mysql -u root -p -e "DROP DATABASE IF EXISTS basdat; CREATE DATABASE basdat CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p -e "USE basdat; SOURCE basdat_final.sql;"
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Perintah pertama mengganti seluruh isi database `basdat`. Jika ada data penting,
ekspor database tersebut terlebih dahulu. Jika password MySQL kosong, tekan Enter
saat diminta.

Akun yang sudah diverifikasi pada dump terbaru:

| Role | NIS/NIP | Password |
| --- | --- | --- |
| Admin | `ADM001` | `admin12345` |
| Peminjam | `2024001` | `peminjam123` |
| Peminjam pengujian stok | `QASTOK` | `peminjam123` |

## Akun demo

Akun ini dibuat oleh `php artisan migrate --seed`:

| Role | NIS/NIP | Password |
| --- | --- | --- |
| Admin | `ADMIN001` | `admin12345` |
| Peminjam | `2026001` | `peminjam123` |

Ganti password akun demo sebelum aplikasi digunakan di lingkungan nyata.

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
php artisan migrate --force
```

Perintah migrasi terakhir memasang perubahan skema yang mungkin belum terdapat
pada backup lama.

### 3. Salin file unggahan

Salin isi folder berikut ke lokasi yang sama di laptop baru:

- `storage/app/private/ebooks`
- `storage/app/public/covers`

Kemudian jalankan:

```powershell
php artisan storage:link
```

## Konfigurasi email

Secara bawaan, email ditulis ke `storage/logs/laravel.log` sehingga fitur dapat
diuji tanpa akun email eksternal.

Untuk SMTP sungguhan, ubah `.env`, misalnya:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=alamat-email
MAIL_PASSWORD=password-aplikasi
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="perpustakaan@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Setelah mengubah `.env`, jalankan `php artisan config:clear`. Jangan commit `.env`
atau password SMTP ke Git.

## Scheduler pengingat keterlambatan

Saat pengembangan, buka terminal kedua:

```powershell
php artisan schedule:work
```

Perintah pengingat juga dapat diuji manual:

```powershell
php artisan loans:send-overdue-reminders
```

Pada server Linux, tambahkan cron berikut agar scheduler berjalan setiap menit:

```cron
* * * * * cd /lokasi/smart-reading-room && php artisan schedule:run >> /dev/null 2>&1
```

## Mengembangkan aset frontend

Halaman utama menggunakan CSS publik yang sudah tersedia. Untuk mengembangkan
aset Vite:

```powershell
npm install
npm run dev
```

Untuk membuat aset produksi, jalankan `npm run build`.

## Pengujian

```powershell
php artisan test
```

## Mengambil pembaruan dari GitHub

```powershell
git pull origin main
composer install
php artisan migrate --force
php artisan optimize:clear
```

Jalankan `npm install` dan `npm run build` hanya jika dependency atau aset Vite
berubah.

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

```powershell
php artisan migrate --seed
```

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
| `database/migrations` | Struktur database |
| `database/seeders` | Data awal dan akun demo |
| `resources/views` | Tampilan Blade |
| `public/css` | CSS aplikasi |
| `routes/web.php` | Daftar route web |
| `routes/console.php` | Command dan scheduler |
| `tests/Feature` | Pengujian fitur |

## Catatan keamanan repository

File berikut sengaja tidak dikirim ke GitHub:

- `.env`
- database SQLite lokal
- `vendor` dan `node_modules`
- log aplikasi
- e-book dan sampul yang diunggah pengguna

Gunakan `.env.example` sebagai templat konfigurasi pada setiap laptop.
