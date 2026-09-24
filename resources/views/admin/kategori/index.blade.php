@extends('layouts.admin')

@section('title', 'Kategori')

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Kategori</h1>
            <p class="ket">Daftar kategori buku yang dipakai di katalog dan form tambah buku.</p>
        </div>
        <a href="{{ route('admin.kategori.create') }}" class="tombol">Tambah Kategori</a>
    </div>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Buku</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $k)
                    <tr>
                        <td>{{ $k->category_id }}</td>
                        <td>{{ $k->category_name }}</td>
                        <td>{{ $k->description ?: '-' }}</td>
                        <td>{{ $k->books_count }}</td>
                        <td class="aksi">
                            <a href="{{ route('admin.kategori.edit', $k->category_id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.kategori.destroy', $k->category_id) }}"
                                  style="display:inline"
                                  onsubmit="return confirm('Hapus kategori {{ $k->category_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="kosong-tabel">
                            Belum ada kategori. <a href="{{ route('admin.kategori.create') }}">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
