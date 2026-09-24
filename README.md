# Smart Reading Room

Aplikasi perpustakaan berbasis Laravel 12 untuk katalog buku, peminjaman,
denda keterlambatan, ulasan, e-book, dan notifikasi pengguna.

## Kebutuhan

- PHP 8.2 atau lebih baru beserta ekstensi `fileinfo`, `mbstring`, `openssl`,
  `pdo_sqlite` atau `pdo_mysql`
- Composer 2
- Git
- Node.js dan npm hanya diperlukan jika aset Vite akan dikembangkan ulang

Laragon direkomendasikan untuk Windows karena sudah menyediakan PHP dan database.
Jangan gunakan PHP 8.0 dari XAMPP karena Laravel 12 membutuhkan PHP 8.2+.

## Instalasi cepat dengan SQLite

Cara ini paling mudah untuk menjalankan proyek di laptop baru.

```powershell
git clone URL_REPOSITORY smart-reading-room
cd smart-reading-room
composer install
Copy-Item .env.example .env
New-Item database/database.sqlite -ItemType File -Force
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Akun awal dari seeder:

| Role | NIS/NIP | Password |
| --- | --- | --- |
| Admin | `ADMIN001` | `admin12345` |
| Peminjam | `2026001` | `peminjam123` |

## Instalasi dengan MySQL

Buat database kosong bernama `basdat`, lalu ubah bagian database pada `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basdat
DB_USERNAME=root
DB_PASSWORD=
```

Setelah itu jalankan:

```powershell
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Untuk memindahkan seluruh data dari laptop lama, ekspor database lama terlebih
dahulu dan impor ke database `basdat` di laptop baru. Sesudah impor, jalankan
`php artisan migrate --force` agar perubahan skema terbaru ikut diterapkan.

## E-book dan sampul

File unggahan tidak disimpan di Git. Jika ingin mempertahankan e-book dan sampul
dari laptop lama, salin isi folder berikut ke lokasi yang sama di laptop baru:

- `storage/app/private/ebooks`
- `storage/app/public/covers`

Kemudian jalankan kembali `php artisan storage:link`.

## Notifikasi dan scheduler

Konfigurasi bawaan menulis email ke `storage/logs/laravel.log`. Untuk mengirim
email sungguhan, isi konfigurasi SMTP pada `.env`.

Jalankan scheduler pada terminal kedua agar pengingat keterlambatan diproses
setiap hari:

```powershell
php artisan schedule:work
```

## Pengujian

```powershell
php artisan test
```

Pada Windows, pastikan perintah `php -v` menampilkan PHP 8.2 atau lebih baru.
Jika beberapa instalasi PHP tersedia, gunakan executable PHP dari Laragon.
