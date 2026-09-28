@extends('layouts.user')

@section('title', 'E-book')

@section('content')
    {{-- kepala halaman dengan pencarian --}}
    <div class="kepala-baris" style="margin-bottom:24px">
        <div>
            <h1 style="margin:0 0 5px;font-size:26px;font-weight:800;color:var(--navy)">Koleksi E-book</h1>
            <p style="margin:0;color:var(--abu);font-size:14px">Akses dan unduh referensi digital resmi Smart Reading Room tanpa batas.</p>
        </div>
        <form class="ebook-cari-bar" method="GET" action="{{ route('ebooks.index') }}" style="margin-bottom:0;max-width:340px">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul e-book, topik, atau penulis...">
            <button type="submit">Cari</button>
        </form>
    </div>

    {{-- tab kategori --}}
    @php
        $kategoriList = $ebooks->pluck('category.category_name')->filter()->unique()->values();
        $activeKat = request('kat');
    @endphp
    <div class="tab-kategori">
        <a href="{{ route('ebooks.index', array_filter(['q' => request('q')])) }}"
           class="tab-kat-btn {{ !$activeKat ? 'aktif' : '' }}">Semua E-book</a>
        @foreach ($kategoriList as $kat)
            <a href="{{ route('ebooks.index', array_filter(['q' => request('q'), 'kat' => $kat])) }}"
               class="tab-kat-btn {{ $activeKat == $kat ? 'aktif' : '' }}">{{ $kat }}</a>
        @endforeach
    </div>

    @php
        $filteredEbooks = $activeKat
            ? $ebooks->filter(fn($e) => optional($e->category)->category_name == $activeKat)->values()
            : $ebooks;
        $featured = $filteredEbooks->first();
        $populer  = $filteredEbooks->slice(1, 8);
    @endphp

    {{-- hero (buku unggulan) --}}
    @if ($featured)
    <div class="ebook-hero">
        <div class="ebook-hero-sampul">
            <div class="pdf-label">PDF</div>
            <div class="pdf-sub">{{ $featured->category?->category_name ?? 'E-Book' }}</div>
        </div>
        <div class="ebook-hero-info">
            <div class="ebook-hero-tags">
                <span class="ebook-tag unggulan">⭐ Paling Banyak Diunduh</span>
                @if ($featured->category)
                    <span class="ebook-tag kategori">{{ $featured->category->category_name }}</span>
                @endif
            </div>
            <h2 class="ebook-hero-judul">{{ $featured->title }}</h2>
            <p class="ebook-hero-oleh">
                Oleh {{ $featured->author?->author_name ?? 'Penulis belum diisi' }}
                @if ($featured->publisher) · {{ $featured->publisher }} @endif
                @if ($featured->publication_year) · {{ $featured->publication_year }} @endif
            </p>
            <p class="ebook-hero-deskripsi">
                {{ $featured->description ?: 'Tidak ada deskripsi untuk e-book ini.' }}
            </p>
            <div class="ebook-hero-meta">
                <div class="ebook-meta-item">
                    <span class="ikon">⏱️</span> Est. 45 menit baca
                </div>
                <div class="ebook-meta-item">
                    <span class="ikon">📄</span> Format: PDF
                </div>
                <div class="ebook-meta-item">
                    <span class="ikon">⭐</span> Rating: 4.8 / 5
                </div>
            </div>
            <div class="ebook-hero-aksi">
                @if ($featured->file_url)
                    <a href="{{ route('ebooks.download', $featured->ebook_id) }}" class="tombol">Baca Sekarang</a>
                    <a href="{{ route('ebooks.download', $featured->ebook_id) }}" class="tombol tombol-gelap">Unduh PDF</a>
                @else
                    <span class="tombol nonaktif">File belum tersedia</span>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- koleksi populer --}}
    @if ($populer->count() > 0)
    <div class="ebook-bagian-judul">
        <h2>Koleksi E-book Terpopuler</h2>
        <span class="sub">Menampilkan {{ $populer->count() }} dari {{ $filteredEbooks->count() }} e-book</span>
    </div>
    <div class="ebook-grid">
        @foreach ($populer as $ebook)
            <div class="ebook-kartu">
                <div class="ebook-kartu-sampul">
                    <div class="pdf-label">PDF</div>
                    <div class="pdf-sub">{{ $ebook->category?->category_name ?? 'E-Book' }}</div>
                </div>
                <div class="ebook-kartu-body">
                    <div class="ebook-kartu-meta">
                        @if ($ebook->category)
                            <span class="ebook-kartu-kat">{{ $ebook->category->category_name }}</span>
                        @else
                            <span class="ebook-kartu-kat">Umum</span>
                        @endif
                        <span class="ebook-kartu-rating">★ 4.8</span>
                    </div>
                    <div class="ebook-kartu-judul">{{ $ebook->title }}</div>
                    <div class="ebook-kartu-penulis">{{ $ebook->author?->author_name ?? '-' }}</div>
                    <div class="ebook-kartu-deskripsi">{{ $ebook->description ?: 'Belum ada deskripsi.' }}</div>
                    @if ($ebook->file_url)
                        <a href="{{ route('ebooks.download', $ebook->ebook_id) }}" class="ebook-unduh-btn">Unduh PDF</a>
                    @else
                        <span class="ebook-unduh-btn nonaktif">File belum tersedia</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- kosong --}}
    @if ($filteredEbooks->isEmpty())
        <div class="kosong">Belum ada e-book yang tersedia.</div>
    @endif

    {{-- info box --}}
    <div class="info-box">
        <span class="ikon-info">💡</span>
        <div>
            <strong>Informasi Akses:</strong>
            E-book di Smart Reading Room bebas diunduh kapan saja tanpa batas waktu pengembalian.
            Koleksi digital tersimpan otomatis di perangkat Anda setelah diunduh.
        </div>
    </div>
@endsection
