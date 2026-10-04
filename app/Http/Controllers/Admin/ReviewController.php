<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    const PER_HALAMAN = 10;

    // daftar ulasan + pencarian, filter buku, dan filter rating (seperti halaman Review & Ulasan peminjam)
    public function index(Request $request)
    {
        // rating yang dicentang, 5 sampai 1 (nilai di luar 1-5 diabaikan)
        $bintang = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('bintang', [])),
            fn ($n) => $n >= 1 && $n <= 5
        )));
        rsort($bintang);
        $cari = trim((string) $request->q);

        // buku yang punya ulasan (termasuk buku nonaktif, karena ulasannya tetap tersimpan)
        $daftarBuku = Book::whereHas('reviews')
            ->withCount('reviews as jumlah_ulasan')
            ->orderBy('title')
            ->get(['book_id', 'title', 'is_active']);
        $bukuDipilih = $request->filled('buku') ? $daftarBuku->firstWhere('book_id', (int) $request->buku) : null;

        $dasar = Review::query()
            ->when($bukuDipilih, fn ($q) => $q->where('book_id', $bukuDipilih->book_id))
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('review_text', 'like', '%' . $cari . '%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $cari . '%'))
                        ->orWhereHas('book', fn ($b) => $b->where('title', 'like', '%' . $cari . '%'));
                });
            });

        // jumlah per rating untuk panel filter (mengikuti pencarian & buku, sebelum filter rating)
        $jumlahRating = (clone $dasar)->selectRaw('rating, COUNT(*) as jumlah')->groupBy('rating')->pluck('jumlah', 'rating');

        $reviews = (clone $dasar)
            ->when($bintang, fn ($q) => $q->whereIn('rating', $bintang))
            ->with(['user', 'book'])
            ->orderByDesc('created_at')
            ->orderByDesc('review_id')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        // setelah menghapus ulasan terakhir di halaman terakhir: pindah ke halaman sebelumnya
        if ($reviews->isEmpty() && $reviews->currentPage() > 1) {
            return redirect()->to($reviews->url($reviews->lastPage()));
        }

        return view('admin.reviews.index', [
            'reviews'      => $reviews,
            'daftarBuku'   => $daftarBuku,
            'bukuDipilih'  => $bukuDipilih,
            'bintang'      => $bintang,
            'cari'         => $cari,
            'jumlahRating' => $jumlahRating,
            'jumlahTotal'  => Review::count(),
            'filter'       => array_filter([
                'q'       => $cari,
                'buku'    => $bukuDipilih?->book_id,
                'bintang' => $bintang,
            ]),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $ulasan = Review::with(['user', 'book'])->findOrFail($id);
        $ulasan->delete();

        AdminLog::catat('Hapus', 'reviews', $ulasan->review_id,
            'Menghapus ulasan ' . $ulasan->rating . ' bintang oleh ' . ($ulasan->user->name ?? '-')
                . ' untuk buku "' . ($ulasan->book->title ?? '-') . '"');

        // kembali ke daftar dengan filter & halaman yang sama
        return redirect()->route('admin.reviews.index', $request->only(['q', 'buku', 'bintang', 'page']))
            ->with('status', 'Ulasan berhasil dihapus.');
    }
}
