@extends('layouts.admin')

@section('title', 'Request Masuk')

@section('content')
    <div class="kepala">
        <h1>Request Masuk</h1>
        <p class="ket">Pengajuan peminjaman yang menunggu persetujuan.</p>
    </div>

    <div class="kotak">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No. peminjaman</th>
                    <th>Peminjam</th>
                    <th>Kontak</th>
                    <th>Buku</th>
                    <th>Diajukan</th>
                    <th>Tgl pinjam</th>
                    <th>Batas kembali</th>
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
                        <td>{{ $p->request_date ? $p->request_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $p->loan_date ? \Illuminate\Support\Carbon::parse($p->loan_date)->format('d/m/Y') : '-' }}</td>
                        <td>{{ $p->due_date ? \Illuminate\Support\Carbon::parse($p->due_date)->format('d/m/Y') : '-' }}</td>
                        <td class="aksi">
                            <form method="POST" action="{{ route('admin.peminjaman.setujui', $p->loan_id) }}" style="display:inline"
                                  onsubmit="return confirm('Konfirmasi pengajuan peminjaman ini?');">
                                @csrf
                                @method('PUT')
                                <button type="submit">Konfirmasi</button>
                            </form>
                            <form method="POST" action="{{ route('admin.peminjaman.tolak', $p->loan_id) }}" style="display:inline"
                                  onsubmit="return confirm('Tolak pengajuan ini? Status akan menjadi Gagal.');">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="bahaya">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kosong-tabel">Tidak ada pengajuan yang menunggu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
