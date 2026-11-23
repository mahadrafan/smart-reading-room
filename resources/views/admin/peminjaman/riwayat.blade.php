@extends('layouts.admin')

@section('title', 'Riwayat Peminjaman')

@section('content')
    @php
        // filter status, pencarian, & halaman ikut dikirim saat aksi agar kembali ke tampilan yang sama
        $bawaFilter = array_filter(['status' => $status, 'q' => $cari, 'page' => $daftar->currentPage()]);
        // keterangan filter untuk ringkasan & pesan kosong
        $ketStatus = $status === 'Terlambat' ? ' yang terlambat & dendanya belum lunas' : ($status ? ' berstatus ' . $status : '');
        $ketCari = $cari !== '' ? ' yang cocok dengan "' . $cari . '"' : '';
    @endphp

    <div class="kepala">
        <h1>Riwayat & Kelola Peminjaman</h1>
        <p class="ket">Kelola status peminjaman (dikonfirmasi, dipinjam, gagal, atau dikembalikan).</p>
    </div>

    {{-- cari nama / NIS peminjam atau no. peminjaman; filter status yang sedang dibuka tetap dipakai --}}
    <form method="GET" action="{{ route('admin.peminjaman.riwayat') }}" class="filter-ulasan">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <label class="sr-only" for="cari_riwayat">Cari nama peminjam atau no. peminjaman</label>
        <input type="search" id="cari_riwayat" name="q" value="{{ $cari }}" class="cari-input cari-lebar"
               placeholder="Cari nama peminjam atau no. peminjaman..."
               title="Cari berdasarkan nama peminjam, NIS, atau no. peminjaman (mis. LOAN-20261004-030 atau 030)">
        <button type="submit" class="tombol tombol-kecil">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('admin.peminjaman.riwayat', array_filter(['status' => $status])) }}" class="hapus-semua-filter">Hapus pencarian</a>
        @endif
    </form>

    {{-- pilihan status; kata kunci pencarian ikut terbawa --}}
    <div class="chip-baris">
        @foreach (['' => 'Semua', 'Dikonfirmasi' => 'Dikonfirmasi', 'Dipinjam' => 'Dipinjam', 'Terlambat' => 'Terlambat',
                   'Gagal' => 'Gagal', 'Dikembalikan' => 'Dikembalikan'] as $nilai => $label)
            <a href="{{ route('admin.peminjaman.riwayat', array_filter(['status' => $nilai, 'q' => $cari])) }}"
               class="chip {{ (string) $status === $nilai ? 'aktif' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <p class="ringkasan-hasil">
        @if ($daftar->total() > 0)
            Menampilkan {{ $daftar->firstItem() }}–{{ $daftar->lastItem() }} dari <strong>{{ $daftar->total() }}</strong> peminjaman{{ $ketStatus }}{{ $ketCari }}
        @endif
    </p>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No. peminjaman</th>
                    <th>Peminjam</th>
                    <th>Kontak</th>
                    <th>Buku</th>
                    <th>Status</th>
                    <th>Tenggat / Diproses</th>
                    <th>Tgl kembali</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $p->kode }}</td>
                        <td>{{ $p->user->name }} ({{ $p->user->nim_nip }})</td>
                        <td><a href="mailto:{{ $p->user->email }}">{{ $p->user->email }}</a><br>@include('admin._telepon', ['user' => $p->user])</td>
                        <td>{{ $p->book->title }}</td>
                        <td>
                            @include('user._status', ['pinjam' => $p])
                            @if ($p->hari_terlambat > 0)
                                <div class="pesan-salah">{{ $p->hari_terlambat }} hari terlambat<br>Denda Rp{{ number_format($p->jumlah_denda, 0, ',', '.') }}</div>
                                {{-- denda dibayar bersamaan dengan pengembalian (tombol di kolom Aksi); di sini hanya label --}}
                                @if ($p->denda_lunas)
                                    <span class="badge b-setuju" style="margin-top:6px">Lunas</span>
                                @elseif ($p->status == 'Dipinjam')
                                    <span class="badge b-tolak" style="margin-top:6px">Belum Lunas</span>
                                @else
                                    {{-- data lama: sudah dikembalikan tapi dendanya belum dibayar --}}
                                    <form method="POST" action="{{ route('admin.peminjaman.denda', $p->loan_id) }}" style="margin-top:6px"
                                          onsubmit="return confirm('Tandai denda Lunas? Status Lunas tidak bisa diubah lagi.')">
                                        @csrf
                                        @method('PUT')
                                    @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                        <button type="submit" class="tombol tombol-kecil tombol-belum">Tandai Lunas</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                        <td>
                            {{-- Diproses = admin yang terakhir mengubah status (dari admin_logs), bukan selalu yang menyetujui --}}
                            @php $proses = $p->proses_terakhir; @endphp
                            @if ($p->status == 'Dikonfirmasi')
                                <span style="color:#1d4ed8; font-weight:600;">Tenggat Ambil:</span><br>
                                <span class="pesan-info">{{ $p->tenggat_pengambilan ? $p->tenggat_pengambilan->format('d/m/Y H:i') : '-' }}</span>
                                @if ($proses)
                                    <br><span class="pesan-info">Dikonfirmasi oleh {{ $proses['nama'] }}</span>
                                @endif
                            @elseif ($proses)
                                {{ $proses['nama'] }}
                                <br><span class="pesan-info">{{ $proses['aksi'] }}@if ($proses['waktu']) · {{ $proses['waktu']->format('d/m/Y H:i') }}@endif</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $p->return_date ? \Illuminate\Support\Carbon::parse($p->return_date)->format('d/m/Y') : '-' }}</td>
                        <td class="aksi">
                            @if ($p->status == 'Dikonfirmasi')
                                <form method="POST" action="{{ route('admin.peminjaman.dipinjam', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Tandai bahwa buku telah diambil manual oleh user di perpus?');">
                                    @csrf
                                    @method('PUT')
                                    @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                    <button type="submit">Tandai Dipinjam</button>
                                </form>
                            @elseif ($p->status == 'Dipinjam' && $p->jumlah_denda > 0)
                                {{-- buku terlambat: pengembalian dan pelunasan denda selalu bersamaan --}}
                                @php $teksDenda = 'Rp' . number_format($p->jumlah_denda, 0, ',', '.'); @endphp
                                <form method="POST" action="{{ route('admin.peminjaman.denda', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm({{ Js::from('Buku dikembalikan dan denda ' . $teksDenda . ' sudah dibayar? Status Lunas tidak bisa diubah lagi.') }});">
                                    @csrf
                                    @method('PUT')
                                    @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                    <button type="submit" class="bahaya">Kembalikan &amp; Lunasi Denda ({{ $teksDenda }})</button>
                                </form>
                            @elseif ($p->status == 'Dipinjam')
                                <form method="POST" action="{{ route('admin.peminjaman.kembalikan', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Tandai bahwa buku sudah dikembalikan oleh user ke perpus?');">
                                    @csrf
                                    @method('PUT')
                                    @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                    <button type="submit">Tandai Kembali</button>
                                </form>
                            @elseif ($p->jumlah_denda > 0 && ! $p->denda_lunas)
                                <span class="aksi-mati" title="Dendanya belum lunas, jadi riwayat ini belum bisa dihapus">Hapus</span>
                            @elseif ($p->status == 'Dikembalikan' || $p->status == 'Gagal')
                                <form method="POST" action="{{ route('admin.peminjaman.destroy', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Hapus riwayat peminjaman ini?');">
                                    @csrf
                                    @method('DELETE')
                                    @include('admin._bawa-filter', ['bawa' => $bawaFilter])
                                    <button type="submit" class="bahaya">Hapus</button>
                                </form>
                            @else
                                <span class="pesan-info">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong-tabel">{{ $status || $cari !== '' ? 'Tidak ada peminjaman' . $ketStatus . $ketCari . '.' : 'Belum ada data peminjaman di riwayat.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin._halaman', ['halaman' => $daftar])
@endsection
