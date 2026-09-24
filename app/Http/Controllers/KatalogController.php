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

        // bagian "Paling Sering Dipinjam" (hanya tampil kalau tidak sedang mencari)
        $populer = collect();
        if (!$kataKunci && !$kategoriDipilih) {
            $populer = Book::aktif()
                ->with(['category', 'author'])
                ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                    $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
                }])
                ->orderByDesc('jumlah_dipinjam')
                ->limit(4)
                ->get()
                ->filter(function ($b) {
                    return $b->jumlah_dipinjam > 0;
                });
        }

        $kategori = Category::orderBy('category_name')->get();

        // ringkasan peminjaman milik user yang sedang login
        $idUser = Auth::id();
        $menunggu     = Loan::where('user_id', $idUser)->where('status', 'Menunggu')->count();
        $dikonfirmasi = Loan::where('user_id', $idUser)->where('status', 'Dikonfirmasi')->count();
        $dipinjam     = Loan::where('user_id', $idUser)->where('status', 'Dipinjam')->count();
        $terlambat    = Loan::where('user_id', $idUser)
            ->where('status', 'Dipinjam')
            ->where('due_date', '<', today()->format('Y-m-d'))
            ->count();

        return view('user.katalog', [
            'buku'         => $buku,
            'populer'      => $populer,
            'kategori'     => $kategori,
            'menunggu'     => $menunggu,
            'dikonfirmasi' => $dikonfirmasi,
            'dipinjam'     => $dipinjam,
            'terlambat'    => $terlambat,
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
