@extends('layouts.admin')

@php
    // tab Kategori dan tab Penulis memakai tampilan yang sama
    $k = $jenis === 'kategori'
        ? ['judul' => 'Kategori', 'id' => 'category_id', 'nama' => 'category_name', 'ket' => 'description',
           'labelKet' => 'Deskripsi', 'maksKet' => 255, 'route' => 'admin.buku.kategori']
        : ['judul' => 'Penulis', 'id' => 'author_id', 'nama' => 'author_name', 'ket' => 'biography',
           'labelKet' => 'Biografi', 'maksKet' => 1000, 'route' => 'admin.buku.penulis'];
    $kecil = strtolower($k['judul']);
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
        {{ $k['judul'] }} baru ditambahkan lewat form <strong>Tambah/Edit Buku</strong> (pilihan "+ Tambah {{ $kecil }} baru").
        {{ $k['judul'] }} hanya bisa dihapus bila tidak dipakai buku apa pun, termasuk buku nonaktif, agar riwayat peminjaman tetap utuh.
    </p>

    <form method="GET" action="{{ route($k['route'] . '.index') }}" class="cari-admin">
        <label for="q" class="sr-only">Cari {{ $kecil }}</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama {{ $kecil }}">
        <button type="submit" class="tombol tombol-kecil">Cari</button>
    </form>

    <div class="kotak">
        <table class="tabel tabel-kp">
            <thead>
                <tr>
                    <th>Nama {{ $kecil }}</th>
                    <th>{{ $k['labelKet'] }}</th>
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
                            <td colspan="4">
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
                                    <div class="isian">
                                        <label for="ket-{{ $id }}">{{ $k['labelKet'] }} <span class="opsional">(opsional)</span></label>
                                        <textarea id="ket-{{ $id }}" name="{{ $k['ket'] }}" rows="3" maxlength="{{ $k['maksKet'] }}"
                                                  class="@error($k['ket']) salah @enderror">{{ old($k['ket'], $d->{$k['ket']}) }}</textarea>
                                        @error($k['ket'])<p class="pesan-salah">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="form-aksi">
                                        <button type="submit" class="tombol tombol-kecil">Simpan</button>
                                        <a href="{{ route($k['route'] . '.index', request()->only('q')) }}#data-{{ $id }}" class="tombol tombol-kecil tombol-luar">Batal</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @else
                        <tr id="data-{{ $id }}">
                            <td class="nama-kp">{{ $d->{$k['nama']} }}</td>
                            <td class="ket-kp">{{ $d->{$k['ket']} ? Str::limit($d->{$k['ket']}, 90) : '-' }}</td>
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
                        <td colspan="4" class="kosong-tabel">
                            {{ request('q') ? 'Tidak ada ' . $kecil . ' yang cocok dengan pencarian.' : 'Belum ada ' . $kecil . '.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
