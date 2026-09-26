#!/usr/bin/env bash
set -euo pipefail

command -v php >/dev/null 2>&1 || { echo 'PHP tidak ditemukan. Pasang PHP 8.2+.'; exit 1; }
command -v composer >/dev/null 2>&1 || { echo 'Composer tidak ditemukan.'; exit 1; }

php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || {
  echo 'Laravel 12 membutuhkan PHP 8.2 atau lebih baru.'
  exit 1
}

composer install
[ -f .env ] || cp .env.example .env
[ -f database/database.sqlite ] || touch database/database.sqlite

php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear

echo
echo 'Setup selesai. Jalankan: php artisan serve'
echo 'Admin: ADM001 / admin12345'
echo 'Peminjam: 2024001 / peminjam123'
