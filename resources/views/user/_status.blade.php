{{-- label status peminjaman, butuh variabel $pinjam --}}
@php
    $terlambat = $pinjam->status == 'Dipinjam' && $pinjam->due_date && $pinjam->due_date->lt(today());

    if ($terlambat) {
        $kelasBadge = 'b-tolak';
        $teksBadge = 'Terlambat ' . $pinjam->hari_terlambat . ' hari';
    } elseif ($pinjam->status == 'Menunggu') {
        $kelasBadge = 'b-tunggu';
        $teksBadge = 'Menunggu';
    } elseif ($pinjam->status == 'Dikonfirmasi') {
        $kelasBadge = 'b-konfirmasi';
        $teksBadge = 'Dikonfirmasi';
    } elseif ($pinjam->status == 'Dipinjam') {
        $kelasBadge = 'b-setuju';
        $teksBadge = 'Dipinjam';
    } elseif ($pinjam->status == 'Gagal') {
        $kelasBadge = 'b-tolak';
        $teksBadge = 'Gagal';
    } else {
        $kelasBadge = 'b-kembali';
        $teksBadge = 'Dikembalikan';
    }
@endphp
<span class="badge {{ $kelasBadge }}">{{ $teksBadge }}</span>
