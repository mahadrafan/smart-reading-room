@extends('layouts.user')

@section('title', 'Notifikasi')

@section('content')
    {{-- kepala --}}
    <div class="notif-kepala">
        <div>
            <h1 style="margin:0 0 5px;font-size:26px;font-weight:800;color:var(--navy)">Notifikasi</h1>
            <p style="margin:0;color:var(--abu);font-size:14px">Riwayat informasi peminjaman yang juga dikirim ke emailmu.</p>
        </div>
        <button class="tombol-tandai">✔ Tandai Semua Dibaca</button>
    </div>

    {{-- tab filter --}}
    @php
        $tabAktif   = request('tab', 'semua');
        $jmlPinjam  = $notifications->whereIn('type', ['Pengajuan','Dipinjam','Dikembalikan','Gagal','Terlambat','Perpanjangan'])->count();
        $jmlPengum  = $notifications->where('type', 'Pengumuman')->count();
    @endphp
    <div class="notif-tabs">
        <a href="{{ route('notifikasi.index') }}"
           class="notif-tab {{ $tabAktif == 'semua' ? 'aktif' : '' }}">
            Semua ({{ $notifications->count() }})
        </a>
        <a href="{{ route('notifikasi.index', ['tab' => 'peminjaman']) }}"
           class="notif-tab {{ $tabAktif == 'peminjaman' ? 'aktif' : '' }}">
            Peminjaman ({{ $jmlPinjam }})
        </a>
        <a href="{{ route('notifikasi.index', ['tab' => 'pengumuman']) }}"
           class="notif-tab {{ $tabAktif == 'pengumuman' ? 'aktif' : '' }}">
            Pengumuman ({{ $jmlPengum }})
        </a>
    </div>

    {{-- daftar notifikasi --}}
    @forelse ($notifications as $notif)
        @php
            // tentukan ikon dan warna berdasarkan type
            $tipe = $notif->type ?? 'Info';
            if (in_array($tipe, ['Terlambat', 'Gagal'])) {
                $ikonClass = 'notif-ikon-peringatan';
                $ikon = '📅';
                $badgeClass = 'penting';
                $badgeLabel = $tipe == 'Terlambat' ? 'Penting' : 'Gagal';
            } elseif (in_array($tipe, ['Dikembalikan', 'Dipinjam'])) {
                $ikonClass = 'notif-ikon-sukses';
                $ikon = '✅';
                $badgeClass = 'selesai';
                $badgeLabel = 'Selesai';
            } elseif ($tipe == 'Pengumuman') {
                $ikonClass = 'notif-ikon-info';
                $ikon = '📚';
                $badgeClass = 'pengumuman';
                $badgeLabel = 'Pengumuman';
            } else {
                $ikonClass = 'notif-ikon-info';
                $ikon = '📋';
                $badgeClass = 'pengumuman';
                $badgeLabel = $tipe;
            }

            // waktu relatif (detik -> menit+detik -> jam+menit -> hari), lihat UserNotification::waktu_relatif
            $waktuLabel = $notif->waktu_relatif;
        @endphp

        <div class="notif-grup">
            <div class="notifikasi-item">
                <div class="notif-ikon-wrap {{ $ikonClass }}">{{ $ikon }}</div>
                <div class="notif-isi">
                    <div class="notif-judul-baris">
                        <span class="notif-judul">{{ $notif->subject ?? ucfirst($tipe) }}</span>
                        <span class="notif-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        <span class="notif-waktu">{{ $waktuLabel }}</span>
                    </div>
                    <p class="notif-pesan">{{ $notif->message }}</p>
                    <div class="notif-aksi">
                        @if ($tipe == 'Terlambat' && $notif->loan_id)
                            <a href="{{ route('peminjaman.detail', $notif->loan_id) }}" class="notif-aksi-btn">🔄 Perpanjang Sekarang</a>
                            <a href="{{ route('peminjaman.detail', $notif->loan_id) }}" class="notif-aksi-link">Detail Peminjaman</a>
                        @elseif ($notif->loan?->book && $tipe != 'Pengumuman')
                            <a href="{{ route('peminjaman.detail', $notif->loan_id) }}" class="notif-aksi-link">Lihat Peminjaman →</a>
                        @elseif ($tipe == 'Pengumuman')
                            <a href="{{ route('ebooks.index') }}" class="notif-tautan">Buka Koleksi E-book →</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="notif-grup">
            <div style="padding:40px 20px;text-align:center;color:var(--abu)">
                Belum ada notifikasi.
            </div>
        </div>
    @endforelse

    {{-- pengaturan notifikasi --}}
    <div class="notif-pengaturan">
        <div class="notif-pengaturan-kiri">
            <div class="notif-pengaturan-ikon">✉️</div>
            <div class="notif-pengaturan-teks">
                <div class="judul">Pengaturan Notifikasi</div>
                <div class="sub">Kirim salinan notifikasi ke email
                    ({{ Auth::user()->email }})
                </div>
            </div>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" checked>
            <span class="toggle-slider"></span>
        </label>
    </div>
@endsection
