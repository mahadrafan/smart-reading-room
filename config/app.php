<?php

// hanya pengaturan yang berbeda dari bawaan Laravel; sisanya (name, env, debug, url, key, locale, ...)
// otomatis diambil dari vendor/laravel/framework/config/app.php dan tetap dibaca dari .env
return [

    // zona waktu WIB: pergantian hari (tanggal pengajuan, batas kembali, denda) mengikuti jam Indonesia
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    // denda keterlambatan per hari (Rp)
    'fine_per_day' => (int) env('LIBRARY_FINE_PER_DAY', 1000),

];
