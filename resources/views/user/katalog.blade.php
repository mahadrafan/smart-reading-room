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
        @if ($tampilan == 'semua')
            <input type="hidden" name="tampilan" value="semua">
        @endif
        <button type="submit">Cari</button>
    </form>

    {{-- filter kategori --}}
    @php $paramTampilan = $tampilan == 'semua' ? 'semua' : null; @endphp
    <div class="chip-baris">
        <a href="{{ route('dashboard', array_filter(['q' => request('q'), 'tampilan' => $paramTampilan])) }}"
           class="chip {{ request('kategori') ? '' : 'aktif' }}">Semua</a>
        @foreach ($kategori as $k)
            <a href="{{ route('dashboard', array_filter(['kategori' => $k->category_id, 'q' => request('q'), 'tampilan' => $paramTampilan])) }}"
               class="chip {{ request('kategori') == $k->category_id ? 'aktif' : '' }}">{{ $k->category_name }}</a>
        @endforeach
    </div>

    @php
        $sedangMencari = request('q') || request('kategori');
        $kategoriAktif = $kategori->firstWhere('category_id', request('kategori'));
    @endphp

    <div class="panel-rak">
        {{-- pilihan tampilan: rak buku atau grid semua buku --}}
        <nav class="tab-tampilan" aria-label="Pilihan tampilan katalog">
            <a href="{{ route('dashboard', array_filter(['q' => request('q'), 'kategori' => request('kategori')])) }}"
               class="{{ $tampilan == 'rak' ? 'aktif' : '' }}">Rak Buku</a>
            <a href="{{ route('dashboard', array_filter(['q' => request('q'), 'kategori' => request('kategori'), 'tampilan' => 'semua'])) }}"
               class="{{ $tampilan == 'semua' ? 'aktif' : '' }}">Semua Buku</a>
        </nav>

        @if ($tampilan == 'rak' && !$sedangMencari)
            {{-- rak utama: pinjaman aktif, populer, lalu satu rak per kategori --}}
            @if ($pinjamanAktif->count() > 0)
                @include('user._rak', [
                    'judul'      => 'Sedang Kamu Pinjam',
                    'pinjaman'   => $pinjamanAktif,
                    'tautan'     => route('peminjaman.index'),
                    'teksTautan' => 'Peminjaman saya',
                ])
            @endif

            @if ($populer->count() > 0)
                @include('user._rak', ['judul' => 'Paling Sering Dipinjam', 'buku' => $populer])
            @endif

            @forelse ($rakKategori as $rak)
                @include('user._rak', [
                    'judul'      => $rak['kategori']->category_name,
                    'buku'       => $rak['buku'],
                    'tautan'     => route('dashboard', ['kategori' => $rak['kategori']->category_id]),
                    'teksTautan' => 'Lihat semua (' . $rak['total'] . ')',
                ])
            @empty
                <div class="kosong">
                    <p>Katalog masih kosong. Buku akan muncul setelah admin menambahkannya.</p>
                </div>
            @endforelse
        @else
            {{-- hasil pencarian / filter kategori, atau tampilan "semua buku" --}}
            <div class="rak-kepala">
                <h2>
                    @if ($sedangMencari)
                        {{ request('q') ? 'Hasil pencarian' : ($kategoriAktif->category_name ?? 'Hasil filter') }}
                        <small>({{ $buku->total() }} buku)</small>
                    @else
                        Semua Koleksi
                    @endif
                </h2>
                @if ($sedangMencari)
                    <a href="{{ route('dashboard', $tampilan == 'semua' ? ['tampilan' => 'semua'] : []) }}" class="rak-tautan">
                        <span aria-hidden="true">&larr;</span> Semua rak
                    </a>
                @endif
            </div>

            @if ($buku->count() > 0)
                @if ($tampilan == 'rak')
                    @foreach ($buku->chunk(6) as $barisBuku)
                        @include('user._rak', ['buku' => $barisBuku])
                    @endforeach
                @else
                    <div class="rak">
                        @foreach ($buku as $b)
                            @include('user._kartu', ['b' => $b])
                        @endforeach
                    </div>
                @endif

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
                    @if ($sedangMencari)
                        <p>Tidak ada buku yang cocok dengan pencarianmu.</p>
                        <a href="{{ route('dashboard') }}">Tampilkan semua buku</a>
                    @else
                        <p>Katalog masih kosong. Buku akan muncul setelah admin menambahkannya.</p>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
