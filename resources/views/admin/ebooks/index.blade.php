@extends('layouts.admin')

@section('title', 'Kelola E-book')

@section('content')
    <div class="kepala kepala-baris">
        <div><h1>Kelola E-book</h1><p class="ket">Daftar koleksi PDF yang dapat diunduh peminjam.</p></div>
        <a class="tombol" href="{{ route('admin.ebooks.create') }}">Tambah E-book</a>
    </div>
    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>Judul</th><th>Penulis</th><th>Kategori</th><th>Tahun</th><th>File</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($ebooks as $ebook)
                    <tr>
                        <td>{{ $ebook->title }}</td><td>{{ $ebook->author->author_name ?? '-' }}</td><td>{{ $ebook->category->category_name ?? '-' }}</td><td>{{ $ebook->publication_year ?: '-' }}</td>
                        <td>{{ $ebook->file_url ? 'Tersedia' : 'Belum ada' }}</td>
                        <td class="aksi"><form method="POST" action="{{ route('admin.ebooks.destroy', $ebook->ebook_id) }}" onsubmit="return confirm('Hapus e-book dan file PDF ini?');">@csrf @method('DELETE')<button class="bahaya">Hapus</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong-tabel">Belum ada e-book.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
