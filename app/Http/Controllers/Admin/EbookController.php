<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Category;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EbookController extends Controller
{
    public function index()
    {
        return view('admin.ebooks.index', [
            'ebooks' => Ebook::with(['category', 'author'])->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.ebooks.create', [
            'kategori' => Category::orderBy('category_name')->get(),
            'penulis' => Author::orderBy('author_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,category_id'],
            'author_id' => ['nullable', 'exists:authors,author_id'],
            'title' => ['required', 'string', 'max:200'],
            'publisher' => ['nullable', 'string', 'max:100'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'description' => ['nullable', 'string', 'max:2000'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $data['file_url'] = $request->file('pdf_file')->store('ebooks', 'local');
        $data['access_status'] = 'Aktif';
        unset($data['pdf_file']);
        Ebook::create($data);

        return redirect()->route('admin.ebooks.index')->with('status', 'E-book berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $ebook = Ebook::findOrFail($id);
        if ($ebook->file_url) {
            Storage::disk('local')->delete($ebook->file_url);
        }
        $ebook->delete();

        return redirect()->route('admin.ebooks.index')->with('status', 'E-book berhasil dihapus.');
    }
}
