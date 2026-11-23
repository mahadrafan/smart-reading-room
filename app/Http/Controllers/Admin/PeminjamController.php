<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PeminjamController extends Controller
{
    const PER_HALAMAN = 10;

    // daftar peminjam (siswa) + pencarian NIS, nama, atau kelas; 10 per halaman
    public function index(Request $request)
    {
        $cari = trim((string) $request->q);

        $peminjam = User::where('role', 'Peminjam')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('nim_nip', 'like', '%' . $cari . '%')
                        ->orWhere('name', 'like', '%' . $cari . '%')
                        ->orWhere('class', 'like', '%' . $cari . '%');
                });
            })
            ->withCount([
                'loans as pinjaman_aktif' => fn ($query) => $query->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam']),
                'loans as pinjaman_terlambat' => fn ($query) => $query->where('status', 'Dipinjam')->whereDate('due_date', '<', today()),
            ])
            ->orderBy('name')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        // nomor halaman di luar jangkauan (mis. dari alamat lama): pindah ke halaman terakhir
        if ($peminjam->isEmpty() && $peminjam->currentPage() > 1) {
            return redirect()->to($peminjam->url($peminjam->lastPage()));
        }

        return view('admin.peminjam.index', [
            'peminjam'    => $peminjam,
            'cari'        => $cari,
            'jumlahTotal' => User::where('role', 'Peminjam')->count(),
        ]);
    }
}
