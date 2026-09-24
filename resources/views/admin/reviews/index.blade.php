@extends('layouts.admin')

@section('title', 'Kelola Ulasan')

@section('content')
    <div class="kepala">
        <h1>Kelola Ulasan</h1>
        <p class="ket">Pantau rating dan hapus komentar yang tidak sesuai.</p>
    </div>
    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>Pengguna</th><th>Buku</th><th>Rating</th><th>Komentar</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td>{{ $review->user->name ?? '-' }}</td><td>{{ $review->book->title ?? '-' }}</td>
                        <td><span class="rating-bintang">{{ str_repeat('★', $review->rating) }}</span></td>
                        <td style="white-space:normal; min-width:240px;">{{ $review->review_text }}</td>
                        <td>{{ $review->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="aksi"><form method="POST" action="{{ route('admin.reviews.destroy', $review->review_id) }}" onsubmit="return confirm('Hapus ulasan ini?');">@csrf @method('DELETE')<button class="bahaya">Hapus</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong-tabel">Belum ada ulasan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
