<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Category;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    // READ: daftar semua kategori
    public function index()
    {
        $daftar = Category::withCount('books')->orderBy('category_name')->get();
        return view('admin.kategori.index', ['daftar' => $daftar]);
    }

    // form tambah
    public function create()
    {
        return view('admin.kategori.create');
    }

    // CREATE: simpan kategori baru
    public function store(Request $request)
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

        return redirect()->route('admin.kategori.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    // form edit
    public function edit($id)
    {
        $kategori = Category::findOrFail($id);
        return view('admin.kategori.edit', ['kategori' => $kategori]);
    }

    // UPDATE
    public function update(Request $request, $id)
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

        $kategori->category_name = $request->category_name;
        $kategori->description   = $request->description;
        $kategori->save();

        AdminLog::catat('Edit', 'categories', $kategori->category_id,
            'Mengubah kategori "' . $kategori->category_name . '"');

        return redirect()->route('admin.kategori.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    // DELETE: hanya boleh kalau belum dipakai buku manapun
    public function destroy($id)
    {
        $kategori = Category::withCount('books')->findOrFail($id);

        if ($kategori->books_count > 0) {
            return back()->with('error',
                'Kategori "' . $kategori->category_name . '" tidak bisa dihapus karena masih dipakai ' .
                $kategori->books_count . ' buku.');
        }

        $nama = $kategori->category_name;
        $kategori->delete();

        AdminLog::catat('Hapus', 'categories', $id, 'Menghapus kategori "' . $nama . '"');

        return redirect()->route('admin.kategori.index')->with('status', 'Kategori berhasil dihapus.');
    }
}
