<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KatalogController extends Controller
{
    // jumlah buku maksimal di satu rak; selebihnya lewat tautan "Lihat semua" atau halaman berikutnya
    const ISI_RAK = 6;

    // daftar buku (Semua Buku maupun hasil pencarian/filter) tampil 3 rak per halaman
    const RAK_PER_HALAMAN = 3;

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

        // "Semua Buku" hanya untuk koleksi tanpa filter; hasil pencarian/filter selalu tampil di Rak Buku
        if ($tampilan == 'semua' && $sedangMencari) {
            return redirect()->route('dashboard', $request->except(['tampilan', 'page']));
        }

        // semua buku aktif + hitung berapa kali sudah dipinjam, rata-rata rating & jumlah ulasan
        $query = Book::aktif()
            ->with(['category', 'authors'])
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }])
            ->withAvg('reviews as rata_rating', 'rating')
            ->withCount('reviews as jumlah_rating');

        // pencarian: judul atau nama penulis
        if ($kataKunci) {
            $query->where(function ($w) use ($kataKunci) {
                $w->where('title', 'like', '%' . $kataKunci . '%')
                  ->orWhereHas('authors', function ($a) use ($kataKunci) {
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
        $buku = $query->orderBy('title')->paginate(self::ISI_RAK * self::RAK_PER_HALAMAN)->withQueryString();

        // semua kategori (untuk panel filter) + jumlah buku aktifnya
        $kategori = Category::withCount(['books as jumlah_buku' => function ($q) {
            $q->where('is_active', 1);
        }])->orderBy('category_name')->get();

        // kategori tanpa buku aktif tidak ditampilkan ke peminjam (kecuali sedang dipilih lewat alamat halaman)
        $kategori = $kategori
            ->filter(fn ($k) => $k->jumlah_buku > 0 || in_array($k->category_id, $kategoriDipilih))
            ->values();

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
                ->with(['book.category', 'book.authors'])
                ->orderBy('due_date')
                ->get()
                ->filter(function ($p) {
                    return $p->book !== null;
                });

            // 2. buku yang paling sering dipinjam oleh semua user
            $populer = Book::aktif()
                ->with(['category', 'authors'])
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
                ->with(['category', 'authors'])
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
            'isiRak'          => self::ISI_RAK,
            'pinjamanAktif' => $pinjamanAktif,
            'populer'       => $populer,
            'topReview'       => $topReview,
            'jedaPinjam'      => Loan::jedaPinjamUlang(Auth::id()),
        ]);
    }

    // ---------------------------------------------------------
    // DETAIL BUKU
    // ---------------------------------------------------------
    public function show($id)
    {
        Loan::prosesTenggatPengambilan();

        $buku = Book::aktif()
            ->with(['category', 'authors', 'reviews.user'])
            ->withCount(['loans as jumlah_dipinjam' => function ($q) {
                $q->whereIn('status', ['Dikonfirmasi', 'Dipinjam', 'Dikembalikan']);
            }])
            ->findOrFail($id);

        // apakah user ini sudah mengajukan / sedang meminjam buku yang sama
        $sudahAjukan = Loan::where('user_id', Auth::id())
            ->where('book_id', $buku->book_id)
            ->whereIn('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam'])
            ->exists();

        // buku yang baru dikembalikan user ini: [book_id => tanggal boleh dipinjam lagi]
        $jedaPinjam = Loan::jedaPinjamUlang(Auth::id());

        // daftar peminjam aktif untuk buku ini (dikonfirmasi atau sedang dipinjam)
        $peminjamAktif = Loan::where('book_id', $buku->book_id)
            ->whereIn('status', ['Dikonfirmasi', 'Dipinjam'])
            ->with('user')
            ->orderBy('loan_id', 'desc')
            ->get();

        // buku lain di kategori yang sama
        $lainnya = Book::aktif()
            ->with(['category', 'authors'])
            ->where('category_id', $buku->category_id)
            ->where('book_id', '!=', $buku->book_id)
            ->limit(4)
            ->get();

        return view('user.detail', [
            'buku'          => $buku,
            'sudahAjukan'   => $sudahAjukan,
            // tanggal boleh meminjam lagi bila buku ini baru dikembalikan user (jeda pinjam ulang)
            'bolehPinjamLagi' => $jedaPinjam[$buku->book_id] ?? null,
            'jedaPinjam'    => $jedaPinjam,
            'peminjamAktif' => $peminjamAktif,
            'lainnya'       => $lainnya,
            'ulasanSaya'    => $buku->reviews->firstWhere('user_id', Auth::id()),
            'rataRating'     => round((float) $buku->reviews->avg('rating'), 1),
        ]);
    }
}
