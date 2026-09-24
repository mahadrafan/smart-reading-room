@extends('layouts.user')

@section('title', 'Notifikasi')

@section('content')
    <div class="kepala">
        <h1>Notifikasi</h1>
        <p class="ket">Riwayat informasi peminjaman yang juga dikirim ke emailmu.</p>
    </div>

    <div class="daftar-notifikasi">
        @forelse ($notifications as $notification)
            <article class="kotak notifikasi-item">
                <div>
                    <span class="badge {{ $notification->type === 'Terlambat' ? 'b-tolak' : 'b-konfirmasi' }}">{{ $notification->type }}</span>
                    <p>{{ $notification->message }}</p>
                    @if ($notification->loan?->book)
                        <a href="{{ route('peminjaman.detail', $notification->loan_id) }}">Lihat peminjaman</a>
                    @endif
                </div>
                <small class="pesan-info">{{ $notification->sent_at?->format('d/m/Y H:i') }} · Email {{ strtolower($notification->status ?? '-') }}</small>
            </article>
        @empty
            <div class="kotak"><p class="pesan-info">Belum ada notifikasi.</p></div>
        @endforelse
    </div>
@endsection
