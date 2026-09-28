{{-- satu rak buku: butuh $judul dan $buku (koleksi Book) atau $pinjaman (koleksi Loan);
     opsional $tautan + $teksTautan untuk link "lihat semua" di pojok kanan,
     dan $info ('dipinjam' / 'ulasan') untuk keterangan di bawah buku --}}
<section class="rak-bagian">
    @if (!empty($judul) || !empty($tautan))
        <div class="rak-kepala">
            @if (!empty($judul))
                <h2>{{ $judul }}</h2>
            @endif
            @if (!empty($tautan))
                <a href="{{ $tautan }}" class="rak-tautan">{{ $teksTautan ?? 'Lihat semua' }} <span aria-hidden="true">&rarr;</span></a>
            @endif
        </div>
    @endif

    <div class="rak-rak">
        <div class="rak-isi">
            @if (isset($pinjaman))
                @foreach ($pinjaman as $p)
                    @include('user._buku-rak', ['b' => $p->book, 'pinjam' => $p])
                @endforeach
            @else
                @foreach ($buku as $b)
                    @include('user._buku-rak', ['b' => $b, 'info' => $info ?? null])
                @endforeach
            @endif
        </div>
    </div>
</section>
