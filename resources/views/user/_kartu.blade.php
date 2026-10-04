{{-- kartu buku di katalog, butuh variabel $b --}}
<div class="kartu-buku">
    <a href="{{ route('buku.detail', $b->book_id) }}" class="kartu-buku-gambar">
        @include('user._sampul', ['b' => $b])
        @if ($b->category)
            <span class="label-kat label-kat-overlay">{{ $b->category->category_name }}</span>
        @endif
    </a>

    <div class="info-buku">
        <a href="{{ route('buku.detail', $b->book_id) }}" class="tautan-judul">
            <h3>{{ $b->title }}</h3>
        </a>
        <p class="penulis">{{ $b->author ? $b->author->author_name : 'Penulis belum diisi' }}</p>

        @if ($b->description)
            <p class="sinopsis-ringkas">{{ Str::limit($b->description, 90) }}</p>
        @endif

        <div class="baris-info">
            <span>{{ $b->jumlah_dipinjam ?? 0 }}x dipinjam</span>
            @if ($b->available_stock > 0)
                <span class="stok ada">Tersedia {{ $b->available_stock }}</span>
            @else
                <span class="stok habis">Habis</span>
            @endif
        </div>

        @if (isset($jedaPinjam[$b->book_id]))
            {{-- baru dikembalikan user ini: jeda pinjam ulang --}}
            <span class="tombol tombol-penuh nonaktif" title="Buku yang baru kamu kembalikan bisa dipinjam lagi mulai {{ $jedaPinjam[$b->book_id]->format('d M Y') }}">
                Bisa dipinjam {{ $jedaPinjam[$b->book_id]->format('d M') }}
            </span>
        @elseif ($b->available_stock > 0)
            <a href="{{ route('peminjaman.buat', ['buku' => $b->book_id]) }}" class="tombol tombol-penuh">Pinjam Buku</a>
        @else
            <span class="tombol tombol-penuh nonaktif">Stok habis</span>
        @endif
    </div>
</div>