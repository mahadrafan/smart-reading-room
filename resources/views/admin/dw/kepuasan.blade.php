@extends('layouts.admin')

@section('title', 'Dashboard Data Warehouse')

@section('content')
@include('admin.dw._atas')

<div class="grid">
    <div class="panel">
        <h3>Rata-rata rating per kategori</h3>
        <div class="cv"><canvas id="cKategori"></canvas></div>
        <p class="ket">Sumber: basdat_dw · v_ulasan_bulanan. Periode: {{ $periode }}. Satuan: rating 1–5 (SUM rating / SUM ulasan).</p>
    </div>

    <div class="panel">
        <h3>Sebaran ulasan menurut skala rating</h3>
        <div class="cv"><canvas id="cSebaran"></canvas></div>
        <p class="ket">Sumber: basdat_dw · v_rating_sebaran. Periode: {{ $periode }}. Satuan: ulasan.</p>
    </div>
</div>

<div class="panel">
    <h3>Rating tinggi dan rendah per tingkat dan kelas</h3>
    <div class="cv"><canvas id="cKelompok"></canvas></div>
    <table style="margin-top:12px">
        <tr><th>Tingkat · kelas</th><th class="n">Ulasan</th><th class="n">Rata-rata</th><th class="n">% tinggi</th><th class="n">% rendah</th></tr>
        @forelse ($kelompok as $x)
            <tr>
                <td>{{ $x['nama'] }}</td>
                <td class="n">{{ $x['n'] }}</td>
                <td class="n">{{ number_format($x['rata'], 2, ',', '.') }}</td>
                <td class="n">{{ number_format($x['tinggi'], 1, ',', '.') }}</td>
                <td class="n">{{ number_format($x['rendah'], 1, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Tidak ada data untuk filter ini.</td></tr>
        @endforelse
    </table>
    <p class="ket">
        Sumber: basdat_dw · v_ulasan_bulanan. Periode: {{ $periode }}. Satuan: persen ulasan.
        Rating tinggi = 3 sampai 5, rendah = 1 atau 2.
    </p>
</div>

<div class="grid">
    <div class="panel">
        <h3>Buku dengan rating tertinggi (minimal 3 ulasan)</h3>
        <table>
            <tr><th>Judul</th><th>Kategori</th><th class="n">Ulasan</th><th class="n">Rata-rata</th></tr>
            @forelse ($tertinggi as $b)
                <tr>
                    <td>{{ $b->judul }}</td>
                    <td>{{ $b->kategori }}</td>
                    <td class="n">{{ $b->n }}</td>
                    <td class="n">{{ number_format($b->r / $b->n, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada buku dengan minimal 3 ulasan untuk filter ini.</td></tr>
            @endforelse
        </table>
        <p class="ket">Sumber: basdat_dw · v_ulasan_buku. Periode: {{ $periode }}. Satuan: rating 1–5.</p>
    </div>

    <div class="panel">
        <h3>Buku dengan rating rendah terbanyak</h3>
        <table>
            <tr><th>Judul</th><th>Kategori</th><th class="n">Rating rendah</th><th class="n">% rendah</th><th class="n">Rata-rata</th></tr>
            @forelse ($rendah as $b)
                <tr>
                    <td>{{ $b->judul }}</td>
                    <td>{{ $b->kategori }}</td>
                    <td class="n">{{ $b->w }}</td>
                    <td class="n">{{ number_format(100 * $b->w / $b->n, 1, ',', '.') }}</td>
                    <td class="n">{{ number_format($b->r / $b->n, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada rating rendah untuk filter ini.</td></tr>
            @endforelse
        </table>
        <p class="ket">Sumber: basdat_dw · v_ulasan_buku. Periode: {{ $periode }}. Satuan: ulasan dan persen.</p>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h3>Sering dipinjam vs rating</h3>
        <div class="cv"><canvas id="cBanding"></canvas></div>
        <p class="ket">
            Sumber: basdat_dw · v_buku_populer (DW 1) dan v_ulasan_buku (DW 2), dicocokkan lewat book_id.
            Periode: {{ $periode }}. Satu titik = satu buku; sumbu X peminjaman berhasil, sumbu Y rata-rata rating.
        </p>
    </div>

    <div class="panel">
        <h3>Daftar dipinjam vs rating (15 teratas)</h3>
        <table>
            <tr><th>Judul</th><th class="n">Dipinjam</th><th class="n">Ulasan</th><th class="n">Rata-rata</th></tr>
            @foreach ($banding->take(15) as $b)
                <tr>
                    <td>{{ $b['judul'] }}</td>
                    <td class="n">{{ $b['dipinjam'] }}</td>
                    <td class="n">{{ $b['n'] }}</td>
                    <td class="n">{{ $b['rata'] === null ? '–' : number_format($b['rata'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>
        <p class="ket">Sumber: sama dengan panel di sebelahnya. Satuan: peminjaman, ulasan, rating 1–5.</p>
    </div>
</div>

@include('admin.dw._bawah')
<script>
new Chart(document.getElementById('cKategori'), {
    type: 'bar',
    data: { labels: @json($katLabel), datasets: [ds('Rata-rata rating', @json($katRata), 0)] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 5 } } }
});

new Chart(document.getElementById('cSebaran'), {
    type: 'bar',
    data: {
        labels: @json($sebaran->map(fn ($s) => $s->nilai . ' · ' . $s->label_rating)->values()),
        datasets: [ds('Ulasan', @json($sebaran->pluck('n')->map(fn ($v) => (int) $v)->values()), 1)]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('cKelompok'), {
    type: 'bar',
    data: {
        labels: @json($kelompok->pluck('nama')),
        datasets: [
            ds('% rating tinggi', @json($kelompok->pluck('tinggi')), 2),
            ds('% rating rendah', @json($kelompok->pluck('rendah')), 3)
        ]
    },
    options: { maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, max: 100 } } }
});

const titik = @json($banding->filter(fn ($b) => $b['rata'] !== null)->map(fn ($b) => ['x' => $b['dipinjam'], 'y' => $b['rata'], 'judul' => $b['judul']])->values());
new Chart(document.getElementById('cBanding'), {
    type: 'scatter',
    data: { datasets: [ds('Buku', titik, 0)] },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => c.raw.judul + ' (' + c.raw.x + ' dipinjam, rating ' + c.raw.y + ')' } }
        },
        scales: { x: { title: { display: true, text: 'Peminjaman berhasil' } }, y: { min: 0, max: 5, title: { display: true, text: 'Rata-rata rating' } } }
    }
});
</script>
@endsection
