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
use Illuminate\Support\Facades\Storage;

class BukuController extends Controller
{
    // READ: daftar semua buku aktif (yang dihapus/nonaktif ada di tab Nonaktif)
    public function index(Request $request)
    {
        $query = Book::with(['category', 'author', 'creator'])
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
        $query = Book::with(['category', 'author', 'updater'])
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
        $this->prosesKategoriPenulisBaru($request);
        $data = $this->validasi($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $data['available_stock'] = $data['stock'];
        $data['created_by']      = Auth::id();

        $buku = Book::create($data);

        AdminLog::catat('Tambah', 'books', $buku->book_id, 'Menambahkan buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.index')->with('status', 'Buku berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $buku = Book::aktif()->findOrFail($id);

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
        $this->prosesKategoriPenulisBaru($request);
        $data = $this->validasi($request, $id);

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

        $buku->update($data);

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

    // kategori/penulis baru hanya dibuat bila admin memilih opsi "+ Tambah ... baru" (nilai "new")
    private function prosesKategoriPenulisBaru(Request $request)
    {
        if ($request->category_id === 'new') {
            $request->validate([
                'new_category_name' => 'required|string|max:50',
            ], [
                'new_category_name.required' => 'Nama kategori baru wajib diisi.',
                'new_category_name.max'      => 'Nama kategori baru maksimal 50 karakter.',
            ]);

            $kat = Category::firstOrCreate(['category_name' => trim($request->new_category_name)]);
            $request->merge(['category_id' => $kat->category_id]);
        }

        if ($request->author_id === 'new') {
            $request->validate([
                'new_author_name' => 'required|string|max:100',
            ], [
                'new_author_name.required' => 'Nama penulis baru wajib diisi.',
                'new_author_name.max'      => 'Nama penulis baru maksimal 100 karakter.',
            ]);

            $pen = Author::firstOrCreate(['author_name' => trim($request->new_author_name)]);
            $request->merge(['author_id' => $pen->author_id]);
        }
    }

    private function validasi(Request $request, $idBukuSaatIni = null)
    {
        return $request->validate([
            // setiap buku wajib punya kategori dan penulis
            'category_id'      => 'required|exists:categories,category_id',
            'author_id'        => 'required|exists:authors,author_id',
            'title'            => 'required|max:200',
            'publisher'        => 'nullable|max:100',
            'publication_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'description'      => 'nullable|max:2000',
            'location'         => 'nullable|max:100',
            'stock'            => 'required|integer|min:0',
            'cover_image'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'integer'  => ':attribute harus berupa angka.',
            'min'      => ':attribute minimal :min.',
            'exists'   => ':attribute tidak ditemukan.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'author_id.required'   => 'Penulis wajib dipilih.',
        ], [
            'category_id'      => 'Kategori',
            'author_id'        => 'Penulis',
            'title'            => 'Judul buku',
            'publisher'        => 'Penerbit',
            'publication_year' => 'Tahun terbit',
            'description'      => 'Deskripsi',
            'location'         => 'Lokasi rak',
            'stock'            => 'Jumlah stok',
            'cover_image'      => 'Cover buku',
        ]);
    }
}
