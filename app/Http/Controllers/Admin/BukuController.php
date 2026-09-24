<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BukuController extends Controller
{
    // READ: daftar semua buku aktif (yang dihapus/nonaktif disembunyikan)
    public function index(Request $request)
    {
        $query = Book::with(['category', 'author'])->aktif();

        if ($request->q) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $daftar = $query->orderBy('title')->paginate(10)->withQueryString();

        return view('admin.buku.index', ['daftar' => $daftar]);
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

        if ($request->hasFile('cover_image')) {
            if ($buku->cover_image) {
                Storage::disk('public')->delete($buku->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $sedangDipinjam = $buku->stock - $buku->available_stock;
        $data['available_stock'] = max(0, $data['stock'] - $sedangDipinjam);

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

        $buku->is_active  = 0;
        $buku->updated_by = Auth::id();
        $buku->updated_at = now();
        $buku->save();

        AdminLog::catat('Hapus', 'books', $buku->book_id, 'Menonaktifkan buku "' . $buku->title . '"');

        return redirect()->route('admin.buku.index')->with('status', 'Buku berhasil dihapus dari katalog.');
    }

    private function prosesKategoriPenulisBaru(Request $request)
    {
        if ($request->category_id === 'new' || $request->filled('new_category_name')) {
            $request->validate([
                'new_category_name' => 'required|string|max:50',
            ], [
                'new_category_name.required' => 'Nama kategori baru wajib diisi.',
                'new_category_name.max'      => 'Nama kategori baru maksimal 50 karakter.',
            ]);

            $kat = Category::firstOrCreate(['category_name' => trim($request->new_category_name)]);
            $request->merge(['category_id' => $kat->category_id]);
        }

        if ($request->author_id === 'new' || $request->filled('new_author_name')) {
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
            'category_id'      => 'nullable|exists:categories,category_id',
            'author_id'        => 'nullable|exists:authors,author_id',
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
