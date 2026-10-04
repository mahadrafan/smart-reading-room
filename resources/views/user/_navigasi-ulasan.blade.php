{{-- navigasi per 5 ulasan: tombol ‹ › untuk berpindah ke 5 ulasan sebelumnya / berikutnya --}}
@php
    $akhirHalaman = min($awalHalaman + $daftarUlasan->count() - 1, $jumlahUlasan);
    $urlHalaman = fn ($nomor) => route('ulasan.index', $filter + ($nomor > 1 ? ['halaman' => $nomor] : []));
@endphp
<nav class="navigasi-ulasan" aria-label="Halaman ulasan">
    <span class="info-halaman-ulasan">
        <strong>{{ $awalHalaman }}–{{ $akhirHalaman }}</strong> dari {{ $jumlahUlasan }} ulasan
    </span>

    @if ($jumlahHalaman > 1)
        <div class="tombol-halaman-grup">
            @if ($halaman > 1)
                <a href="{{ $urlHalaman($halaman - 1) }}" class="tombol-halaman" rel="prev" data-arah-halaman="mundur" aria-label="5 ulasan sebelumnya">&lsaquo;</a>
            @else
                <span class="tombol-halaman nonaktif" aria-hidden="true">&lsaquo;</span>
            @endif

            <span class="nomor-halaman-ulasan">Halaman {{ $halaman }} / {{ $jumlahHalaman }}</span>

            @if ($halaman < $jumlahHalaman)
                <a href="{{ $urlHalaman($halaman + 1) }}" class="tombol-halaman" rel="next" data-arah-halaman="maju" aria-label="5 ulasan berikutnya">&rsaquo;</a>
            @else
                <span class="tombol-halaman nonaktif" aria-hidden="true">&rsaquo;</span>
            @endif
        </div>
    @endif
</nav>
