<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // jumlah ulasan per halaman; halaman berikutnya dibuka lewat tombol ‹ ›
    const PER_HALAMAN = 5;

    // halaman Review & Ulasan: semua ulasan dari setiap buku, bisa disaring per buku / ulasan saya
    public function index(Request $request)
    {
        // filter rating: boleh lebih dari satu (kotak centang 5 bintang sampai 1 bintang), kosong = semua rating
        // nama parameternya "bintang" agar tidak bentrok dengan isian rating di form edit ulasan
        $bintang = $this->bintangDipilih($request);
        $hanyaSaya = $request->boolean('saya');
        $cari = trim((string) $request->q);

        // buku yang sudah punya ulasan, untuk pilihan filter
        $daftarBuku = Book::aktif()
            ->whereHas('reviews')
            ->withCount('reviews as jumlah_rating')
            ->orderBy('title')
            ->get(['book_id', 'title']);

        $bukuDipilih = $request->filled('buku')
            ? Book::aktif()
                ->with('authors')
                ->withAvg('reviews as rata_rating', 'rating')
                ->withCount('reviews as jumlah_rating')
                ->find($request->buku)
            : null;

        // hanya ulasan untuk buku yang masih aktif di katalog
        $dasar = Review::whereHas('book', fn ($q) => $q->aktif())
            ->when($bukuDipilih, fn ($q) => $q->where('book_id', $bukuDipilih->book_id));

        // jumlah per rating untuk pilihan filter (mengikuti filter buku & tab ulasan saya)
        $jumlahRating = (clone $dasar)
            ->when($hanyaSaya, fn ($q) => $q->where('user_id', Auth::id()))
            ->selectRaw('rating, COUNT(*) as jumlah')
            ->groupBy('rating')
            ->pluck('jumlah', 'rating');

        $dasar->when($bintang, fn ($q) => $q->whereIn('rating', $bintang));

        $jumlahSemua = (clone $dasar)->count();
        $jumlahSaya = (clone $dasar)->where('user_id', Auth::id())->count();

        $query = (clone $dasar)->with(['user', 'book.authors']);

        if ($hanyaSaya) {
            $query->where('user_id', Auth::id());
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('review_text', 'like', '%' . $cari . '%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $cari . '%'))
                    ->orWhereHas('book', fn ($b) => $b->where('title', 'like', '%' . $cari . '%'));
            });
        }

        $semuaUlasan = $query->orderByDesc('created_at')->orderByDesc('review_id')->get();

        // ulasan yang dipilih (ditampilkan di panel aksi); setelah gagal validasi, kembali ke ulasan yang diedit
        $idDipilih = (int) old('ulasan_id', $request->ulasan);
        $posisi = $idDipilih
            ? $semuaUlasan->search(fn ($u) => (int) $u->review_id === $idDipilih)
            : false;

        // per halaman 5 ulasan; ulasan yang dipilih (mis. dari tombol Edit Ulasan) selalu dibuka di halamannya
        $jumlahHalaman = max(1, (int) ceil($semuaUlasan->count() / self::PER_HALAMAN));
        $halaman = $posisi !== false
            ? intdiv($posisi, self::PER_HALAMAN) + 1
            : (int) $request->halaman;
        $halaman = min(max($halaman, 1), $jumlahHalaman);
        $daftarUlasan = $semuaUlasan->forPage($halaman, self::PER_HALAMAN)->values();

        $dipilih = $daftarUlasan->firstWhere('review_id', $idDipilih) ?? $daftarUlasan->first();
        $modeEdit = $dipilih
            && $dipilih->user_id == Auth::id()
            && ($request->boolean('edit') || (int) old('ulasan_id') === (int) $dipilih->review_id);

        return view('user.ulasan', [
            'daftarUlasan' => $daftarUlasan,
            'daftarBuku'   => $daftarBuku,
            'bukuDipilih'  => $bukuDipilih,
            'bintang'      => $bintang,
            'jumlahRating' => $jumlahRating,
            'hanyaSaya'    => $hanyaSaya,
            'cari'         => $cari,
            'jumlahSemua'  => $jumlahSemua,
            'jumlahSaya'   => $jumlahSaya,
            'dipilih'      => $dipilih,
            'modeEdit'     => $modeEdit,
            'halaman'       => $halaman,
            'jumlahHalaman' => $jumlahHalaman,
            'jumlahUlasan'  => $semuaUlasan->count(),
            'awalHalaman'   => ($halaman - 1) * self::PER_HALAMAN + 1,
            // nomor halaman tidak ikut di filter: ganti tab / filter selalu kembali ke halaman 1
            'filter'        => collect($this->filterHalaman($request))->except('halaman')->all(),
        ]);
    }

    // ulasan baru dari halaman detail buku; satu user hanya boleh satu ulasan per buku
    public function store(Request $request, $bookId)
    {
        $book = Book::aktif()->findOrFail($bookId);

        $sudahAda = Review::where('user_id', Auth::id())->where('book_id', $book->book_id)->first();
        if ($sudahAda) {
            return redirect()
                ->to(route('ulasan.index', ['ulasan' => $sudahAda->review_id, 'edit' => 1]) . '#ulasan-' . $sudahAda->review_id)
                ->withErrors(['review_text' => 'Kamu sudah pernah memberi ulasan untuk buku ini. Silakan ubah ulasanmu di sini.']);
        }

        Review::create($this->validasi($request) + [
            'user_id' => Auth::id(),
            'book_id' => $book->book_id,
        ]);

        return back()->with('status', 'Ulasan dan rating berhasil disimpan.');
    }

    // edit ulasan milik sendiri
    public function update(Request $request, $id)
    {
        $ulasan = Review::where('user_id', Auth::id())->findOrFail($id);
        $ulasan->update($this->validasi($request));

        return redirect()
            ->to(route('ulasan.index', $this->filterHalaman($request) + ['ulasan' => $ulasan->review_id]) . '#ulasan-' . $ulasan->review_id)
            ->with('status', 'Ulasan berhasil diperbarui.');
    }

    // hapus ulasan milik sendiri; setelah itu user boleh menulis ulasan baru untuk buku tersebut
    public function destroy(Request $request, $id)
    {
        $ulasan = Review::where('user_id', Auth::id())->findOrFail($id);
        $ulasan->delete();

        return redirect()
            ->route('ulasan.index', $this->filterHalaman($request))
            ->with('status', 'Ulasan berhasil dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_text' => ['required', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Rating wajib dipilih.',
            'rating.between' => 'Rating harus antara 1 sampai 5.',
            'review_text.required' => 'Komentar ulasan wajib diisi.',
            'review_text.max' => 'Komentar ulasan maksimal 1000 karakter.',
        ]);
    }

    // rating yang dicentang, urut dari 5 ke 1 (nilai di luar 1-5 diabaikan)
    private function bintangDipilih(Request $request): array
    {
        $bintang = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('bintang', [])),
            fn ($n) => $n >= 1 && $n <= 5
        )));
        rsort($bintang);

        return $bintang;
    }

    // filter halaman Review & Ulasan yang dibawa kembali setelah edit / hapus
    private function filterHalaman(Request $request): array
    {
        $filter = array_filter(
            $request->only(['buku', 'saya', 'q', 'halaman']),
            fn ($nilai) => is_string($nilai) && $nilai !== ''
        );

        if ($bintang = $this->bintangDipilih($request)) {
            $filter['bintang'] = $bintang;
        }

        return $filter;
    }
}
