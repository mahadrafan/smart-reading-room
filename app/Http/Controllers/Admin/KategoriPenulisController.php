<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;

class KategoriPenulisController extends Controller
{
    // READ: satu halaman berisi dua daftar (kategori & penulis)
    public function index()
    {
        $kategori = Category::withCount('books')->orderBy('category_name')->get();
        $penulis  = Author::withCount('books')->orderBy('author_name')->get();

        return view('admin.kp.index', [
            'kategori' => $kategori,
            'penulis'  => $penulis,
        ]);
    }

    // ---------------- KATEGORI ----------------

    public function simpanKategori(Request $request)
    {
        $request->validate([
            'category_name' => 'required|max:100|unique:categories,category_name',
            'description'   => 'nullable|max:255',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah ada.',
        ], [
            'category_name' => 'Nama kategori',
            'description'   => 'Deskripsi',
        ]);

        $kategori = Category::create($request->only('category_name', 'description'));
        AdminLog::catat('Tambah', 'categories', $kategori->category_id,
            'Menambahkan kategori "' . $kategori->category_name . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function updateKategori(Request $request, $id)
    {
        $kategori = Category::findOrFail($id);

        $request->validate([
            'category_name' => 'required|max:100|unique:categories,category_name,' . $id . ',category_id',
            'description'   => 'nullable|max:255',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah ada.',
        ], [
            'category_name' => 'Nama kategori',
            'description'   => 'Deskripsi',
        ]);

        $kategori->update($request->only('category_name', 'description'));
        AdminLog::catat('Edit', 'categories', $kategori->category_id,
            'Mengubah kategori "' . $kategori->category_name . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function hapusKategori($id)
    {
        $kategori = Category::withCount('books')->findOrFail($id);

        if ($kategori->books_count > 0) {
            return back()->with('error',
                'Kategori "' . $kategori->category_name . '" tidak bisa dihapus, masih dipakai ' .
                $kategori->books_count . ' buku.');
        }

        $nama = $kategori->category_name;
        $kategori->delete();
        AdminLog::catat('Hapus', 'categories', $id, 'Menghapus kategori "' . $nama . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Kategori berhasil dihapus.');
    }

    // ---------------- PENULIS ----------------

    public function simpanPenulis(Request $request)
    {
        $request->validate([
            'author_name' => 'required|max:100|unique:authors,author_name',
            'biography'   => 'nullable|max:1000',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah ada.',
        ], [
            'author_name' => 'Nama penulis',
            'biography'   => 'Biografi',
        ]);

        $penulis = Author::create($request->only('author_name', 'biography'));
        AdminLog::catat('Tambah', 'authors', $penulis->author_id,
            'Menambahkan penulis "' . $penulis->author_name . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Penulis berhasil ditambahkan.');
    }

    public function updatePenulis(Request $request, $id)
    {
        $penulis = Author::findOrFail($id);

        $request->validate([
            'author_name' => 'required|max:100|unique:authors,author_name,' . $id . ',author_id',
            'biography'   => 'nullable|max:1000',
        ], [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah ada.',
        ], [
            'author_name' => 'Nama penulis',
            'biography'   => 'Biografi',
        ]);

        $penulis->update($request->only('author_name', 'biography'));
        AdminLog::catat('Edit', 'authors', $penulis->author_id,
            'Mengubah penulis "' . $penulis->author_name . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Penulis berhasil diperbarui.');
    }

    public function hapusPenulis($id)
    {
        $penulis = Author::withCount('books')->findOrFail($id);

        if ($penulis->books_count > 0) {
            return back()->with('error',
                'Penulis "' . $penulis->author_name . '" tidak bisa dihapus, masih dipakai ' .
                $penulis->books_count . ' buku.');
        }

        $nama = $penulis->author_name;
        $penulis->delete();
        AdminLog::catat('Hapus', 'authors', $id, 'Menghapus penulis "' . $nama . '"');

        return redirect()->route('admin.kp.index')->with('status', 'Penulis berhasil dihapus.');
    }
}
