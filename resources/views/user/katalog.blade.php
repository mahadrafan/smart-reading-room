@extends('layouts.user')

@section('title', 'Katalog Buku')

@section('content')
    <div class="kepala">
        <h1>Halo, {{ Auth::user()->name }}</h1>
        <p class="ket">Cari buku, cek ketersediaannya, lalu ajukan peminjaman.</p>
    </div>

    {{-- ringkasan peminjaman milik user --}}
    <div class="ringkasan">
        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}" class="kartu-ringkas">
            <span class="angka">{{ $menunggu }}</span>
            <span class="label">Menunggu konfirmasi</span>
        </a>
        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}" class="kartu-ringkas">
            <span class="angka">{{ $dikonfirmasi }}</span>
            <span class="label">Dikonfirmasi (Siap diambil)</span>
        </a>
        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}" class="kartu-ringkas">
            <span class="angka">{{ $dipinjam }}</span>
            <span class="label">Sedang dipinjam</span>
        </a>
        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}" class="kartu-ringkas {{ $terlambat > 0 ? 'peringatan' : '' }}">
            <span class="angka">{{ $terlambat }}</span>
            <span class="label">Melewati batas kembali</span>
        </a>
    </div>

    {{-- kolom pencarian --}}
    <form method="GET" action="{{ route('dashboard') }}" class="cari">
        <label for="q" class="sr-only">Cari buku</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari judul buku atau nama penulis">
        @if (request('kategori'))
            <input type="hidden" name="kategori" value="{{ request('kategori') }}">
        @endif
        <button type="submit">Cari</button>
    </form>

    {{-- filter kategori --}}
    <div class="chip-baris">
        <a href="{{ route('dashboard', ['q' => request('q')]) }}"
           class="chip {{ request('kategori') ? '' : 'aktif' }}">Semua</a>
        @foreach ($kategori as $k)
            <a href="{{ route('dashboard', ['kategori' => $k->category_id, 'q' => request('q')]) }}"
               class="chip {{ request('kategori') == $k->category_id ? 'aktif' : '' }}">{{ $k->category_name }}</a>
        @endforeach
    </div>

    {{-- paling sering dipinjam --}}
    @if ($populer->count() > 0)
        <h2 class="judul-bagian">Paling Sering Dipinjam</h2>
        <div class="rak">
            @foreach ($populer as $b)
                @include('user._kartu', ['b' => $b])
            @endforeach
        </div>
    @endif

    {{-- semua koleksi / hasil pencarian --}}
    <h2 class="judul-bagian">
        @if (request('q') || request('kategori'))
            Hasil pencarian ({{ $buku->total() }} buku)
        @else
            Semua Koleksi
        @endif
    </h2>

    @if ($buku->count() > 0)
        <div class="rak">
            @foreach ($buku as $b)
                @include('user._kartu', ['b' => $b])
            @endforeach
        </div>

        @if ($buku->lastPage() > 1)
            <div class="halaman">
                @if ($buku->onFirstPage())
                    <span class="nonaktif">Sebelumnya</span>
                @else
                    <a href="{{ $buku->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span>Halaman {{ $buku->currentPage() }} dari {{ $buku->lastPage() }}</span>

                @if ($buku->hasMorePages())
                    <a href="{{ $buku->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="nonaktif">Berikutnya</span>
                @endif
            </div>
        @endif
    @else
        <div class="kosong">
            @if (request('q') || request('kategori'))
                <p>Tidak ada buku yang cocok dengan pencarianmu.</p>
                <a href="{{ route('dashboard') }}">Tampilkan semua buku</a>
            @else
                <p>Katalog masih kosong. Buku akan muncul setelah admin menambahkannya.</p>
            @endif
        </div>
    @endif
@endsection
