<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Author;
use Illuminate\Http\Request;

class PenulisController extends Controller
{
    // READ: daftar semua penulis
    public function index()
    {
        $daftar = Author::withCount('books')->orderBy('author_name')->get();
        return view('admin.penulis.index', ['daftar' => $daftar]);
    }

    public function create()
    {
        return view('admin.penulis.create');
    }

    // CREATE
    public function store(Request $request)
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

        return redirect()->route('admin.penulis.index')->with('status', 'Penulis berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $penulis = Author::findOrFail($id);
        return view('admin.penulis.edit', ['penulis' => $penulis]);
    }

    // UPDATE
    public function update(Request $request, $id)
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

        $penulis->author_name = $request->author_name;
        $penulis->biography   = $request->biography;
        $penulis->save();

        AdminLog::catat('Edit', 'authors', $penulis->author_id,
            'Mengubah penulis "' . $penulis->author_name . '"');

        return redirect()->route('admin.penulis.index')->with('status', 'Penulis berhasil diperbarui.');
    }

    // DELETE: hanya kalau belum dipakai buku manapun
    public function destroy($id)
    {
        $penulis = Author::withCount('books')->findOrFail($id);

        if ($penulis->books_count > 0) {
            return back()->with('error',
                'Penulis "' . $penulis->author_name . '" tidak bisa dihapus karena masih dipakai ' .
                $penulis->books_count . ' buku.');
        }

        $nama = $penulis->author_name;
        $penulis->delete();

        AdminLog::catat('Hapus', 'authors', $id, 'Menghapus penulis "' . $nama . '"');

        return redirect()->route('admin.penulis.index')->with('status', 'Penulis berhasil dihapus.');
    }
}
