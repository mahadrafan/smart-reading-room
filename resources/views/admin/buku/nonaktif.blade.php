@extends('layouts.admin')

@section('title', 'Kelola Buku - Nonaktif')

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
        Buku yang dihapus dari katalog tidak dihapus permanen, tetapi disimpan di sini agar riwayat peminjaman, denda,
        dan ulasannya tetap utuh. Peminjam tidak bisa melihat atau meminjam buku nonaktif sampai diaktifkan kembali.
        Hapus permanen hanya bisa untuk buku yang belum pernah dipinjam maupun diulas (misalnya salah input).
    </p>

    <form method="GET" action="{{ route('admin.buku.nonaktif') }}" class="cari-admin">
        <label for="q" class="sr-only">Cari judul buku nonaktif</label>
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
                    <th>Riwayat</th>
                    <th>Dinonaktifkan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $b)
                    <tr>
                        <td>
                            {{ $b->title }}
                            @if ($b->pinjaman_aktif > 0)
                                {{-- data lama: dinonaktifkan sebelum ada aturan larangan, padahal masih dipinjam --}}
                                <span class="peringatan-kecil">Masih ada {{ $b->pinjaman_aktif }} peminjaman aktif</span>
                            @endif
                        </td>
                        <td>{{ $b->category->category_name ?? '-' }}</td>
                        <td>{{ $b->nama_penulis ?? '-' }}</td>
                        <td>{{ $b->stock }}</td>
                        <td class="info-nonaktif">
                            @if ($b->jumlah_pinjaman == 0 && $b->jumlah_ulasan == 0)
                                Belum pernah dipakai
                            @else
                                {{ $b->jumlah_pinjaman }}x dipinjam
                                <small>{{ $b->jumlah_ulasan }} ulasan</small>
                            @endif
                        </td>
                        <td class="info-nonaktif">
                            {{ $b->updated_at ? \Illuminate\Support\Carbon::parse($b->updated_at)->format('d M Y, H:i') : '-' }}
                            <small>{{ $b->updater->name ?? '' }}</small>
                        </td>
                        <td class="aksi">
                            <form method="POST" action="{{ route('admin.buku.aktifkan', $b->book_id) }}" style="display:inline"
                                  onsubmit="return confirm({{ Js::from('Aktifkan kembali buku "' . $b->title . '"? Buku akan tampil lagi di katalog peminjam.') }})">
                                @csrf
                                @method('PUT')
                                @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                                <button type="submit">Aktifkan kembali</button>
                            </form>
                            @if ($b->jumlah_pinjaman == 0 && $b->jumlah_ulasan == 0)
                                <form method="POST" action="{{ route('admin.buku.hapus-permanen', $b->book_id) }}" style="display:inline"
                                      onsubmit="return confirm({{ Js::from('Hapus permanen buku "' . $b->title . '"? Data dan cover buku akan dihapus dari database dan tidak bisa dikembalikan.') }})">
                                    @csrf
                                    @method('DELETE')
                                    @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                                    <button type="submit" class="bahaya">Hapus permanen</button>
                                </form>
                            @else
                                {{-- sudah punya riwayat peminjaman / ulasan: hanya bisa disimpan sebagai nonaktif --}}
                                <span class="aksi-mati" title="Sudah pernah dipinjam atau diulas, jadi riwayatnya tetap disimpan dan tidak bisa dihapus permanen">Hapus permanen</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tabel">
                            {{ request('q') ? 'Tidak ada buku nonaktif yang cocok dengan pencarian.' : 'Tidak ada buku nonaktif.' }}
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
