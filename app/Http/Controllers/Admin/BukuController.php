<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BukuController extends Controller
{
    // batas jumlah penulis dalam satu buku
    const MAKS_PENULIS = 10;

    // batas jumlah eksemplar satu buku
    const MAKS_STOK = 9999;

    // READ: daftar semua buku aktif (yang dihapus/nonaktif ada di tab Nonaktif)
    public function index(Request $request)
    {
        $query = Book::with(['category', 'authors', 'creator'])
            ->withCount(['loans as pinjaman_aktif' => fn ($q) => $q->whereIn('status', Loan::STATUS_AKTIF)])
            ->aktif();

        if ($request->q) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $daftar = $query->orderBy('title')->paginate(10)->withQueryString();

        return view('admin.buku.index', [
            'daftar' => $daftar,
            'jumlah' => self::jumlahTab(),
        ]);
    }

    // READ: tab Nonaktif, buku yang sudah dihapus dari katalog (masih tersimpan untuk riwayat)
    public function nonaktif(Request $request)
    {
        $query = Book::with(['category', 'authors', 'updater'])
            ->withCount([
                'loans as jumlah_pinjaman',
                'loans as pinjaman_aktif' => fn ($q) => $q->whereIn('status', Loan::STATUS_AKTIF),
                'reviews as jumlah_ulasan',
            ])
            ->where('is_active', 0);

        if ($request->q) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        // yang terakhir dinonaktifkan tampil paling atas
        $daftar = $query->orderByDesc('updated_at')->orderBy('title')->paginate(10)->withQueryString();

        return view('admin.buku.nonaktif', [
            'daftar' => $daftar,
            'jumlah' => self::jumlahTab(),
        ]);
    }

    // UPDATE: aktifkan kembali buku nonaktif agar tampil lagi di katalog
    public function aktifkan(Request $request, $id)
    {
        $buku = Book::where('is_active', 0)->findOrFail($id);

        $buku->is_active  = 1;
        $buku->updated_by = Auth::id();
        $buku->updated_at = now();
        $buku->save();

        AdminLog::catat('Aktifkan', 'books', $buku->book_id, 'Mengaktifkan kembali buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.nonaktif', $request->only('q'))
            ->with('status', 'Buku "' . $buku->title . '" aktif kembali dan tampil lagi di katalog.');
    }

    // DELETE (permanen): hanya untuk buku nonaktif yang belum pernah dipinjam maupun diulas,
    // misalnya buku yang salah input. Buku yang punya riwayat tetap disimpan sebagai nonaktif.
    public function hapusPermanen(Request $request, $id)
    {
        $buku = Book::where('is_active', 0)
            ->withCount(['loans as jumlah_pinjaman', 'reviews as jumlah_ulasan'])
            ->findOrFail($id);

        if ($buku->jumlah_pinjaman > 0 || $buku->jumlah_ulasan > 0) {
            return back()->with('error', 'Buku "' . $buku->title . '" tidak bisa dihapus permanen karena sudah punya riwayat ('
                . $buku->jumlah_pinjaman . ' peminjaman, ' . $buku->jumlah_ulasan . ' ulasan). Buku ini tetap disimpan sebagai nonaktif.');
        }

        $judul = $buku->title;
        $cover = $buku->cover_image;

        try {
            $buku->delete();
        } catch (QueryException $e) {
            // jaga-jaga bila ada data baru yang mengacu ke buku ini (foreign key menolak)
            return back()->with('error', 'Buku "' . $judul . '" tidak bisa dihapus permanen karena masih dipakai data lain.');
        }

        // file cover ikut dihapus, kecuali dipakai juga oleh buku lain
        if ($cover && !Book::where('cover_image', $cover)->exists()) {
            Storage::disk('public')->delete($cover);
        }

        AdminLog::catat('Hapus Permanen', 'books', $id, 'Menghapus permanen buku "' . $judul . '"');

        return redirect()->route('admin.buku.nonaktif', $request->only('q'))
            ->with('status', 'Buku "' . $judul . '" dihapus permanen.');
    }

    // jumlah data di tab Buku / Nonaktif / Kategori / Penulis
    public static function jumlahTab(): array
    {
        return [
            'buku'     => Book::aktif()->count(),
            'nonaktif' => Book::where('is_active', 0)->count(),
            'kategori' => Category::count(),
            'penulis'  => Author::count(),
        ];
    }

    public function create()
    {
        return view('admin.buku.create', [
            'kategori' => Category::orderBy('category_name')->get(),
            'penulis'  => Author::orderBy('author_name')->get(),
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $this->prosesKategoriBaru($request);
        $data = $this->validasi($request);
        $penulis = $this->penulisDipilih($request)['penulis'];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $data['available_stock'] = $data['stock'];
        $data['created_by']      = Auth::id();

        $buku = DB::transaction(function () use ($data, $penulis) {
            $buku = Book::create($data);
            $this->simpanPenulis($buku, $penulis);

            return $buku;
        });

        AdminLog::catat('Tambah', 'books', $buku->book_id, 'Menambahkan buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.index')->with('status', 'Buku berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $buku = Book::aktif()->with('authors')->findOrFail($id);

        return view('admin.buku.edit', [
            'buku'     => $buku,
            'kategori' => Category::orderBy('category_name')->get(),
            'penulis'  => Author::orderBy('author_name')->get(),
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $buku = Book::aktif()->findOrFail($id);
        $this->prosesKategoriBaru($request);
        $data = $this->validasi($request, $id);
        $penulis = $this->penulisDipilih($request)['penulis'];

        // eksemplar yang sedang di luar (sudah dikonfirmasi / sedang dipinjam) tidak boleh "hilang" dari stok
        $sedangDipinjam = max(0, $buku->stock - $buku->available_stock);
        if ($data['stock'] < $sedangDipinjam) {
            return back()->withInput()->withErrors([
                'stock' => 'Jumlah stok minimal ' . $sedangDipinjam . ' karena ' . $sedangDipinjam
                    . ' eksemplar sedang dipinjam atau menunggu diambil.',
            ]);
        }

        if ($request->hasFile('cover_image')) {
            if ($buku->cover_image) {
                Storage::disk('public')->delete($buku->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $data['available_stock'] = $data['stock'] - $sedangDipinjam;

        $data['updated_by'] = Auth::id();
        $data['updated_at'] = now();

        DB::transaction(function () use ($buku, $data, $penulis) {
            $buku->update($data);
            $this->simpanPenulis($buku, $penulis);
        });

        AdminLog::catat('Edit', 'books', $buku->book_id, 'Mengubah buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.index')->with('status', 'Buku berhasil diperbarui.');
    }

    // DELETE (soft): nonaktifkan, tidak dihapus permanen supaya riwayat aman
    public function destroy($id)
    {
        $buku = Book::aktif()->findOrFail($id);

        // buku yang masih diajukan / siap diambil / dipinjam tidak boleh hilang dari katalog
        $pinjamanAktif = $buku->loans()->whereIn('status', Loan::STATUS_AKTIF)->count();
        if ($pinjamanAktif > 0) {
            return back()->with('error', 'Buku "' . $buku->title . '" tidak bisa dihapus karena masih ada ' . $pinjamanAktif
                . ' peminjaman aktif (menunggu, siap diambil, atau sedang dipinjam). Selesaikan dulu peminjamannya.');
        }

        $buku->is_active  = 0;
        $buku->updated_by = Auth::id();
        $buku->updated_at = now();
        $buku->save();

        AdminLog::catat('Hapus', 'books', $buku->book_id, 'Menonaktifkan buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.index')
            ->with('status', 'Buku "' . $buku->title . '" dihapus dari katalog dan dipindah ke tab Nonaktif.');
    }

    // kategori baru hanya dibuat bila admin memilih opsi "+ Tambah kategori baru" (nilai "new")
    private function prosesKategoriBaru(Request $request)
    {
        if ($request->category_id === 'new') {
            $request->validate([
                'new_category_name' => 'required|string|max:100',
            ], [
                'new_category_name.required' => 'Nama kategori baru wajib diisi.',
                'new_category_name.max'      => 'Nama kategori baru maksimal 100 karakter.',
            ]);

            $kat = Category::firstOrCreate(['category_name' => trim($request->new_category_name)]);
            $request->merge(['category_id' => $kat->category_id]);
        }
    }

    // penulis dari form, berurutan: penulis[] berisi id penulis yang sudah ada atau "baru:Nama" untuk penulis baru,
    // ditambah kolom penulis_baru (dipakai bila JavaScript mati). Hasil: daftar penulis + pesan salah (bila ada)
    private function penulisDipilih(Request $request): array
    {
        $pilihan = collect((array) $request->input('penulis', []))
            ->map(fn ($nilai) => trim((string) $nilai))
            ->filter(fn ($nilai) => $nilai !== '');
        if (trim((string) $request->input('penulis_baru')) !== '') {
            $pilihan->push('baru:' . trim($request->input('penulis_baru')));
        }

        $penulis = [];
        foreach ($pilihan as $nilai) {
            if (ctype_digit($nilai)) {
                $penulis[] = ['id' => (int) $nilai];
            } elseif (str_starts_with($nilai, 'baru:') && ($nama = trim(substr($nilai, 5))) !== '') {
                if (mb_strlen($nama) > 100) {
                    return ['penulis' => [], 'salah' => 'Nama penulis baru maksimal 100 karakter.'];
                }
                $penulis[] = ['nama' => $nama];
            } else {
                return ['penulis' => [], 'salah' => 'Penulis tidak valid.'];
            }
        }

        if (! $penulis) {
            return ['penulis' => [], 'salah' => 'Penulis wajib dipilih.'];
        }
        if (count($penulis) > self::MAKS_PENULIS) {
            return ['penulis' => [], 'salah' => 'Penulis maksimal ' . self::MAKS_PENULIS . ' orang.'];
        }

        $ids = collect($penulis)->pluck('id')->filter()->unique();
        if ($ids->isNotEmpty() && Author::whereIn('author_id', $ids)->count() !== $ids->count()) {
            return ['penulis' => [], 'salah' => 'Penulis tidak ditemukan.'];
        }

        return ['penulis' => $penulis, 'salah' => null];
    }

    // simpan penulis buku sesuai urutan; penulis baru dibuat (atau dipakai bila namanya sudah ada)
    private function simpanPenulis(Book $buku, array $penulis): void
    {
        $urutan = [];
        foreach ($penulis as $p) {
            $id = $p['id'] ?? Author::firstOrCreate(['author_name' => $p['nama']])->author_id;
            // penulis yang sama dipilih dua kali cukup dicatat sekali
            if (! isset($urutan[$id])) {
                $urutan[$id] = ['urutan' => count($urutan) + 1];
            }
        }

        $buku->authors()->sync($urutan);
    }

    private function validasi(Request $request, $idBukuSaatIni = null)
    {
        return Validator::make($request->all(), [
            // setiap buku wajib punya kategori dan minimal satu penulis
            'category_id'      => 'required|exists:categories,category_id',
            'title'            => 'required|max:200',
            'publisher'        => 'nullable|max:100',
            // kolom YEAR di MySQL hanya menerima 1901-2155; stok dibatasi agar tidak melebihi kolom int
            'publication_year' => 'nullable|integer|min:1901|max:' . (date('Y') + 1),
            'description'      => 'nullable|max:2000',
            'location'         => 'nullable|max:100',
            'stock'            => 'required|integer|min:0|max:' . self::MAKS_STOK,
            'cover_image'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'integer'  => ':attribute harus berupa angka.',
            'min'      => ':attribute minimal :min.',
            'exists'   => ':attribute tidak ditemukan.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'publication_year.max' => 'Tahun terbit maksimal :max.',
            'stock.max'            => 'Jumlah stok maksimal :max.',
        ], [
            'category_id'      => 'Kategori',
            'title'            => 'Judul buku',
            'publisher'        => 'Penerbit',
            'publication_year' => 'Tahun terbit',
            'description'      => 'Deskripsi',
            'location'         => 'Lokasi rak',
            'stock'            => 'Jumlah stok',
            'cover_image'      => 'Cover buku',
        ])->after(function ($validator) use ($request) {
            // pesan penulis ikut tampil bersama pesan kolom lain
            $salah = $this->penulisDipilih($request)['salah'];
            if ($salah) {
                $validator->errors()->add('penulis', $salah);
            }
        })->validate();
    }
}
