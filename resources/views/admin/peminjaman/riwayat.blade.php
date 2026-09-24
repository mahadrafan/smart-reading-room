@extends('layouts.admin')

@section('title', 'Riwayat Peminjaman')

@section('content')
    <div class="kepala">
        <h1>Riwayat & Kelola Peminjaman</h1>
        <p class="ket">Kelola status peminjaman (dikonfirmasi, dipinjam, gagal, atau dikembalikan).</p>
    </div>

    <div class="chip-baris">
        <a href="{{ route('admin.peminjaman.riwayat') }}" class="chip {{ $status ? '' : 'aktif' }}">Semua</a>
        <a href="{{ route('admin.peminjaman.riwayat', ['status' => 'Dikonfirmasi']) }}" class="chip {{ $status == 'Dikonfirmasi' ? 'aktif' : '' }}">Dikonfirmasi</a>
        <a href="{{ route('admin.peminjaman.riwayat', ['status' => 'Dipinjam']) }}" class="chip {{ $status == 'Dipinjam' ? 'aktif' : '' }}">Dipinjam</a>
        <a href="{{ route('admin.peminjaman.riwayat', ['status' => 'Gagal']) }}" class="chip {{ $status == 'Gagal' ? 'aktif' : '' }}">Gagal</a>
        <a href="{{ route('admin.peminjaman.riwayat', ['status' => 'Dikembalikan']) }}" class="chip {{ $status == 'Dikembalikan' ? 'aktif' : '' }}">Dikembalikan</a>
    </div>

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
                        <td><a href="mailto:{{ $p->user->email }}">{{ $p->user->email }}</a><br><a href="tel:{{ $p->user->phone }}">{{ $p->user->phone ?: '-' }}</a></td>
                        <td>{{ $p->book->title }}</td>
                        <td>
                            @include('user._status', ['pinjam' => $p])
                            @if ($p->hari_terlambat > 0)
                                <div class="pesan-salah">{{ $p->hari_terlambat }} hari terlambat<br>Denda Rp{{ number_format($p->jumlah_denda, 0, ',', '.') }}</div>
                                <form method="POST" action="{{ route('admin.peminjaman.denda', $p->loan_id) }}" style="margin-top:6px">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="tombol tombol-kecil {{ $p->denda_lunas ? 'tombol-lunas' : 'tombol-belum' }}"
                                        onclick="return confirm('Ubah status denda menjadi {{ $p->denda_lunas ? 'Belum Lunas' : 'Lunas' }}?')">
                                        {{ $p->denda_lunas ? 'Lunas' : 'Belum Lunas' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if ($p->status == 'Dikonfirmasi')
                                <span style="color:#1d4ed8; font-weight:600;">Tenggat Ambil:</span><br>
                                <span class="pesan-info">{{ $p->tenggat_pengambilan ? $p->tenggat_pengambilan->format('d/m/Y H:i') : '-' }}</span>
                            @else
                                {{ $p->approver ? $p->approver->name : '-' }}
                                @if ($p->approved_at)
                                    <br><span class="pesan-info">{{ $p->approved_at->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                        </td>
                        <td>{{ $p->return_date ? \Illuminate\Support\Carbon::parse($p->return_date)->format('d/m/Y') : '-' }}</td>
                        <td class="aksi">
                            @if ($p->status == 'Dikonfirmasi')
                                <form method="POST" action="{{ route('admin.peminjaman.dipinjam', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Tandai bahwa buku telah diambil manual oleh user di perpus?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit">Tandai Dipinjam</button>
                                </form>
                            @elseif ($p->status == 'Dipinjam')
                                <form method="POST" action="{{ route('admin.peminjaman.kembalikan', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Tandai bahwa buku sudah dikembalikan oleh user ke perpus?');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit">Tandai Kembali</button>
                                </form>
                            @elseif ($p->status == 'Dikembalikan' || $p->status == 'Gagal')
                                <form method="POST" action="{{ route('admin.peminjaman.destroy', $p->loan_id) }}" style="display:inline"
                                      onsubmit="return confirm('Hapus riwayat peminjaman ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bahaya">Hapus</button>
                                </form>
                            @else
                                <span class="pesan-info">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong-tabel">Belum ada data peminjaman di riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
