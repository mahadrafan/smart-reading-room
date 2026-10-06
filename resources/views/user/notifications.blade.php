@extends('layouts.user')

@section('title', 'Notifikasi')

@section('content')
    {{-- kepala --}}
    <div class="notif-kepala">
        <div>
            <h1 style="margin:0 0 5px;font-size:26px;font-weight:800;color:var(--coklat-tua)">Notifikasi</h1>
            <p style="margin:0;color:var(--abu);font-size:14px">Riwayat informasi peminjaman bukumu.</p>
        </div>
    </div>

    {{-- filter jenis notifikasi (angka dihitung di controller dari semua notifikasi) --}}
    @if ($jumlahSemua > 0)
        <nav class="notif-tabs" aria-label="Filter jenis notifikasi">
            <a href="{{ route('notifikasi.index') }}"
               class="notif-tab {{ $jenisAktif ? '' : 'aktif' }}"
               @unless ($jenisAktif) aria-current="page" @endunless>
                Semua ({{ $jumlahSemua }})
            </a>
            @foreach ($jumlahJenis as $jenis => $jumlah)
                <a href="{{ route('notifikasi.index', ['jenis' => strtolower($jenis)]) }}"
                   class="notif-tab {{ $jenisAktif == $jenis ? 'aktif' : '' }}"
                   @if ($jenisAktif == $jenis) aria-current="page" @endif>
                    {{ $jenis }} ({{ $jumlah }})
                </a>
            @endforeach
        </nav>
    @endif

    {{-- daftar notifikasi --}}
    @forelse ($notifications as $notif)
        @php
            // ikon dan badge tiap jenis notifikasi, lihat UserNotification::tampilan
            $tipe = $notif->type ?? 'Info';
            $tampil = $notif->tampilan;
            $baru = in_array($notif->notification_id, $idBaru);
        @endphp

        <div class="notif-grup {{ $baru ? 'notif-baru' : '' }}">
            <div class="notifikasi-item">
                <div class="notif-ikon-wrap {{ $tampil['ikonClass'] }}">{{ $tampil['ikon'] }}</div>
                <div class="notif-isi">
                    <div class="notif-judul-baris">
                        <span class="notif-judul">{{ ucfirst($tipe) }}</span>
                        <span class="notif-badge {{ $tampil['badgeClass'] }}">{{ $tampil['badgeLabel'] }}</span>
                        @if ($baru)
                            <span class="notif-badge baru">Baru</span>
                        @endif
                        {{-- waktu relatif, lihat UserNotification::waktu_relatif --}}
                        <span class="notif-waktu">{{ $notif->waktu_relatif }}</span>
                    </div>
                    <p class="notif-pesan">{{ $notif->message }}</p>
                    @if ($notif->loan?->book && $tipe != 'Pengumuman')
                        <div class="notif-aksi">
                            <a href="{{ route('peminjaman.detail', $notif->loan_id) }}" class="notif-aksi-link">Lihat Peminjaman →</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="notif-grup">
            <div style="padding:40px 20px;text-align:center;color:var(--abu)">
                @if ($jenisAktif)
                    Belum ada notifikasi {{ $jenisAktif }}.
                    <a href="{{ route('notifikasi.index') }}">Lihat semua notifikasi</a>
                @else
                    Belum ada notifikasi.
                @endif
            </div>
        </div>
    @endforelse
@endsection
