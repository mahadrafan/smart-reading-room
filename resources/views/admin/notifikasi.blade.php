@extends('layouts.admin')

@section('title', 'Notifikasi')

@section('content')
    <div class="kepala">
        <h1>Notifikasi</h1>
        <p class="ket">Pengajuan peminjaman baru dan pengingat harian denda yang belum lunas.</p>
    </div>

    {{-- filter jenis notifikasi (angka dihitung di controller dari semua notifikasi) --}}
    <div class="chip-baris">
        <a href="{{ route('admin.notifikasi.index') }}" class="chip {{ $jenisAktif ? '' : 'aktif' }}">Semua ({{ $jumlahSemua }})</a>
        @foreach ($jumlahJenis as $jenis => $jumlah)
            <a href="{{ route('admin.notifikasi.index', ['jenis' => strtolower($jenis)]) }}"
               class="chip {{ $jenisAktif == $jenis ? 'aktif' : '' }}">{{ $jenis }} ({{ $jumlah }})</a>
        @endforeach
    </div>

    @forelse ($notifications as $notif)
        @php
            // ikon dan badge tiap jenis notifikasi, lihat UserNotification::tampilan
            $tampil = $notif->tampilan;
            $baru = in_array($notif->notification_id, $idBaru);
            $pinjam = $notif->loan;
        @endphp

        <div class="notif-grup {{ $baru ? 'notif-baru' : '' }}">
            <div class="notifikasi-item">
                <div class="notif-ikon-wrap {{ $tampil['ikonClass'] }}" aria-hidden="true">{{ $tampil['ikon'] }}</div>
                <div class="notif-isi">
                    <div class="notif-judul-baris">
                        <span class="notif-judul">{{ $notif->type == 'Pengajuan' ? 'Pengajuan baru' : ucfirst($notif->type ?? 'Info') }}</span>
                        @if ($notif->type == 'Terlambat')
                            <span class="notif-badge penting">Denda belum lunas</span>
                        @endif
                        @if ($baru)
                            <span class="notif-badge baru">Baru</span>
                        @endif
                        <span class="notif-waktu">{{ $notif->waktu_relatif }}</span>
                    </div>
                    <p class="notif-pesan">{{ $notif->message }}</p>
                    {{-- tautan ke tempat peminjaman diproses; tidak ada bila peminjamannya sudah dibatalkan / dihapus --}}
                    @if ($pinjam)
                        <div class="notif-aksi">
                            @if ($pinjam->status == 'Menunggu')
                                <a href="{{ route('admin.peminjaman.index', ['q' => $pinjam->user->nim_nip ?? '']) }}" class="notif-aksi-link">Proses di Request Masuk →</a>
                            @else
                                <a href="{{ route('admin.peminjaman.riwayat', ['q' => $pinjam->kode]) }}" class="notif-aksi-link">Lihat di Riwayat ({{ $pinjam->status }}) →</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="kotak kosong-tabel">
            {{ $jenisAktif ? 'Belum ada notifikasi ' . $jenisAktif . '.' : 'Belum ada notifikasi.' }}
        </div>
    @endforelse
@endsection
