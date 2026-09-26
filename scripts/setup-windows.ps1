$ErrorActionPreference = 'Stop'

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP tidak ditemukan di PATH. Pasang PHP 8.2+ atau aktifkan PHP Laragon.'
}

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    throw 'Composer tidak ditemukan di PATH.'
}

$phpVersion = php -r "echo PHP_VERSION_ID;"
if ([int]$phpVersion -lt 80200) {
    throw 'Laravel 12 membutuhkan PHP 8.2 atau lebih baru.'
}

composer install

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
}

if (-not (Test-Path 'database/database.sqlite')) {
    New-Item 'database/database.sqlite' -ItemType File | Out-Null
}

php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear

Write-Host ''
Write-Host 'Setup selesai. Jalankan: php artisan serve' -ForegroundColor Green
Write-Host 'Admin: ADM001 / admin12345'
Write-Host 'Peminjam: 2024001 / peminjam123'
