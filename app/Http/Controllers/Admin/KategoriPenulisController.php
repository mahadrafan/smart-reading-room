<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;

// tab Kategori & Penulis di halaman Kelola Buku.
// tambah baru tetap lewat form buku; di sini hanya lihat, edit, dan hapus.
// hapus hanya boleh bila tidak dipakai buku apa pun (aktif maupun nonaktif),
// karena buku nonaktif tetap menyimpan kategori & penulisnya untuk riwayat peminjaman.
class KategoriPenulisController extends Controller
{
    // ---------------- KATEGORI ----------------

    public function kategori(Request $request)
    {
        $daftar = Category::withCount(['books', 'books as buku_aktif' => fn ($q) => $q->where('is_active', 1)])
            ->when($request->q, fn ($q, $cari) => $q->where('category_name', 'like', '%' . $cari . '%'))
            ->orderBy('category_name')
            ->get();

        return view('admin.buku.kategori-penulis', [
            'jenis'  => 'kategori',
            'daftar' => $daftar,
            'idEdit' => (int) old('id_edit', $request->edit),
            'jumlah' => BukuController::jumlahTab(),
        ]);
    }

    public function updateKategori(Request $request, $id)
    {
        $kategori = Category::findOrFail($id);

        $data = $request->validate([
            'category_name' => 'required|string|max:100|unique:categories,category_name,' . $id . ',category_id',
            'description'   => 'nullable|string|max:255',
        ], $this->pesan(), [
            'category_name' => 'Nama kategori',
            'description'   => 'Deskripsi',
        ]);

        $namaLama = $kategori->category_name;
        $kategori->update($data);
        AdminLog::catat('Edit', 'categories', $kategori->category_id,
            'Mengubah kategori "' . $namaLama . '"' . ($namaLama !== $kategori->category_name ? ' menjadi "' . $kategori->category_name . '"' : ''));

        return redirect()->route('admin.buku.kategori.index', $request->only('q'))
            ->with('status', 'Kategori "' . $kategori->category_name . '" berhasil diperbarui.');
    }

    public function hapusKategori(Request $request, $id)
    {
        $kategori = Category::withCount('books')->findOrFail($id);

        if ($kategori->books_count > 0) {
            return back()->with('error', 'Kategori "' . $kategori->category_name . '" tidak bisa dihapus karena masih dipakai '
                . $kategori->books_count . ' buku (termasuk buku nonaktif).');
        }

        $kategori->delete();
        AdminLog::catat('Hapus', 'categories', $id, 'Menghapus kategori "' . $kategori->category_name . '"');

        return redirect()->route('admin.buku.kategori.index', $request->only('q'))
            ->with('status', 'Kategori "' . $kategori->category_name . '" berhasil dihapus.');
    }

    // ---------------- PENULIS ----------------

    public function penulis(Request $request)
    {
        $daftar = Author::withCount(['books', 'books as buku_aktif' => fn ($q) => $q->where('is_active', 1)])
            ->when($request->q, fn ($q, $cari) => $q->where('author_name', 'like', '%' . $cari . '%'))
            ->orderBy('author_name')
            ->get();

        return view('admin.buku.kategori-penulis', [
            'jenis'  => 'penulis',
            'daftar' => $daftar,
            'idEdit' => (int) old('id_edit', $request->edit),
            'jumlah' => BukuController::jumlahTab(),
        ]);
    }

    public function updatePenulis(Request $request, $id)
    {
        $penulis = Author::findOrFail($id);

        $data = $request->validate([
            'author_name' => 'required|string|max:100|unique:authors,author_name,' . $id . ',author_id',
            'biography'   => 'nullable|string|max:1000',
        ], $this->pesan(), [
            'author_name' => 'Nama penulis',
            'biography'   => 'Biografi',
        ]);

        $namaLama = $penulis->author_name;
        $penulis->update($data);
        AdminLog::catat('Edit', 'authors', $penulis->author_id,
            'Mengubah penulis "' . $namaLama . '"' . ($namaLama !== $penulis->author_name ? ' menjadi "' . $penulis->author_name . '"' : ''));

        return redirect()->route('admin.buku.penulis.index', $request->only('q'))
            ->with('status', 'Penulis "' . $penulis->author_name . '" berhasil diperbarui.');
    }

    public function hapusPenulis(Request $request, $id)
    {
        $penulis = Author::withCount('books')->findOrFail($id);

        if ($penulis->books_count > 0) {
            return back()->with('error', 'Penulis "' . $penulis->author_name . '" tidak bisa dihapus karena masih dipakai '
                . $penulis->books_count . ' buku (termasuk buku nonaktif).');
        }

        $penulis->delete();
        AdminLog::catat('Hapus', 'authors', $id, 'Menghapus penulis "' . $penulis->author_name . '"');

        return redirect()->route('admin.buku.penulis.index', $request->only('q'))
            ->with('status', 'Penulis "' . $penulis->author_name . '" berhasil dihapus.');
    }

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max'      => ':attribute maksimal :max karakter.',
            'unique'   => ':attribute sudah dipakai.',
        ];
    }
}
