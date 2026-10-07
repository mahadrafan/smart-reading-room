{{-- isi email pengingat pengembalian (App\Mail\PengingatPengembalian); gaya ditulis inline agar tampil rapi di aplikasi email --}}
@php
    $judul = $loan->book->title ?? '-';
    $batas = $loan->due_date?->format('d/m/Y') ?? '-';
    $dendaPerHari = 'Rp' . number_format(config('app.fine_per_day'), 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:24px;background:#f6f1ea;font-family:Arial,Helvetica,sans-serif;color:#2b2119;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-top:5px solid #8b5e34;border-radius:14px;padding:28px;">
        <p style="margin:0 0 6px;font-size:13px;color:#857767;">Smart Reading Room</p>
        <h1 style="margin:0 0 18px;font-size:20px;color:#3b2a1e;">
            {{ $jenis === 'H-1' ? 'Besok batas pengembalian buku' : 'Buku kamu terlambat dikembalikan' }}
        </h1>

        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Halo {{ $loan->user->name ?? 'Peminjam' }},</p>

        @if ($jenis === 'H-1')
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                Buku <strong>"{{ $judul }}"</strong> yang kamu pinjam harus dikembalikan ke perpustakaan
                paling lambat <strong>besok, {{ $batas }}</strong>.
            </p>
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                Kembalikan tepat waktu agar tidak terkena denda keterlambatan {{ $dendaPerHari }} per hari.
            </p>
        @else
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                Buku <strong>"{{ $judul }}"</strong> sudah melewati batas pengembalian
                (<strong>{{ $batas }}</strong>) dan kini terlambat {{ $loan->hari_terlambat }} hari.
            </p>
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                Denda saat ini <strong>Rp{{ number_format($loan->jumlah_denda, 0, ',', '.') }}</strong> dan bertambah
                {{ $dendaPerHari }} setiap hari sampai buku dikembalikan dan denda dilunasi di perpustakaan.
            </p>
        @endif

        <table style="width:100%;margin:6px 0 20px;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;color:#857767;">No. peminjaman</td><td style="padding:6px 0;text-align:right;">{{ $loan->kode }}</td></tr>
            <tr><td style="padding:6px 0;color:#857767;">Batas kembali</td><td style="padding:6px 0;text-align:right;">{{ $batas }}</td></tr>
        </table>

        <a href="{{ route('peminjaman.detail', $loan->loan_id) }}"
           style="display:inline-block;padding:12px 22px;background:#8b5e34;color:#ffffff;text-decoration:none;border-radius:999px;font-size:14px;font-weight:bold;">
            Lihat peminjaman
        </a>

        <p style="margin:24px 0 0;font-size:12px;color:#857767;line-height:1.5;">
            Email ini dikirim otomatis oleh Smart Reading Room ke alamat yang terdaftar di akunmu.
        </p>
    </div>
</body>
</html>
