@extends('layouts.admin')

@section('title', 'Penulis')

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Penulis</h1>
            <p class="ket">Daftar nama penulis yang dipakai di katalog dan form tambah buku.</p>
        </div>
        <a href="{{ route('admin.penulis.create') }}" class="tombol">Tambah Penulis</a>
    </div>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Penulis</th>
                    <th>Biografi</th>
                    <th>Jumlah Buku</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $p->author_id }}</td>
                        <td>{{ $p->author_name }}</td>
                        <td>{{ $p->biography ? \Illuminate\Support\Str::limit($p->biography, 60) : '-' }}</td>
                        <td>{{ $p->books_count }}</td>
                        <td class="aksi">
                            <a href="{{ route('admin.penulis.edit', $p->author_id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.penulis.destroy', $p->author_id) }}"
                                  style="display:inline"
                                  onsubmit="return confirm('Hapus penulis {{ $p->author_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="kosong-tabel">
                            Belum ada penulis. <a href="{{ route('admin.penulis.create') }}">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
