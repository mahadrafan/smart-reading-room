<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EbookController extends Controller
{
    public function index(Request $request)
    {
        $query = Ebook::with(['category', 'author'])->where('access_status', 'Aktif');

        if ($request->q) {
            $query->where(function ($builder) use ($request) {
                $builder->where('title', 'like', '%' . $request->q . '%')
                    ->orWhereHas('author', fn ($author) => $author->where('author_name', 'like', '%' . $request->q . '%'));
            });
        }

        return view('user.ebooks', ['ebooks' => $query->orderBy('title')->get()]);
    }

    public function download($id)
    {
        $ebook = Ebook::where('access_status', 'Aktif')->findOrFail($id);
        abort_unless($ebook->file_url && Storage::disk('local')->exists($ebook->file_url), 404);

        return Storage::disk('local')->download($ebook->file_url, $ebook->title . '.pdf');
    }
}
