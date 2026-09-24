@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="kepala">
        <h1>Dashboard Admin</h1>
        <p class="ket">Ringkasan data Smart Reading Room saat ini.</p>
    </div>

    <div class="ringkasan">
        <div class="kartu-ringkas {{ $jumlahMenunggu > 0 ? 'soroti' : '' }}">
            <span class="angka">{{ $jumlahMenunggu }}</span>
            <span class="label">Peminjaman menunggu ACC</span>
        </div>
        <div class="kartu-ringkas">
            <span class="angka">{{ $jumlahBuku }}</span>
            <span class="label">Buku aktif di katalog</span>
        </div>
        <div class="kartu-ringkas {{ $jumlahTerlambat > 0 ? 'soroti' : '' }}">
            <span class="angka">{{ $jumlahTerlambat }}</span>
            <span class="label">Peminjaman terlambat</span>
        </div>
        <div class="kartu-ringkas">
            <span class="angka">{{ $jumlahKategori }}</span>
            <span class="label">Kategori</span>
        </div>
        <div class="kartu-ringkas">
            <span class="angka">{{ $jumlahPenulis }}</span>
            <span class="label">Penulis</span>
        </div>
        <div class="kartu-ringkas">
            <span class="angka">{{ $jumlahPeminjam }}</span>
            <span class="label">Akun peminjam terdaftar</span>
        </div>
    </div>

    <div class="kepala-baris">
        <div class="kepala">
            <h2>Peminjaman Terlambat</h2>
            <p class="ket">Kontak peminjam dan denda berjalan untuk tindak lanjut admin.</p>
        </div>
        <a href="{{ route('admin.peminjam.index') }}" class="tombol">Lihat semua peminjam</a>
    </div>

    <div class="kotak" style="margin-bottom:20px;">
        <table class="tabel">
            <thead><tr><th>Peminjam</th><th>Kontak</th><th>Buku</th><th>Terlambat</th><th>Denda</th><th>Pembayaran</th></tr></thead>
            <tbody>
                @forelse ($terlambat as $p)
                    <tr>
                        <td><strong>{{ $p->user->name }}</strong><br><span class="pesan-info">{{ $p->user->nim_nip }}</span></td>
                        <td><a href="mailto:{{ $p->user->email }}">{{ $p->user->email }}</a><br><a href="tel:{{ $p->user->phone }}">{{ $p->user->phone ?: '-' }}</a></td>
                        <td>{{ $p->book->title }}</td>
                        <td><span class="badge b-tolak">{{ $p->hari_terlambat }} hari</span><br><span class="pesan-info">Tenggat {{ $p->due_date->format('d/m/Y') }}</span></td>
                        <td><strong>Rp{{ number_format($p->jumlah_denda, 0, ',', '.') }}</strong></td>
                        <td>
                            <form method="POST" action="{{ route('admin.peminjaman.denda', $p->loan_id) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="tombol tombol-kecil {{ $p->denda_lunas ? 'tombol-lunas' : 'tombol-belum' }}"
                                    onclick="return confirm('Ubah status denda menjadi {{ $p->denda_lunas ? 'Belum Lunas' : 'Lunas' }}?')">
                                    {{ $p->denda_lunas ? 'Lunas' : 'Belum Lunas' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong-tabel">Tidak ada peminjaman yang terlambat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="kotak">
        <p style="margin:0; color: var(--abu);">
            Selamat datang di Panel Admin <strong>Smart Reading Room</strong>. Gunakan menu di sebelah kiri untuk mengelola katalog buku, memproses pengajuan peminjaman, dan melihat riwayat perpustakaan.
        </p>
    </div>
@endsection
