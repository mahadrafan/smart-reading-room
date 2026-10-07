<?php

use App\Models\UserNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ---------- pengingat pengembalian lewat email (H-1 dan sekali saat terlambat) ----------
// jalankan manual: php artisan pengingat:email
Artisan::command('pengingat:email', function () {
    $hasil = UserNotification::kirimPengingatEmail();

    $this->info('Email H-1 terkirim: ' . $hasil['H-1']);
    $this->info('Email terlambat terkirim: ' . $hasil['Terlambat']);
    if ($hasil['gagal'] > 0) {
        $this->error('Gagal terkirim: ' . $hasil['gagal'] . ' (lihat storage/logs/laravel.log, dicoba lagi besok)');
    }
})->purpose('Kirim email pengingat H-1 batas kembali dan pengingat buku terlambat');

// otomatis setiap hari pukul 07.00 WIB; butuh "php artisan schedule:run" yang dipanggil cron / Task Scheduler
Schedule::command('pengingat:email')->dailyAt('07:00');
