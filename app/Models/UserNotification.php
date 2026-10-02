<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'loan_id', 'type', 'message', 'sent_at', 'status'];

    protected $casts = ['sent_at' => 'datetime'];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
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
