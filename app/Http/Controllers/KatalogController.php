<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogController extends Controller
{
    // jumlah buku maksimal di rak utama; selebihnya lewat tautan "Lihat semua"
    const ISI_RAK = 6;

    // ---------------------------------------------------------
    // LANDING PAGE peminjam setelah login: katalog buku
    // READ: books, categories, authors, loans
    // ---------------------------------------------------------
    public function index(Request $request)
    {
        Loan::prosesTenggatPengambilan();

        $kataKunci = $request->q;

        // filter kategori bisa lebih dari satu (?kategori[]=1&kategori[]=3); ?kategori=1 tetap didukung
        $kategoriDipilih = collect((array) $request->input('kategori', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();

        // urutan hasil & filter ketersediaan dari panel "Filter"
        $daftarUrut = [
            'judul'   => 'Judul A–Z',
            'populer' => 'Paling sering dipinjam',
            'rating'  => 'Top review',
            'terbaru' => 'Terbaru ditambahkan',
        ];
        $urut = array_key_exists($request->urut, $daftarUrut) ? $request->urut : 'judul';
        $hanyaTersedia = $request->boolean('tersedia');

        // tampilan katalog: "rak" (default, buku berjajar di rak) atau "semua" (grid kartu)
        $tampilan = $request->tampilan === 'semua' ? 'semua' : 'rak';
        $sedangMencari = $kataKunci || $kategoriDipilih || $urut !== 'judul' || $hanyaTersedia;

        // semua buku aktif + hitung berapa kali sudah dipinjam, rata-rata rating & jumlah ulasan
        $query = Book::aktif()
            ->with(['category', 'author'])
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }])
            ->withAvg('reviews as rata_rating', 'rating')
            ->withCount('reviews as jumlah_rating');

        // pencarian: judul atau nama penulis
        if ($kataKunci) {
            $query->where(function ($w) use ($kataKunci) {
                $w->where('title', 'like', '%' . $kataKunci . '%')
                  ->orWhereHas('author', function ($a) use ($kataKunci) {
                      $a->where('author_name', 'like', '%' . $kataKunci . '%');
                  });
            });
        }

        // filter kategori (satu atau beberapa)
        if ($kategoriDipilih) {
            $query->whereIn('category_id', $kategoriDipilih);
        }

        if ($hanyaTersedia) {
            $query->where('available_stock', '>', 0);
        }

        // urutan hasil; judul jadi urutan kedua agar hasil yang angkanya sama tetap rapi
        if ($urut === 'populer') {
            $query->orderByDesc('jumlah_dipinjam');
        } elseif ($urut === 'rating') {
            // buku tanpa ulasan (rata-rata NULL) berada di urutan paling bawah
            $query->orderByDesc('rata_rating')->orderByDesc('jumlah_rating');
        } elseif ($urut === 'terbaru') {
            $query->orderByDesc('created_at')->orderByDesc('book_id');
        }
        $buku = $query->orderBy('title')->paginate(12)->withQueryString();

        // semua kategori (untuk panel filter) + jumlah buku aktifnya
        $kategori = Category::withCount(['books as jumlah_buku' => function ($q) {
            $q->where('is_active', 1);
        }])->orderBy('category_name')->get();

        // tombol cepat: 5 kategori dengan buku terbanyak, ditambah kategori terpilih yang tidak masuk 5 besar
        $kategoriCepat = $kategori->sortByDesc('jumlah_buku')->take(5);
        $kategoriCepat = $kategoriCepat
            ->merge($kategori->whereIn('category_id', $kategoriDipilih)->whereNotIn('category_id', $kategoriCepat->pluck('category_id')))
            ->values();

        // tiga rak di tampilan utama (hanya saat tidak sedang mencari / memfilter)
        $pinjamanAktif = collect();
        $populer = collect();
        $topReview = collect();
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
                ->limit(self::ISI_RAK)
                ->get()
                ->filter(function ($b) {
                    return $b->jumlah_dipinjam > 0;
                });

            // 3. top review: rating rata-rata tertinggi (seri -> ulasan terbanyak), hanya buku yang sudah diulas
            $topReview = Book::aktif()
                ->with(['category', 'author'])
                ->whereHas('reviews')
                ->withAvg('reviews as rata_rating', 'rating')
                ->withCount('reviews as jumlah_rating')
                ->orderByDesc('rata_rating')
                ->orderByDesc('jumlah_rating')
                ->orderBy('title')
                ->limit(self::ISI_RAK)
                ->get();
        }

        return view('user.katalog', [
            'buku'            => $buku,
            'kategori'        => $kategori,
            'kategoriCepat'   => $kategoriCepat,
            'kategoriDipilih' => $kategoriDipilih,
            'daftarUrut'      => $daftarUrut,
            'urut'            => $urut,
            'hanyaTersedia'   => $hanyaTersedia,
            'sedangMencari'   => $sedangMencari,
            'tampilan'        => $tampilan,
            'pinjamanAktif' => $pinjamanAktif,
            'populer'       => $populer,
            'topReview'       => $topReview,
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
