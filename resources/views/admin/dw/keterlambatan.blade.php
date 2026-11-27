@extends('layouts.admin')

@section('title', 'Dashboard Data Warehouse')

@section('content')
@include('admin.dw._atas')

<div class="kpi">
    <div>
        <span>Kewajiban pengembalian</span>
        <b>{{ number_format($kpi['kewajiban'], 0, ',', '.') }}</b>
        <small>sudah jatuh tempo</small>
    </div>
    <div>
        <span>Terlambat</span>
        <b>{{ number_format($kpi['terlambat'], 0, ',', '.') }}</b>
        <small>{{ number_format($kpi['persen_terlambat'], 1, ',', '.') }}% dari kewajiban</small>
    </div>
    <div>
        <span>Rata-rata hari terlambat</span>
        <b>{{ number_format($kpi['rata_hari'], 1, ',', '.') }}</b>
        <small>hanya untuk yang terlambat</small>
    </div>
    <div>
        <span>Total denda</span>
        <b>Rp {{ number_format($kpi['denda'], 0, ',', '.') }}</b>
        <small>hari terlambat × Rp1.000</small>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h3>Persentase terlambat per bulan jatuh tempo</h3>
        <div class="cv"><canvas id="cBulan"></canvas></div>
        <p class="ket">
            Sumber: basdat_dw · v_keterlambatan_bulanan. Periode: {{ $periode }} (menurut tanggal jatuh tempo).
            Satuan: persen kewajiban. Peminjaman yang belum kembali dihitung sampai tanggal acuan ETL.
        </p>
    </div>

    <div class="panel">
        <h3>Persentase terlambat per kategori buku</h3>
        <div class="cv"><canvas id="cKategori"></canvas></div>
        <p class="ket">Sumber: basdat_dw · v_keterlambatan_bulanan. Periode: {{ $periode }}. Satuan: persen kewajiban.</p>
    </div>
</div>

<div class="panel">
    <h3>Keterlambatan per tingkat dan kelas</h3>
    <table>
        <tr>
            <th>Tingkat · kelas</th><th class="n">Kewajiban</th><th class="n">Terlambat</th>
            <th class="n">% terlambat</th><th class="n">Rata-rata hari</th><th class="n">Denda</th>
        </tr>
        @forelse ($kelompok as $x)
            <tr>
                <td>{{ $x['nama'] }}</td>
                <td class="n">{{ $x['kewajiban'] }}</td>
                <td class="n">{{ $x['terlambat'] }}</td>
                <td class="n">{{ number_format($x['persen'], 1, ',', '.') }}</td>
                <td class="n">{{ number_format($x['rata_hari'], 1, ',', '.') }}</td>
                <td class="n">Rp {{ number_format($x['denda'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Tidak ada data untuk filter ini.</td></tr>
        @endforelse
    </table>
    <p class="ket">Sumber: basdat_dw · v_keterlambatan_bulanan. Periode: {{ $periode }}. Satuan: kewajiban, persen, hari, rupiah.</p>
</div>

<div class="panel">
    <h3>Sebaran kewajiban menurut status pengembalian dan denda</h3>
    <table>
        <tr>
            <th>Status</th><th>Posisi buku</th><th>Status denda</th>
            <th class="n">Kewajiban</th><th class="n">Total denda</th>
        </tr>
        @forelse ($status as $s)
            <tr>
                <td>{{ $s->status_pengembalian }}</td>
                <td>{{ $s->posisi_buku }}</td>
                <td>{{ $s->status_denda }}</td>
                <td class="n">{{ $s->k }}</td>
                <td class="n">Rp {{ number_format($s->d, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Tidak ada data untuk filter ini.</td></tr>
        @endforelse
    </table>
    <p class="ket">Sumber: basdat_dw · v_keterlambatan_status. Periode: {{ $periode }}. Satuan: kewajiban dan rupiah.</p>
</div>

@include('admin.dw._bawah')
<script>
new Chart(document.getElementById('cBulan'), {
    type: 'line',
    data: { labels: @json($label), datasets: [ds('% terlambat', @json($persenBln), 3)] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0 } } }
});

new Chart(document.getElementById('cKategori'), {
    type: 'bar',
    data: {
        labels: @json($kat->pluck('kategori')),
        datasets: [ds('% terlambat', @json($kat->pluck('persen')), 1)]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0 } } }
});
</script>
@endsection
