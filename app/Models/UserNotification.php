<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    // kolom status menyimpan apakah notifikasi sudah dibaca peminjam (dipakai pop up notifikasi baru)
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
