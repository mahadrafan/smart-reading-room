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

        // bagian "Paling Sering Dipinjam" (hanya tampil kalau tidak sedang mencari)
        $populer = collect();
        if (!$sedangMencari) {
            $populer = Book::aktif()
                ->with(['category', 'author'])
                ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                    $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
                }])
                ->orderByDesc('jumlah_dipinjam')
                ->limit(8)
                ->get()
                ->filter(function ($b) {
                    return $b->jumlah_dipinjam > 0;
                });
        }

        $kategori = Category::orderBy('category_name')->get();
        $idUser = Auth::id();

        // data khusus tampilan rak (hanya saat tidak sedang mencari / memfilter)
        $pinjamanAktif = collect();
        $rakKategori = collect();
        if ($tampilan == 'rak' && !$sedangMencari) {
            // rak "sedang kamu pinjam": pengajuan yang masih berjalan
            $pinjamanAktif = Loan::where('user_id', $idUser)
                ->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam'])
                ->with(['book.category', 'book.author'])
                ->orderByDesc('loan_id')
                ->get()
                ->filter(function ($p) {
                    return $p->book !== null;
                });

            // satu rak untuk setiap kategori yang punya buku aktif
            $bukuPerKategori = Book::aktif()
                ->with(['category', 'author'])
                ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                    $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
                }])
                ->orderBy('title')
                ->get()
                ->groupBy('category_id');

            $rakKategori = $kategori
                ->filter(function ($k) use ($bukuPerKategori) {
                    return $bukuPerKategori->has($k->category_id);
                })
                ->map(function ($k) use ($bukuPerKategori) {
                    return [
                        'kategori' => $k,
                        'buku'     => $bukuPerKategori[$k->category_id]->take(10),
                        'total'    => $bukuPerKategori[$k->category_id]->count(),
                    ];
                });
        }

        // ringkasan peminjaman milik user yang sedang login
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
            'tampilan'     => $tampilan,
            'pinjamanAktif' => $pinjamanAktif,
            'rakKategori'  => $rakKategori,
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
