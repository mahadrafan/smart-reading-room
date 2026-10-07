<?php

namespace App\Models;

use App\Mail\PengingatPengembalian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserNotification extends Model
{
    // kolom status menyimpan apakah notifikasi sudah dibaca penerimanya (dipakai pop up notifikasi baru).
    // penerima ada di kolom user_id: peminjam (perubahan status peminjaman) atau admin (pengajuan baru & denda)
    const BELUM_DIBACA = 'Belum Dibaca';
    const DIBACA = 'Dibaca';

    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'loan_id', 'type', 'message', 'sent_at', 'status'];

    protected $casts = ['sent_at' => 'datetime'];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function scopeBelumDibaca($query)
    {
        return $query->where('status', self::BELUM_DIBACA);
    }

    // kirim notifikasi peminjaman ke halaman Notifikasi peminjam (hanya di dalam web, tidak lewat email).
    // Pengajuan dikirim oleh peminjam sendiri, jadi langsung dianggap dibaca dan tidak muncul di pop up.
    public static function kirim(Loan $loan, string $type, string $message): self
    {
        return self::create([
            'user_id' => $loan->user_id,
            'loan_id' => $loan->loan_id,
            'type'    => $type,
            'message' => $message,
            'sent_at' => now(),
            'status'  => $type === 'Pengajuan' ? self::DIBACA : self::BELUM_DIBACA,
        ]);
    }

    // kirim notifikasi ke semua admin (mis. ada pengajuan baru), muncul sebagai pop up di halaman admin
    public static function kirimKeAdmin(Loan $loan, string $type, string $message): void
    {
        foreach (User::where('role', 'Admin')->pluck('user_id') as $adminId) {
            self::create([
                'user_id' => $adminId,
                'loan_id' => $loan->loan_id,
                'type'    => $type,
                'message' => $message,
                'sent_at' => now(),
                'status'  => self::BELUM_DIBACA,
            ]);
        }
    }

    // pengingat harian denda: setiap hari satu notifikasi "Terlambat" per peminjaman yang dendanya belum lunas
    // (buku masih dipinjam melewati batas kembali, atau data lama yang sudah dikembalikan terlambat tapi belum bayar).
    // Peminjam menerima pengingat untuk peminjamannya sendiri, admin untuk semua peminjaman.
    // Dijalankan saat penerima membuka halaman, jadi tidak perlu penjadwal (cron).
    public static function kirimPengingatTerlambat(User $penerima): void
    {
        $untukAdmin = $penerima->role === 'Admin';
        $belumLunas = Loan::terlambatBelumLunas()
            ->when(! $untukAdmin, fn ($q) => $q->where('user_id', $penerima->user_id))
            ->pluck('loan_id');

        foreach ($belumLunas as $loanId) {
            DB::transaction(function () use ($loanId, $penerima, $untukAdmin) {
                // baris dikunci agar dua halaman yang dibuka bersamaan tidak sama-sama mengirim pengingat hari ini
                $loan = Loan::with(['book', 'user'])->whereNull('fine_paid_at')->lockForUpdate()->find($loanId);
                $sudahDikirim = self::where('user_id', $penerima->user_id)->where('loan_id', $loanId)
                    ->where('type', 'Terlambat')->whereDate('sent_at', today())->exists();
                if (! $loan || $loan->jumlah_denda <= 0 || $sudahDikirim) {
                    return;
                }

                $judul = $loan->book->title ?? '-';
                $denda = 'Rp' . number_format($loan->jumlah_denda, 0, ',', '.');
                $peminjam = ($loan->user->name ?? '-') . ' (' . ($loan->user->nim_nip ?? '-') . ')';

                if ($untukAdmin) {
                    $pesan = $loan->status === 'Dipinjam'
                        ? 'Buku "' . $judul . '" yang dipinjam ' . $peminjam . ' terlambat ' . $loan->hari_terlambat
                            . ' hari, denda ' . $denda . ' belum lunas (' . $loan->kode . ').'
                        : 'Denda keterlambatan buku "' . $judul . '" milik ' . $peminjam . ' sebesar ' . $denda
                            . ' belum lunas (' . $loan->kode . ').';
                } else {
                    $pesan = $loan->status === 'Dipinjam'
                        ? 'Buku "' . $judul . '" terlambat ' . $loan->hari_terlambat . ' hari dan dendanya ' . $denda
                            . ' belum lunas. Denda bertambah Rp' . number_format(config('app.fine_per_day'), 0, ',', '.')
                            . ' setiap hari sampai buku dikembalikan dan denda dilunasi di perpustakaan.'
                        : 'Denda keterlambatan buku "' . $judul . '" sebesar ' . $denda . ' belum lunas. Segera lunasi di perpustakaan.';
                }

                self::create([
                    'user_id' => $penerima->user_id,
                    'loan_id' => $loan->loan_id,
                    'type'    => 'Terlambat',
                    'message' => $pesan,
                    'sent_at' => now(),
                    'status'  => self::BELUM_DIBACA,
                ]);
            });
        }
    }

    // pengingat lewat email (dijalankan terjadwal setiap hari oleh command pengingat:email):
    // - H-1: buku yang sedang dipinjam dan batas kembalinya besok
    // - Terlambat: sekali saja, saat buku pertama kali terdeteksi melewati batas kembali dan dendanya belum lunas
    // setiap email yang berhasil terkirim juga dicatat sebagai notifikasi "Pengingat" di web, sekaligus penanda
    // agar tidak terkirim dua kali (dikirim sebelum/pada batas kembali = H-1, sesudahnya = Terlambat).
    // email yang gagal terkirim tidak dicatat, jadi dicoba lagi pada jadwal berikutnya.
    public static function kirimPengingatEmail(): array
    {
        $hasil = ['H-1' => 0, 'Terlambat' => 0, 'gagal' => 0];

        $daftar = [
            'H-1'       => Loan::where('status', 'Dipinjam')->whereDate('due_date', today()->addDay()),
            'Terlambat' => Loan::where('status', 'Dipinjam')->whereNull('fine_paid_at')->whereDate('due_date', '<', today()),
        ];

        foreach ($daftar as $jenis => $query) {
            foreach ($query->with(['user', 'book'])->get() as $loan) {
                $sudahDikirim = self::where('user_id', $loan->user_id)->where('loan_id', $loan->loan_id)
                    ->where('type', 'Pengingat')
                    ->whereDate('sent_at', $jenis === 'H-1' ? '<=' : '>', $loan->due_date)
                    ->exists();
                if ($sudahDikirim || ! $loan->user?->email) {
                    continue;
                }

                try {
                    Mail::to($loan->user->email)->send(new PengingatPengembalian($loan, $jenis));
                } catch (\Throwable $e) {
                    report($e);
                    $hasil['gagal']++;
                    continue;
                }

                $judul = $loan->book->title ?? '-';
                $pesan = $jenis === 'H-1'
                    ? 'Buku "' . $judul . '" harus dikembalikan besok, ' . $loan->due_date->format('d/m/Y') . '.'
                    : 'Buku "' . $judul . '" sudah melewati batas kembali (' . $loan->due_date->format('d/m/Y')
                        . '). Denda Rp' . number_format(config('app.fine_per_day'), 0, ',', '.') . ' per hari berjalan sampai buku dikembalikan.';

                self::create([
                    'user_id' => $loan->user_id,
                    'loan_id' => $loan->loan_id,
                    'type'    => 'Pengingat',
                    'message' => $pesan . ' Pengingat ini juga dikirim ke email ' . $loan->user->email . '.',
                    'sent_at' => now(),
                    'status'  => self::BELUM_DIBACA,
                ]);
                $hasil[$jenis]++;
            }
        }

        return $hasil;
    }

    // tandai notifikasi belum dibaca milik user sebagai dibaca, sampai id tertentu saja
    // (notifikasi yang masuk setelah pop up tampil tetap belum dibaca)
    public static function tandaiDibaca(int $userId, ?int $sampaiId = null): int
    {
        return self::where('user_id', $userId)
            ->belumDibaca()
            ->when($sampaiId, fn ($q) => $q->where('notification_id', '<=', $sampaiId))
            ->update(['status' => self::DIBACA]);
    }

    // ikon dan label badge tiap jenis notifikasi (dipakai halaman Notifikasi dan pop up)
    public function getTampilanAttribute(): array
    {
        $tipe = $this->type ?? 'Info';

        if (in_array($tipe, ['Terlambat', 'Gagal'])) {
            return ['ikonClass' => 'notif-ikon-peringatan', 'ikon' => '📅', 'badgeClass' => 'penting',
                'badgeLabel' => $tipe == 'Terlambat' ? 'Penting' : 'Gagal'];
        }
        if ($tipe == 'Pengingat') {
            return ['ikonClass' => 'notif-ikon-peringatan', 'ikon' => '⏰', 'badgeClass' => 'penting', 'badgeLabel' => 'Email terkirim'];
        }
        if (in_array($tipe, ['Dikembalikan', 'Dipinjam'])) {
            return ['ikonClass' => 'notif-ikon-sukses', 'ikon' => '✅', 'badgeClass' => 'selesai', 'badgeLabel' => 'Selesai'];
        }
        if ($tipe == 'Pengumuman') {
            return ['ikonClass' => 'notif-ikon-info', 'ikon' => '📚', 'badgeClass' => 'pengumuman', 'badgeLabel' => 'Pengumuman'];
        }

        return ['ikonClass' => 'notif-ikon-info', 'ikon' => '📋', 'badgeClass' => 'pengumuman', 'badgeLabel' => $tipe];
    }

    // waktu relatif tanpa koma: "45 detik", "1 menit 20 detik", "2 jam 5 menit", lalu "3 hari" yang lalu
    public function getWaktuRelatifAttribute(): string
    {
        if (! $this->sent_at) {
            return '-';
        }

        // diffInSeconds di Carbon 3 berupa desimal, jadi dibulatkan ke bawah; minimal 1 detik
        $detik = max(1, (int) floor($this->sent_at->diffInSeconds(now(), true)));

        if ($detik < 60) {
            return $detik . ' detik yang lalu';
        }

        if ($detik < 3600) {
            $menit = intdiv($detik, 60);
            $sisa = $detik % 60;
            return $menit . ' menit' . ($sisa ? ' ' . $sisa . ' detik' : '') . ' yang lalu';
        }

        if ($detik < 86400) {
            $jam = intdiv($detik, 3600);
            $sisa = intdiv($detik % 3600, 60);
            return $jam . ' jam' . ($sisa ? ' ' . $sisa . ' menit' : '') . ' yang lalu';
        }

        return intdiv($detik, 86400) . ' hari yang lalu';
    }
}
