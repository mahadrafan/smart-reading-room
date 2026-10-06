@extends('layouts.admin')

@php
    // tab Kategori dan tab Penulis memakai tampilan yang sama
    $k = $jenis === 'kategori'
        ? ['judul' => 'Kategori', 'id' => 'category_id', 'nama' => 'category_name', 'route' => 'admin.buku.kategori']
        : ['judul' => 'Penulis', 'id' => 'author_id', 'nama' => 'author_name', 'route' => 'admin.buku.penulis'];
    $kecil = strtolower($k['judul']);
    // baris yang baru ditambahkan diberi warna sebentar agar mudah ditemukan
    $idBaru = (int) session('idBaru');
@endphp

@section('title', 'Kelola Buku - ' . $k['judul'])

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Kelola Buku</h1>
            <p class="ket">Daftar buku fisik di katalog Smart Reading Room.</p>
        </div>
        <a href="{{ route('admin.buku.create') }}" class="tombol">Tambah Buku</a>
    </div>

    @include('admin.buku._tab')

    <p class="catatan-tab">
        {{ $k['judul'] }} baru bisa ditambahkan di sini tanpa menambah buku, atau langsung dari form <strong>Tambah/Edit Buku</strong>.
        {{ $k['judul'] }} hanya bisa dihapus bila tidak dipakai buku apa pun, termasuk buku nonaktif, agar riwayat peminjaman tetap utuh.
    </p>

    <div class="alat-kp">
        <form method="POST" action="{{ route($k['route'] . '.store') }}" class="tambah-kp">
            @csrf
            <label for="nama_baru">Tambah {{ $kecil }} baru</label>
            <div class="baris-isian-kp">
                <input type="text" id="nama_baru" name="nama_baru" maxlength="100" required
                       value="{{ old('nama_baru') }}" placeholder="Nama {{ $kecil }}"
                       class="@error('nama_baru') salah @enderror" @error('nama_baru') autofocus aria-invalid="true" @enderror>
                <button type="submit" class="tombol tombol-kecil">Tambah</button>
            </div>
            @error('nama_baru')<p class="pesan-salah">{{ $message }}</p>@enderror
        </form>

        <form method="GET" action="{{ route($k['route'] . '.index') }}" class="cari-kp">
            <label for="q">Cari {{ $kecil }}</label>
            <div class="baris-isian-kp">
                <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Nama {{ $kecil }}">
                <button type="submit" class="tombol tombol-kecil tombol-luar">Cari</button>
            </div>
        </form>
    </div>

    <div class="kotak">
        <table class="tabel tabel-kp">
            <thead>
                <tr>
                    <th>Nama {{ $kecil }}</th>
                    <th>Dipakai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $d)
                    @php
                        $id = $d->{$k['id']};
                        $nonaktif = $d->books_count - $d->buku_aktif;
                    @endphp

                    @if ($idEdit === (int) $id)
                        {{-- baris yang sedang diedit --}}
                        <tr class="baris-edit" id="data-{{ $id }}">
                            <td colspan="3">
                                <form method="POST" action="{{ route($k['route'] . '.update', $id) }}" class="form-edit-kp">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="id_edit" value="{{ $id }}">
                                    @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif

                                    <div class="isian">
                                        <label for="nama-{{ $id }}">Nama {{ $kecil }}</label>
                                        <input type="text" id="nama-{{ $id }}" name="{{ $k['nama'] }}" maxlength="100" required autofocus
                                               value="{{ old($k['nama'], $d->{$k['nama']}) }}" class="@error($k['nama']) salah @enderror">
                                        @error($k['nama'])<p class="pesan-salah">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="form-aksi">
                                        <button type="submit" class="tombol tombol-kecil">Simpan</button>
                                        <a href="{{ route($k['route'] . '.index', request()->only('q')) }}#data-{{ $id }}" class="tombol tombol-kecil tombol-luar">Batal</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @else
                        <tr id="data-{{ $id }}" @if ($idBaru === (int) $id) class="baris-baru" @endif>
                            <td class="nama-kp">{{ $d->{$k['nama']} }}</td>
                            <td class="dipakai-kp">
                                @if ($d->books_count == 0)
                                    <span class="badge b-kembali">Tidak dipakai</span>
                                @else
                                    {{ $d->books_count }} buku
                                    @if ($nonaktif > 0)<span class="ket-nonaktif">({{ $nonaktif }} nonaktif)</span>@endif
                                @endif
                            </td>
                            <td class="aksi">
                                <a href="{{ route($k['route'] . '.index', request()->only('q') + ['edit' => $id]) }}#data-{{ $id }}">Edit</a>
                                @if ($d->books_count == 0)
                                    <form method="POST" action="{{ route($k['route'] . '.destroy', $id) }}" style="display:inline"
                                          onsubmit="return confirm({{ Js::from('Hapus ' . $kecil . ' "' . $d->{$k['nama']} . '"? Data yang dihapus tidak bisa dikembalikan.') }})">
                                        @csrf
                                        @method('DELETE')
                                        @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                                        <button type="submit" class="bahaya">Hapus</button>
                                    </form>
                                @else
                                    <span class="aksi-mati" title="Masih dipakai {{ $d->books_count }} buku, jadi tidak bisa dihapus">Hapus</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="3" class="kosong-tabel">
                            {{ request('q') ? 'Tidak ada ' . $kecil . ' yang cocok dengan pencarian.' : 'Belum ada ' . $kecil . '.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
