@extends('layouts.admin')

@section('title', 'Dashboard Data Warehouse')

@section('content')
@include('admin.dw._atas')

<div class="kpi">
    <div>
        <span>Peminjaman berhasil</span>
        <b>{{ number_format($kpi['berhasil'], 0, ',', '.') }}</b>
        <small>pengajuan berstatus Dipinjam / Dikembalikan</small>
    </div>
    <div>
        <span>Persentase berhasil</span>
        <b>{{ number_format($kpi['persen_berhasil'], 1, ',', '.') }}%</b>
        <small>berhasil / seluruh pengajuan</small>
    </div>
    <div>
        <span>Rata-rata rating</span>
        <b>{{ number_format($kpi['rata_rating'], 2, ',', '.') }}</b>
        <small>skala 1–5, SUM rating / SUM ulasan</small>
    </div>
    <div>
        <span>Persentase terlambat</span>
        <b>{{ number_format($kpi['persen_terlambat'], 1, ',', '.') }}%</b>
        <small>terlambat / kewajiban kembali</small>
    </div>
    <div>
        <span>Total denda</span>
        <b>Rp {{ number_format($kpi['denda'], 0, ',', '.') }}</b>
        <small>hari terlambat × Rp1.000</small>
    </div>
</div>

<div class="panel">
    <h3>Gabungan tiga DW per bulan</h3>
    <div class="cv"><canvas id="cGabungan"></canvas></div>
    <p class="ket">
        Sumber: basdat_dw · v_ringkasan_bulanan (dim_waktu bersama). Periode: {{ $periode }}.
        Satuan: kejadian per bulan. Bulan tiap garis mengikuti perannya: pengajuan (DW 1),
        ulasan (DW 2), jatuh tempo (DW 3).
    </p>
</div>

@include('admin.dw._bawah')
<script>
new Chart(document.getElementById('cGabungan'), {
    type: 'line',
    data: {
        labels: @json($label),
        datasets: [
            ds('Pengajuan (DW 1)', @json($pengajuan), 0),
            ds('Ulasan (DW 2)', @json($ulasan), 2),
            ds('Terlambat (DW 3)', @json($terlambat), 3)
        ]
    },
    options: { maintainAspectRatio: false }
});
</script>
@endsection