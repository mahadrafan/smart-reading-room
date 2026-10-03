@extends('layouts.user')

@section('title', 'Katalog Buku')

@section('content')
    <div class="kepala">
        <h1>Halo, {{ Auth::user()->name }}</h1>
        <p class="ket">Cari buku, cek ketersediaannya, lalu ajukan peminjaman.</p>
    </div>

    @php
        // parameter filter yang sedang aktif, dipakai ulang di semua tautan agar pilihan tidak hilang
        $paramTampilan = $tampilan == 'semua' ? 'semua' : null;
        $paramFilter = array_filter([
            'q'        => request('q'),
            'kategori' => $kategoriDipilih ?: null,
            'urut'     => $urut !== 'judul' ? $urut : null,
            'tersedia' => $hanyaTersedia ? 1 : null,
        ]);
        $jumlahFilterAktif = count($kategoriDipilih) + ($urut !== 'judul' ? 1 : 0) + ($hanyaTersedia ? 1 : 0);
        $namaKategoriDipilih = $kategori->whereIn('category_id', $kategoriDipilih)->pluck('category_name');
    @endphp

    {{-- kolom pencarian --}}
    <form method="GET" action="{{ route('dashboard') }}" class="cari">
        <label for="q" class="sr-only">Cari buku</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari judul buku atau nama penulis">
        @foreach ($kategoriDipilih as $idKat)
            <input type="hidden" name="kategori[]" value="{{ $idKat }}">
        @endforeach
        @if ($urut !== 'judul')
            <input type="hidden" name="urut" value="{{ $urut }}">
        @endif
        @if ($hanyaTersedia)
            <input type="hidden" name="tersedia" value="1">
        @endif
        @if ($tampilan == 'semua')
            <input type="hidden" name="tampilan" value="semua">
        @endif
        <button type="submit">Cari</button>
    </form>

    {{-- filter: 5 kategori teratas sebagai tombol cepat + panel filter lengkap (bisa pilih beberapa) --}}
    <div class="chip-baris filter-katalog">
        <a href="{{ route('dashboard', array_filter(array_merge(Arr::except($paramFilter, 'kategori'), ['tampilan' => $paramTampilan]))) }}"
           class="chip {{ $kategoriDipilih ? '' : 'aktif' }}">Semua</a>
        @foreach ($kategoriCepat as $k)
            <a href="{{ route('dashboard', array_filter(array_merge($paramFilter, ['kategori' => [$k->category_id], 'tampilan' => $paramTampilan]))) }}"
               class="chip {{ in_array($k->category_id, $kategoriDipilih) ? 'aktif' : '' }}">{{ $k->category_name }}</a>
        @endforeach

        <details class="panel-filter">
            <summary class="chip chip-filter {{ $jumlahFilterAktif ? 'ada-filter' : '' }}">
                <span aria-hidden="true">⚙</span> Filter
                @if ($jumlahFilterAktif)
                    <span class="jumlah-filter">{{ $jumlahFilterAktif }}</span>
                @endif
            </summary>

            <form method="GET" action="{{ route('dashboard') }}" class="panel-filter-isi">
                @if (request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if ($tampilan == 'semua')
                    <input type="hidden" name="tampilan" value="semua">
                @endif

                <fieldset>
                    <legend>Kategori <small>(boleh pilih lebih dari satu)</small></legend>
                    <div class="pilihan-kategori">
                        @foreach ($kategori as $k)
                            <label class="pilihan-cek">
                                <input type="checkbox" name="kategori[]" value="{{ $k->category_id }}"
                                       {{ in_array($k->category_id, $kategoriDipilih) ? 'checked' : '' }}>
                                <span>{{ $k->category_name }}</span>
                                <small>{{ $k->jumlah_buku }}</small>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Urutkan</legend>
                    <div class="pilihan-urut">
                        @foreach ($daftarUrut as $nilai => $teks)
                            <label class="pilihan-cek">
                                <input type="radio" name="urut" value="{{ $nilai }}" {{ $urut === $nilai ? 'checked' : '' }}>
                                <span>{{ $teks }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="pilihan-cek pilihan-tersedia">
                    <input type="checkbox" name="tersedia" value="1" {{ $hanyaTersedia ? 'checked' : '' }}>
                    <span>Hanya buku yang tersedia</span>
                </label>

                <div class="panel-filter-aksi">
                    <a href="{{ route('dashboard', array_filter(['q' => request('q'), 'tampilan' => $paramTampilan])) }}" class="periode-reset">Reset</a>
                    <button type="submit" class="periode-apply">Terapkan</button>
                </div>
            </form>
        </details>
    </div>

    <div class="panel-rak">
        {{-- pilihan tampilan: rak buku atau grid semua buku --}}
        <nav class="tab-tampilan" aria-label="Pilihan tampilan katalog">
            <a href="{{ route('dashboard', $paramFilter) }}"
               class="{{ $tampilan == 'rak' ? 'aktif' : '' }}">Rak Buku</a>
            <a href="{{ route('dashboard', array_merge($paramFilter, ['tampilan' => 'semua'])) }}"
               class="{{ $tampilan == 'semua' ? 'aktif' : '' }}">Semua Buku</a>
        </nav>

        @if ($tampilan == 'rak' && !$sedangMencari)
            {{-- rak utama: sedang dipinjam, paling sering dipinjam, top review --}}
            @if ($pinjamanAktif->count() > 0)
                @include('user._rak', [
                    'judul'      => 'Sedang Kamu Pinjam',
                    'pinjaman'   => $pinjamanAktif,
                    'tautan'     => route('peminjaman.index'),
                    'teksTautan' => 'Riwayat peminjaman',
                ])
            @endif

            @if ($populer->count() > 0)
                @include('user._rak', [
                    'judul'      => 'Paling Sering Dipinjam',
                    'buku'       => $populer,
                    'info'       => 'dipinjam',
                    'tautan'     => route('dashboard', ['urut' => 'populer']),
                    'teksTautan' => 'Lihat semua',
                ])
            @endif

            @if ($topReview->count() > 0)
                @include('user._rak', [
                    'judul'      => 'Top Review',
                    'buku'       => $topReview,
                    'info'       => 'rating',
                    'tautan'     => route('dashboard', ['urut' => 'rating']),
                    'teksTautan' => 'Lihat semua',
                ])
            @endif

            @if ($pinjamanAktif->isEmpty() && $populer->isEmpty() && $topReview->isEmpty())
                <div class="kosong">
                    <p>Belum ada buku yang dipinjam atau diulas.</p>
                    <a href="{{ route('dashboard', ['tampilan' => 'semua']) }}">Lihat semua buku</a>
                </div>
            @endif
        @else
            {{-- hasil pencarian / filter kategori, atau tampilan "semua buku" --}}
            <div class="rak-kepala">
                <h2>
                    @if ($sedangMencari)
                        {{-- contoh: "Fiksi & Sastra · Paling sering dipinjam" --}}
                        @php
                            $bagianJudul = array_filter([
                                request('q') ? 'Hasil pencarian "' . request('q') . '"' : null,
                                $namaKategoriDipilih->isNotEmpty() ? $namaKategoriDipilih->implode(', ') : null,
                                $urut !== 'judul' ? $daftarUrut[$urut] : null,
                                $hanyaTersedia ? 'Tersedia' : null,
                            ]);
                        @endphp
                        {{ implode(' · ', $bagianJudul) ?: 'Hasil filter' }}
                        <small>({{ $buku->total() }} buku)</small>
                    @else
                        Semua Koleksi
                    @endif
                </h2>
                @if ($sedangMencari)
                    <a href="{{ route('dashboard', $tampilan == 'semua' ? ['tampilan' => 'semua'] : []) }}" class="rak-tautan">
                        <span aria-hidden="true">&larr;</span> {{ $tampilan == 'semua' ? 'Hapus filter' : 'Semua rak' }}
                    </a>
                @endif
            </div>

            @if ($buku->count() > 0)
                @if ($tampilan == 'rak')
                    {{-- keterangan di bawah buku mengikuti urutan: jumlah dipinjam / rata-rata rating / stok --}}
                    @php $infoRak = ['populer' => 'dipinjam', 'rating' => 'rating'][$urut] ?? null; @endphp
                    @foreach ($buku->chunk(6) as $barisBuku)
                        @include('user._rak', ['buku' => $barisBuku, 'info' => $infoRak])
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

@section('scripts')
<script>
    // panel filter: tutup saat klik di luar / tekan Esc, dan rata kanan bila panel keluar layar
    (function () {
        var panel = document.querySelector('.panel-filter');
        if (!panel) { return; }
        var isi = panel.querySelector('.panel-filter-isi');

        panel.addEventListener('toggle', function () {
            if (!panel.open) { return; }
            isi.style.left = '0';
            isi.style.right = 'auto';
            if (isi.getBoundingClientRect().right > window.innerWidth - 16) {
                isi.style.left = 'auto';
                isi.style.right = '0';
            }
        });

        document.addEventListener('click', function (e) {
            if (panel.open && !panel.contains(e.target)) { panel.open = false; }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.open) {
                panel.open = false;
                panel.querySelector('summary').focus();
            }
        });
    })();
</script>
@endsection
