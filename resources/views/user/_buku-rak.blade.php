{{-- satu buku yang berdiri di rak, butuh $b; opsional $pinjam untuk rak "sedang kamu pinjam",
     atau $info ('dipinjam' / 'rating') untuk mengganti keterangan stok --}}
@php
    $pinjam = $pinjam ?? null;
    $info = $info ?? null;
    $tautan = $pinjam ? route('peminjaman.detail', $pinjam->loan_id) : route('buku.detail', $b->book_id);
    $penulis = $b->author ? $b->author->author_name : 'Penulis belum diisi';

    // keterangan di bawah rak + progress masa pinjam (hanya untuk buku yang sedang dipinjam)
    $progres = null;
    $terlambat = false;
    if ($pinjam) {
        if ($pinjam->status == 'Dipinjam' && $pinjam->loan_date && $pinjam->due_date) {
            $terlambat = $pinjam->due_date->lt(today());
            $totalHari = max(1, $pinjam->loan_date->diffInDays($pinjam->due_date));
            $progres = $terlambat ? 100 : min(100, max(4, round($pinjam->loan_date->diffInDays(today()) / $totalHari * 100)));
            $keterangan = $terlambat
                ? 'Terlambat ' . $pinjam->hari_terlambat . ' hari'
                : 'Kembali ' . $pinjam->due_date->format('d/m/Y');
        } elseif ($pinjam->status == 'Dikonfirmasi') {
            $keterangan = 'Siap diambil';
        } else {
            $keterangan = 'Menunggu konfirmasi';
        }
    } elseif ($info == 'dipinjam') {
        $keterangan = ($b->jumlah_dipinjam ?? 0) . 'x dipinjam';
    } elseif ($info == 'rating') {
        // rata-rata rating, mis. "4.5 · 2 ulasan" (bintang ditampilkan terpisah)
        $keterangan = $b->jumlah_rating
            ? number_format((float) $b->rata_rating, 1) . ' · ' . $b->jumlah_rating . ' ulasan'
            : 'Belum ada rating';
    } else {
        $keterangan = $b->available_stock > 0 ? 'Tersedia ' . $b->available_stock : 'Stok habis';
    }

    $daftarWarna = ['#8671c9', '#1b2340', '#2f6f73', '#b5574b', '#5b7b3a', '#a07a2c'];
    $warnaSampul = $daftarWarna[($b->category_id ?? 0) % count($daftarWarna)];
@endphp
<a href="{{ $tautan }}" class="buku-rak" title="{{ $b->title }} — {{ $penulis }}">
    <span class="buku-rak-sampul">
        @if ($b->cover_url)
            <span class="buku-fisik">
                <img src="{{ $b->cover_url }}" alt="Sampul {{ $b->title }}" loading="lazy">
            </span>
        @else
            {{-- buku tanpa file sampul: tampilkan sampul polos berisi judul & penulis --}}
            <span class="buku-fisik buku-polos" style="background: {{ $warnaSampul }};">
                <span class="buku-polos-judul">{{ Str::limit($b->title, 48) }}</span>
                <span class="buku-polos-penulis">{{ Str::limit($penulis, 28) }}</span>
            </span>
        @endif

        @if (!$pinjam && $b->available_stock <= 0)
            <span class="pita-habis">Habis</span>
        @endif
    </span>

    <span class="buku-rak-papan">
        @if ($progres !== null)
            <span class="progres-pinjam {{ $terlambat ? 'lewat' : '' }}" role="progressbar"
                  aria-valuenow="{{ $progres }}" aria-valuemin="0" aria-valuemax="100" aria-label="Masa pinjam">
                <span style="width: {{ $progres }}%;"></span>
            </span>
        @endif
    </span>

    <span class="buku-rak-judul">{{ $b->title }}</span>
    <span class="buku-rak-ket {{ $info ? 'netral' : '' }} {{ $terlambat || (!$pinjam && !$info && $b->available_stock <= 0) ? 'merah' : '' }}">
        @if ($info == 'rating' && $b->jumlah_rating)
            <span class="bintang-rak" aria-hidden="true">★</span>
            <span class="sr-only">Rating rata-rata</span>
        @endif
        {{ $keterangan }}
    </span>
</a>
