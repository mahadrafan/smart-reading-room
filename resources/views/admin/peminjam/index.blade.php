@extends('layouts.admin')

@section('title', 'Data Peminjam')

@section('content')
    <div class="kepala">
        <h1>Data Peminjam</h1>
        <p class="ket">Kontak pengguna untuk kebutuhan administrasi dan peringatan pengembalian.</p>
    </div>

    <form method="GET" action="{{ route('admin.peminjam.index') }}" class="cari-admin">
        <label for="q" class="sr-only">Cari peminjam</label>
        <input type="search" id="q" name="q" value="{{ $cari }}" placeholder="Cari NIS/NIP, nama, kelas, atau jabatan">
        <button type="submit" class="tombol tombol-kecil">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('admin.peminjam.index') }}" class="hapus-semua-filter">Hapus</a>
        @endif
    </form>

    <p class="ringkasan-hasil">
        @if ($peminjam->total() > 0)
            Menampilkan {{ $peminjam->firstItem() }}–{{ $peminjam->lastItem() }} dari <strong>{{ $peminjam->total() }}</strong>
            peminjam{{ $cari !== '' ? ' yang cocok (total ' . $jumlahTotal . ')' : '' }}
        @endif
    </p>

    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>NIS/NIP</th><th>Nama</th><th>Jenis</th><th>Kelas / jabatan</th><th>Email</th><th>No. telepon</th><th>Pinjaman aktif</th><th>Terlambat</th></tr></thead>
            <tbody>
                @forelse ($peminjam as $user)
                    <tr>
                        <td>{{ $user->nim_nip }}</td><td>{{ $user->name }}</td><td>{{ $user->borrower_type ?: '-' }}</td><td>{{ $user->keterangan ?: '-' }}</td>
                        <td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                        <td>@include('admin._telepon', ['user' => $user])</td>
                        <td>{{ $user->pinjaman_aktif }}</td>
                        <td>@if ($user->pinjaman_terlambat)<span class="badge b-tolak">{{ $user->pinjaman_terlambat }}</span>@else 0 @endif</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="kosong-tabel">
                            @if ($cari !== '')
                                Tidak ada peminjam dengan NIS/NIP, nama, kelas, atau jabatan yang cocok dengan “{{ $cari }}”.
                                <a href="{{ route('admin.peminjam.index') }}">Tampilkan semua</a>
                            @else
                                Belum ada data peminjam.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin._halaman', ['halaman' => $peminjam])
@endsection
