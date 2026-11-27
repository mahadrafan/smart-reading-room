@extends('layouts.admin')

@section('title', 'Dashboard Data Warehouse')

@section('content')
@include('admin.dw._atas')

<div class="grid">
    <div class="panel">
        <h3>Tren peminjaman per bulan</h3>
        <div class="cv"><canvas id="cTren"></canvas></div>
        <p class="ket">Sumber: basdat_dw · v_peminjaman_bulanan. Periode: {{ $periode }}. Satuan: pengajuan per bulan.</p>
    </div>

    <div class="panel">
        <h3>Peminjaman berhasil per kategori × {{ $rinci }}</h3>
        <p class="ket" style="margin:0 0 8px">
            Rincian:
            <a href="{{ request()->fullUrlWithQuery(['rinci' => null]) }}">per tingkat</a> ·
            <a href="{{ request()->fullUrlWithQuery(['rinci' => 'kelas']) }}">per kelas</a>
        </p>
        <div class="cv"><canvas id="cKategori"></canvas></div>
        <p class="ket">Sumber: basdat_dw · v_peminjaman_bulanan. Periode: {{ $periode }}. Satuan: peminjaman berhasil.</p>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h3>10 buku terpopuler</h3>
        <table>
            <tr><th>#</th><th>Judul</th><th>Kategori</th><th class="n">Dipinjam</th></tr>
            @forelse ($buku as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $b->judul }}</td>
                    <td>{{ $b->kategori }}</td>
                    <td class="n">{{ number_format($b->berhasil, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </table>
        <p class="ket">Sumber: basdat_dw · v_buku_populer. Periode: {{ $periode }}. Satuan: peminjaman berhasil.</p>
    </div>

    <div class="panel">
        <h3>Kategori favorit tiap tingkat</h3>
        <table>
            <tr><th>Tingkat</th><th>Kategori favorit</th><th class="n">Dipinjam</th></tr>
            @forelse ($favorit as $x)
                <tr>
                    <td>{{ $x->tingkat }}</td>
                    <td>{{ $x->kategori }}</td>
                    <td class="n">{{ number_format($x->dipinjam, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </table>
        <p class="ket">Sumber: basdat_dw · v_peminjaman_bulanan. Periode: {{ $periode }}. Satuan: peminjaman berhasil.</p>
    </div>
</div>

@include('admin.dw._bawah')
<script>
new Chart(document.getElementById('cTren'), {
    type: 'line',
    data: {
        labels: @json($label),
        datasets: [
            ds('Pengajuan', @json($pengajuan), 0),
            ds('Berhasil', @json($berhasil), 2),
            ds('Gagal', @json($gagal), 3)
        ]
    },
    options: { maintainAspectRatio: false }
});

new Chart(document.getElementById('cKategori'), {
    type: 'bar',
    data: {
        labels: @json($kategori),
        datasets: @json($stack).map((s, i) => ds(s.label, s.data, i))
    },
    options: { maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true } } }
});
</script>
@endsection
