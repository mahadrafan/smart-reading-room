@extends('layouts.admin')

@section('title', 'Kelola Admin')

@section('content')
    <div class="kepala kepala-baris">
        <div>
            <h1>Kelola Admin</h1>
            <p class="ket">Akun petugas perpustakaan yang dapat masuk ke panel admin.</p>
        </div>
        <a href="{{ route('admin.akun.create') }}" class="tombol">Tambah Admin</a>
    </div>

    <form method="GET" action="{{ route('admin.akun.index') }}" class="cari-admin">
        <label for="q" class="sr-only">Cari admin</label>
        <input type="search" id="q" name="q" value="{{ $cari }}" placeholder="Cari NIP/ID, nama, atau email">
        <button type="submit" class="tombol tombol-kecil">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('admin.akun.index') }}" class="hapus-semua-filter">Hapus</a>
        @endif
    </form>

    <p class="ringkasan-hasil">
        @if ($admin->total() > 0)
            Menampilkan {{ $admin->firstItem() }}–{{ $admin->lastItem() }} dari <strong>{{ $admin->total() }}</strong>
            admin{{ $cari !== '' ? ' yang cocok (total ' . $jumlahTotal . ')' : '' }}
        @endif
    </p>

    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>NIP / ID</th><th>Nama</th><th>Email</th><th>No. telepon</th><th>Aktivitas</th><th>Terakhir aktif</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($admin as $akun)
                    <tr>
                        <td>{{ $akun->nim_nip }}</td>
                        <td>
                            {{ $akun->name }}
                            @if ($akun->user_id == Auth::id())
                                <span class="badge b-konfirmasi">Anda</span>
                            @endif
                        </td>
                        <td><a href="mailto:{{ $akun->email }}">{{ $akun->email }}</a></td>
                        <td>@include('admin._telepon', ['user' => $akun])</td>
                        <td>{{ $akun->jumlah_aktivitas }} catatan</td>
                        <td>{{ $akun->aktivitas_terakhir ? \Illuminate\Support\Carbon::parse($akun->aktivitas_terakhir)->format('d/m/Y H:i') : 'Belum ada' }}</td>
                        <td class="aksi"><a href="{{ route('admin.akun.edit', $akun->user_id) }}">{{ $akun->user_id == Auth::id() ? 'Ubah akun saya' : 'Edit' }}</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tabel">
                            Tidak ada admin dengan NIP/ID, nama, atau email yang cocok dengan “{{ $cari }}”.
                            <a href="{{ route('admin.akun.index') }}">Tampilkan semua</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin._halaman', ['halaman' => $admin])

    <p class="pesan-info">
        Akun admin tidak dapat dihapus karena namanya tercatat pada riwayat persetujuan peminjaman, pelunasan denda,
        data buku, dan log aktivitas. Untuk petugas yang sudah tidak bertugas, reset password akunnya.
    </p>
@endsection
