<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // semua notifikasi selain Pengumuman termasuk tab Peminjaman (Pengajuan, Dikonfirmasi, Dipinjam,
        // Dikembalikan, Gagal, Terlambat, ...), jadi tipe baru otomatis ikut tanpa perlu didaftarkan
        $tabPeminjaman = fn ($q) => $q->where(fn ($w) => $w->whereNull('type')->orWhere('type', '!=', 'Pengumuman'));
        $tabPengumuman = fn ($q) => $q->where('type', 'Pengumuman');

        $milikSaya = UserNotification::where('user_id', Auth::id());

        // angka di setiap tab selalu dihitung dari semua notifikasi, bukan dari tab yang sedang dibuka
        $jumlahTab = [
            'semua'      => (clone $milikSaya)->count(),
            'peminjaman' => $tabPeminjaman(clone $milikSaya)->count(),
            'pengumuman' => $tabPengumuman(clone $milikSaya)->count(),
        ];

        $tab = in_array($request->tab, ['peminjaman', 'pengumuman'], true) ? $request->tab : 'semua';

        $query = (clone $milikSaya)->with('loan.book')->orderByDesc('notification_id');
        if ($tab == 'peminjaman') {
            $tabPeminjaman($query);
        } elseif ($tab == 'pengumuman') {
            $tabPengumuman($query);
        }

        return view('user.notifications', [
            'notifications' => $query->get(),
            'tabAktif'      => $tab,
            'jumlahTab'     => $jumlahTab,
        ]);
    }
}
