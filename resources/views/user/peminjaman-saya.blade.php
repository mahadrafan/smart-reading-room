@extends('layouts.user')

@section('title', 'Peminjaman Saya')

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Peminjaman Saya</h1>
            <p class="ket">Pantau status pengajuan dan buku yang sedang kamu pinjam.</p>
        </div>
        <a href="{{ route('peminjaman.buat') }}" class="tombol">Ajukan Peminjaman</a>
    </div>

    <div class="chip-baris">
        <a href="{{ route('peminjaman.index') }}" class="chip {{ $filter ? '' : 'aktif' }}">Semua</a>
        <a href="{{ route('peminjaman.index', ['filter' => 'aktif']) }}" class="chip {{ $filter == 'aktif' ? 'aktif' : '' }}">Aktif</a>
        <a href="{{ route('peminjaman.index', ['filter' => 'riwayat']) }}" class="chip {{ $filter == 'riwayat' ? 'aktif' : '' }}">Riwayat</a>
    </div>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No. peminjaman</th>
                    <th>Judul buku</th>
                    <th>Diajukan</th>
                    <th>Tanggal pinjam</th>
                    <th>Batas kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td>{{ $p->kode }}</td>
                        <td>{{ $p->book ? $p->book->title : '-' }}</td>
                        <td>{{ $p->request_date ? $p->request_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $p->loan_date ? $p->loan_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $p->due_date ? $p->due_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            @include('user._status', ['pinjam' => $p])
                            @if ($p->hari_terlambat > 0)
                                <div class="pesan-salah">Denda Rp{{ number_format($p->jumlah_denda, 0, ',', '.') }}</div>
                                <span class="badge {{ $p->denda_lunas ? 'b-setuju' : 'b-tolak' }}">{{ $p->denda_lunas ? 'Lunas' : 'Belum Lunas' }}</span>
                            @endif
                        </td>
                        <td class="aksi">
                            <a href="{{ route('peminjaman.detail', $p->loan_id) }}" class="tautan">Detail</a>
                            @if ($p->status == 'Menunggu')
                                <form method="POST" action="{{ route('peminjaman.batal', $p->loan_id) }}"
                                      onsubmit="return confirm('Batalkan pengajuan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tautan bahaya">Batalkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tabel">
                            Belum ada data peminjaman.
                            <a href="{{ route('dashboard') }}">Cari buku di katalog</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
