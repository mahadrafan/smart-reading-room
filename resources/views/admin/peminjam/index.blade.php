@extends('layouts.admin')

@section('title', 'Data Peminjam')

@section('content')
    <div class="kepala">
        <h1>Data Peminjam</h1>
        <p class="ket">Kontak pengguna untuk kebutuhan administrasi dan peringatan pengembalian.</p>
    </div>
    <div class="kotak">
        <table class="tabel">
            <thead><tr><th>NIS/NIP</th><th>Nama</th><th>Kelas</th><th>Email</th><th>No. telepon</th><th>Pinjaman aktif</th><th>Terlambat</th></tr></thead>
            <tbody>
                @forelse ($peminjam as $user)
                    <tr>
                        <td>{{ $user->nim_nip }}</td><td>{{ $user->name }}</td><td>{{ $user->class ?: '-' }}</td>
                        <td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                        <td><a href="tel:{{ $user->phone }}">{{ $user->phone ?: '-' }}</a></td>
                        <td>{{ $user->pinjaman_aktif }}</td>
                        <td>@if ($user->pinjaman_terlambat)<span class="badge b-tolak">{{ $user->pinjaman_terlambat }}</span>@else 0 @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong-tabel">Belum ada data peminjam.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
