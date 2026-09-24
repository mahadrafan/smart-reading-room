@extends('layouts.user')

@section('title', 'Detail Peminjaman')

@section('content')
    <a href="{{ route('peminjaman.index') }}" class="kembali">&larr; Kembali ke peminjaman saya</a>

    <div class="kepala">
        <h1>Detail Peminjaman</h1>
        <p class="ket">{{ $pinjam->kode }} @include('user._status', ['pinjam' => $pinjam])</p>
    </div>

    <div class="detail-buku">
        <div class="detail-sampul">
            @if ($pinjam->book)
                @include('user._sampul', ['b' => $pinjam->book])
            @endif
        </div>

        <div class="detail-info">
            <h2 class="judul-kecil">Buku yang dipinjam</h2>
            <table class="tabel-info">
                <tr><th>Judul</th><td>{{ $pinjam->book ? $pinjam->book->title : '-' }}</td></tr>
                <tr><th>Penulis</th><td>{{ ($pinjam->book && $pinjam->book->author) ? $pinjam->book->author->author_name : '-' }}</td></tr>
                <tr><th>Kategori</th><td>{{ ($pinjam->book && $pinjam->book->category) ? $pinjam->book->category->category_name : '-' }}</td></tr>
                <tr><th>Lokasi rak</th><td>{{ $pinjam->book ? ($pinjam->book->location ?: '-') : '-' }}</td></tr>
            </table>

            <h2 class="judul-kecil">Informasi peminjaman</h2>
            <table class="tabel-info">
                <tr><th>Diajukan pada</th><td>{{ $pinjam->request_date ? $pinjam->request_date->format('d/m/Y H:i') : '-' }}</td></tr>
                @if ($pinjam->status == 'Dikonfirmasi' || $pinjam->tenggat_pengambilan)
                    <tr>
                        <th>Tenggat pengambilan</th>
                        <td>
                            <strong style="color: #1d4ed8;">
                                {{ $pinjam->tenggat_pengambilan ? $pinjam->tenggat_pengambilan->format('d/m/Y H:i') : '-' }}
                            </strong>
                            <span class="pesan-info" style="display:block; font-size:12px;">(2 hari sejak dikonfirmasi admin)</span>
                        </td>
                    </tr>
                @endif
                <tr><th>Tanggal pinjam</th><td>{{ $pinjam->loan_date ? $pinjam->loan_date->format('d/m/Y') : '-' }}</td></tr>
                <tr><th>Batas pengembalian</th><td>{{ $pinjam->due_date ? $pinjam->due_date->format('d/m/Y') : '-' }}</td></tr>
                <tr><th>Tanggal dikembalikan</th><td>{{ $pinjam->return_date ? $pinjam->return_date->format('d/m/Y') : '-' }}</td></tr>
                @if ($pinjam->hari_terlambat > 0)
                    <tr><th>Keterlambatan</th><td><strong style="color:#d12b3d">{{ $pinjam->hari_terlambat }} hari</strong></td></tr>
                    <tr><th>Denda berjalan</th><td><strong style="color:#d12b3d">Rp{{ number_format($pinjam->jumlah_denda, 0, ',', '.') }}</strong><br><span class="pesan-info">Rp{{ number_format(config('library.fine_per_day'), 0, ',', '.') }} per hari</span></td></tr>
                    <tr><th>Status pembayaran</th><td><span class="badge {{ $pinjam->denda_lunas ? 'b-setuju' : 'b-tolak' }}">{{ $pinjam->denda_lunas ? 'Lunas' : 'Belum Lunas' }}</span></td></tr>
                @endif
                <tr>
                    <th>Diproses oleh</th>
                    <td>
                        @if ($pinjam->approver)
                            {{ $pinjam->approver->name }}
                            @if ($pinjam->approved_at)
                                ({{ $pinjam->approved_at->format('d/m/Y H:i') }})
                            @endif
                        @else
                            Belum diproses admin
                        @endif
                    </td>
                </tr>
            </table>

            @if ($pinjam->status == 'Dikonfirmasi')
                <div class="alert" style="background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; margin-top: 15px;">
                    📌 <strong>Peminjaman Dikonfirmasi!</strong> Silakan ambil buku di perpustakaan sebelum
                    <strong>{{ $pinjam->tenggat_pengambilan ? $pinjam->tenggat_pengambilan->format('d/m/Y H:i') : '-' }}</strong>.
                    Jika buku tidak diambil hingga tenggat 2 hari berakhir, status otomatis menjadi <strong>Gagal</strong> dan stok akan dikembalikan.
                </div>
            @endif

            @if ($pinjam->hari_terlambat > 0)
                <div class="alert alert-galat">
                    Pengembalian sudah terlambat {{ $pinjam->hari_terlambat }} hari. Denda sementara
                    <strong>Rp{{ number_format($pinjam->jumlah_denda, 0, ',', '.') }}</strong>.
                    Segera kembalikan buku atau hubungi perpustakaan.
                </div>
            @endif

            @if ($pinjam->status == 'Menunggu')
                <form method="POST" action="{{ route('peminjaman.batal', $pinjam->loan_id) }}"
                      onsubmit="return confirm('Batalkan pengajuan ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tombol tombol-bahaya">Batalkan Pengajuan</button>
                </form>
            @endif
        </div>
    </div>
@endsection
