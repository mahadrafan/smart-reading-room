<?php

namespace App\Providers;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // jumlah notifikasi baru yang ditampilkan di pop up (sisanya cukup disebut jumlahnya)
    const POPUP_MAKS = 5;

    public function boot(): void
    {
        // data notifikasi belum dibaca untuk setiap halaman peminjam: angka di menu Notifikasi + pop up
        View::composer('layouts.user', function ($view) {
            $belumDibaca = UserNotification::where('user_id', Auth::id())->belumDibaca();

            $jumlah = (clone $belumDibaca)->count();

            // pop up tidak perlu di halaman Notifikasi sendiri (di sana notifikasi baru sudah diberi tanda "Baru")
            $tampilPopup = $jumlah > 0 && ! request()->routeIs('notifikasi.index');

            $view->with([
                'jumlahNotifBaru' => $jumlah,
                'popupNotif'      => $tampilPopup
                    ? (clone $belumDibaca)->orderByDesc('notification_id')->limit(self::POPUP_MAKS)->get()
                    : collect(),
                // tombol "Mengerti" hanya menandai notifikasi sampai id ini (yang masuk setelahnya tetap baru)
                'popupSampai'     => $tampilPopup ? (clone $belumDibaca)->max('notification_id') : null,
            ]);
        });
    }
}
