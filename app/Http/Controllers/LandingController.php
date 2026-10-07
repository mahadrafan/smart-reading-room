<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;

// halaman perkenalan (introduction) untuk tamu; tombol & tautannya mengarah ke halaman login / daftar
class LandingController extends Controller
{
    // "Paling Sering Dipinjam": 5 buku per halaman, digeser dengan tombol < > sampai 3 halaman
    const POPULER_PER_HALAMAN = 5;
    const HALAMAN_POPULER = 3;

    public function index()
    {
        // buku paling sering dipinjam (seri -> terbaru), dipakai untuk hero dan rak "Paling Sering Dipinjam"
        $populer = Book::aktif()
            ->with('authors')
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }])
            ->orderByDesc('jumlah_dipinjam')
            ->orderByDesc('created_at')
            ->limit(self::POPULER_PER_HALAMAN * self::HALAMAN_POPULER)
            ->get();

        // tumpukan sampul di hero: tiga buku populer yang punya file sampul (bisa diklik untuk ganti buku di depan)
        $sorotan = $populer->filter(fn ($b) => $b->cover_url)->take(3)->values();

        // empat buku terbaru bersampul untuk bagian "Temukan buku favoritmu"
        $terbaru = Book::aktif()
            ->whereNotNull('cover_image')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->filter(fn ($b) => $b->cover_url)
            ->take(4)
            ->values();

        return view('landing', [
            'populer'  => $populer->chunk(self::POPULER_PER_HALAMAN),
            'sorotan'  => $sorotan,
            'terbaru'  => $terbaru,
            'statistik' => [
                'buku'     => Book::aktif()->count(),
                'kategori' => Category::whereHas('books', fn ($q) => $q->where('is_active', 1))->count(),
                'anggota'  => User::where('role', 'Peminjam')->count(),
            ],
            'aturan' => [
                'batasAktif'  => PeminjamanController::BATAS_AKTIF,
                'maksDurasi'  => PeminjamanController::MAKS_DURASI,
                'dendaPerHari' => config('app.fine_per_day'),
            ],
        ]);
    }
}
