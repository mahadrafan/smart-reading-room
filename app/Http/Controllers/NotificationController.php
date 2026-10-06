<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // filter jenis notifikasi yang selalu tampil (urut sesuai alur peminjaman)
    const JENIS_UTAMA = ['Pengajuan', 'Dikonfirmasi', 'Dipinjam', 'Dikembalikan', 'Gagal'];

    public function index(Request $request)
    {
        // proses tenggat pengambilan dulu agar notifikasi "gagal otomatis" langsung tampil di sini
        Loan::prosesTenggatPengambilan();

        $milikSaya = UserNotification::where('user_id', Auth::id());

        // jumlah notifikasi per jenis, selalu dihitung dari semua notifikasi (bukan dari filter yang dibuka)
        $perJenis = (clone $milikSaya)
            ->selectRaw('type, COUNT(*) as jumlah')
            ->groupBy('type')
            ->pluck('jumlah', 'type');

        // filter utama selalu ada; jenis lain (misal Terlambat dari data lama, Pengumuman) hanya bila ada isinya
        $jumlahJenis = collect(self::JENIS_UTAMA)
            ->merge($perJenis->keys()->filter()->diff(self::JENIS_UTAMA)->sort())
            ->mapWithKeys(fn ($jenis) => [$jenis => (int) ($perJenis[$jenis] ?? 0)]);

        // ?jenis= yang tidak dikenal dianggap "Semua"
        $jenisAktif = $jumlahJenis->keys()
            ->first(fn ($jenis) => strtolower($jenis) === strtolower((string) $request->jenis));

        $query = (clone $milikSaya)->with('loan.book')->orderByDesc('notification_id');
        if ($jenisAktif) {
            $query->where('type', $jenisAktif);
        }
        $notifications = $query->get();

        // notifikasi belum dibaca yang tampil di sini diberi tanda "Baru", lalu dianggap sudah dibaca
        // (yang tersembunyi oleh filter jenis tetap belum dibaca dan masih muncul di pop up)
        $idBaru = $notifications->where('status', UserNotification::BELUM_DIBACA)->pluck('notification_id')->all();
        if ($idBaru) {
            UserNotification::whereIn('notification_id', $idBaru)->update(['status' => UserNotification::DIBACA]);
        }

        return view('user.notifications', [
            'notifications' => $notifications,
            'idBaru'        => $idBaru,
            'jenisAktif'    => $jenisAktif,
            'jumlahJenis'   => $jumlahJenis,
            'jumlahSemua'   => (int) $perJenis->sum(),
        ]);
    }

    // tombol "Mengerti" di pop up notifikasi baru
    public function tandaiDibaca(Request $request)
    {
        $request->validate(['sampai' => 'nullable|integer']);

        UserNotification::tandaiDibaca(Auth::id(), $request->integer('sampai') ?: null);

        // ditutup lewat JavaScript: cukup jawab tanpa isi; tanpa JavaScript: kembali ke halaman sebelumnya
        return $request->expectsJson() ? response()->noContent() : back();
    }
}
