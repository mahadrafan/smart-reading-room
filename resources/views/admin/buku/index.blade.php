@extends('layouts.admin')

@section('title', 'Kelola Buku')

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Kelola Buku</h1>
            <p class="ket">Daftar buku fisik di katalog Smart Reading Room.</p>
        </div>
        <a href="{{ route('admin.buku.create') }}" class="tombol">Tambah Buku</a>
    </div>

    <form method="GET" action="{{ route('admin.buku.index') }}" class="cari" style="margin-bottom:20px;">
        <label for="q" class="sr-only">Cari judul buku</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari judul buku">
        <button type="submit" class="tombol tombol-kecil">Cari</button>
    </form>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Penulis</th>
                    <th>Stok</th>
                    <th>Tersedia</th>
                    <th>Ditambahkan oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $b)
                    <tr>
                        <td>{{ $b->title }}</td>
                        <td>{{ $b->category ? $b->category->category_name : '-' }}</td>
                        <td>{{ $b->author ? $b->author->author_name : '-' }}</td>
                        <td>{{ $b->stock }}</td>
                        <td>{{ $b->available_stock }}</td>
                        <td>{{ $b->creator ? $b->creator->name : '-' }}</td>
                        <td class="aksi">
                            <a href="{{ route('admin.buku.edit', $b->book_id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.buku.destroy', $b->book_id) }}"
                                  style="display:inline" onsubmit="return confirm('Hapus buku {{ $b->title }} dari katalog?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tabel">
                            Belum ada buku. <a href="{{ route('admin.buku.create') }}">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($daftar->hasPages())
        <div class="halaman">
            @if ($daftar->onFirstPage())
                <span class="nonaktif">Sebelumnya</span>
            @else
                <a href="{{ $daftar->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span>Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</span>
            @if ($daftar->hasMorePages())
                <a href="{{ $daftar->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="nonaktif">Berikutnya</span>
            @endif
        </div>
    @endif
@endsection
