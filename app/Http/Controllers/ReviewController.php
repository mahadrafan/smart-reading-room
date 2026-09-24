<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $bookId)
    {
        $book = Book::aktif()->findOrFail($bookId);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_text' => ['required', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Rating wajib dipilih.',
            'rating.between' => 'Rating harus antara 1 sampai 5.',
            'review_text.required' => 'Komentar ulasan wajib diisi.',
        ]);

        Review::updateOrCreate(
            ['user_id' => Auth::id(), 'book_id' => $book->book_id],
            $data
        );

        return back()->with('status', 'Ulasan dan rating berhasil disimpan.');
    }
}
