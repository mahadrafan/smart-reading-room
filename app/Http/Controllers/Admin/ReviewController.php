<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;

class ReviewController extends Controller
{
    public function index()
    {
        return view('admin.reviews.index', [
            'reviews' => Review::with(['user', 'book'])->orderByDesc('review_id')->get(),
        ]);
    }

    public function destroy($id)
    {
        Review::findOrFail($id)->delete();

        return redirect()->route('admin.reviews.index')->with('status', 'Ulasan berhasil dihapus.');
    }
}
