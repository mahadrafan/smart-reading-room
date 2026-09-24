@extends('layouts.admin')

@section('title', 'Kategori & Penulis')

@section('content')
    <div class="kepala">
        <h1>Kategori & Penulis</h1>
        <p class="ket">Data pendukung yang dipakai di form Kelola Buku.</p>
    </div>

    <div class="dua-kotak">
        {{-- ============ KATEGORI ============ --}}
        <div>
            <h2 class="judul-kecil">Kategori</h2>

            <div class="kotak kotak-form" style="margin-bottom:16px;">
                <form method="POST" action="{{ route('admin.kp.kategori.store') }}">
                    @csrf
                    <div class="isian">
                        <label for="category_name">Nama kategori baru</label>
                        <input type="text" id="category_name" name="category_name"
                               placeholder="Contoh: Fiksi"
                               class="@error('category_name') salah @enderror">
                        @error('category_name')
                            <p class="pesan-salah">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="tombol tombol-kecil">Tambah Kategori</button>
                </form>
            </div>

            <div class="kotak">
                <table class="tabel">
                    <thead>
                        <tr><th>Nama</th><th>Jml Buku</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($kategori as $k)
                            <tr>
                                <td>{{ $k->category_name }}</td>
                                <td>{{ $k->books_count }}</td>
                                <td class="aksi">
                                    <form method="POST" action="{{ route('admin.kp.kategori.destroy', $k->category_id) }}"
                                          style="display:inline" onsubmit="return confirm('Hapus kategori {{ $k->category_name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bahaya">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="kosong-tabel">Belum ada kategori.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ PENULIS ============ --}}
        <div>
            <h2 class="judul-kecil">Penulis</h2>

            <div class="kotak kotak-form" style="margin-bottom:16px;">
                <form method="POST" action="{{ route('admin.kp.penulis.store') }}">
                    @csrf
                    <div class="isian">
                        <label for="author_name">Nama penulis baru</label>
                        <input type="text" id="author_name" name="author_name"
                               placeholder="Contoh: Andrea Hirata"
                               class="@error('author_name') salah @enderror">
                        @error('author_name')
                            <p class="pesan-salah">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="tombol tombol-kecil">Tambah Penulis</button>
                </form>
            </div>

            <div class="kotak">
                <table class="tabel">
                    <thead>
                        <tr><th>Nama</th><th>Jml Buku</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($penulis as $p)
                            <tr>
                                <td>{{ $p->author_name }}</td>
                                <td>{{ $p->books_count }}</td>
                                <td class="aksi">
                                    <form method="POST" action="{{ route('admin.kp.penulis.destroy', $p->author_id) }}"
                                          style="display:inline" onsubmit="return confirm('Hapus penulis {{ $p->author_name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bahaya">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="kosong-tabel">Belum ada penulis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
