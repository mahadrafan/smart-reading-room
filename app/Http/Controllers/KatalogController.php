<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogController extends Controller
{
    // ---------------------------------------------------------
    // LANDING PAGE peminjam setelah login: katalog buku
    // READ: books, categories, authors, loans
    // ---------------------------------------------------------
    public function index(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $kataKunci = $request->q;
        $kategoriDipilih = $request->kategori;

        // tampilan katalog: "rak" (default, buku berjajar di rak) atau "semua" (grid kartu)
        $tampilan = $request->tampilan === 'semua' ? 'semua' : 'rak';
        $sedangMencari = $kataKunci || $kategoriDipilih;

        // semua buku aktif + hitung berapa kali sudah dipinjam
        $query = Book::aktif()
            ->with(['category', 'author'])
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }]);

        // pencarian: judul atau nama penulis
        if ($kataKunci) {
            $query->where(function ($w) use ($kataKunci) {
                $w->where('title', 'like', '%' . $kataKunci . '%')
                  ->orWhereHas('author', function ($a) use ($kataKunci) {
                      $a->where('author_name', 'like', '%' . $kataKunci . '%');
                  });
            });
        }

        // filter kategori
        if ($kategoriDipilih) {
            $query->where('category_id', $kategoriDipilih);
        }

        $buku = $query->orderBy('title')->paginate(12)->withQueryString();

        $kategori = Category::orderBy('category_name')->get();

        // tiga rak di tampilan utama (hanya saat tidak sedang mencari / memfilter)
        $pinjamanAktif = collect();
        $populer = collect();
        $banyakUlasan = collect();
        if ($tampilan == 'rak' && !$sedangMencari) {
            // 1. buku yang sedang dipinjam user ini
            $pinjamanAktif = Loan::where('user_id', Auth::id())
                ->where('status', 'Dipinjam')
                ->with(['book.category', 'book.author'])
                ->orderBy('due_date')
                ->get()
                ->filter(function ($p) {
                    return $p->book !== null;
                });

            // 2. buku yang paling sering dipinjam oleh semua user
            $populer = Book::aktif()
                ->with(['category', 'author'])
                ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                    $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
                }])
                ->orderByDesc('jumlah_dipinjam')
                ->orderBy('title')
                ->limit(8)
                ->get()
                ->filter(function ($b) {
                    return $b->jumlah_dipinjam > 0;
                });

            // 3. buku dengan komentar ulasan terbanyak (ulasan yang hanya berisi rating tidak dihitung)
            $banyakUlasan = Book::aktif()
                ->with(['category', 'author'])
                ->withCount(['reviews as jumlah_ulasan' => function ($q) {
                    $q->whereNotNull('review_text')->where('review_text', '!=', '');
                }])
                ->orderByDesc('jumlah_ulasan')
                ->orderBy('title')
                ->limit(8)
                ->get()
                ->filter(function ($b) {
                    return $b->jumlah_ulasan > 0;
                });
        }

        return view('user.katalog', [
            'buku'          => $buku,
            'kategori'      => $kategori,
            'tampilan'      => $tampilan,
            'pinjamanAktif' => $pinjamanAktif,
            'populer'       => $populer,
            'banyakUlasan'  => $banyakUlasan,
        ]);
    }

    // ---------------------------------------------------------
    // DETAIL BUKU
    // ---------------------------------------------------------
    public function show($id)
    {
        Loan::prosesTenggatPengambilan();

        $buku = Book::aktif()
            ->with(['category', 'author', 'reviews.user'])
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }])
            ->findOrFail($id);

        // apakah user ini sudah mengajukan / sedang meminjam buku yang sama
        $sudahAjukan = Loan::where('user_id', Auth::id())
            ->where('book_id', $buku->book_id)
            ->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam'])
            ->exists();

        // daftar peminjam aktif untuk buku ini (dikonfirmasi atau sedang dipinjam)
        $peminjamAktif = Loan::where('book_id', $buku->book_id)
            ->whereIn('status', ['Dikonfirmasi', 'Dipinjam'])
            ->with('user')
            ->orderBy('loan_id', 'desc')
            ->get();

        // buku lain di kategori yang sama
        $lainnya = Book::aktif()
            ->with(['category', 'author'])
            ->where('category_id', $buku->category_id)
            ->where('book_id', '!=', $buku->book_id)
            ->limit(4)
            ->get();

        return view('user.detail', [
            'buku'          => $buku,
            'sudahAjukan'   => $sudahAjukan,
            'peminjamAktif' => $peminjamAktif,
            'lainnya'       => $lainnya,
            'ulasanSaya'    => $buku->reviews->firstWhere('user_id', Auth::id()),
            'rataRating'     => round((float) $buku->reviews->avg('rating'), 1),
        ]);
    }
}
