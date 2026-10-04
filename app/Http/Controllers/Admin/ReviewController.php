<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
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
        $ulasan = Review::with(['user', 'book'])->findOrFail($id);
        $ulasan->delete();

        AdminLog::catat('Hapus', 'reviews', $ulasan->review_id,
            'Menghapus ulasan ' . $ulasan->rating . ' bintang oleh ' . ($ulasan->user->name ?? '-')
                . ' untuk buku "' . ($ulasan->book->title ?? '-') . '"');

        return redirect()->route('admin.reviews.index')->with('status', 'Ulasan berhasil dihapus.');
    }
}
