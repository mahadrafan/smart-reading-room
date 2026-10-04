<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    // nama tabel dan primary key mengikuti database basdat
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    // tabel users hanya punya created_at (tidak ada updated_at)
    public $timestamps = false;

    // kolom yang boleh diisi lewat User::create()
    protected $fillable = [
        'role',
        'nim_nip',
        'name',
        'class',
        'email',
        'password',
        'phone',
    ];

    // kolom yang disembunyikan kalau data user diubah jadi array / json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // inisial untuk avatar ulasan: huruf depan dua kata pertama nama, misalnya "Budi Santoso" -> "BS"
    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim($this->name ?: 'P'));

        return mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    }

    // warna avatar tetap untuk setiap user
    public function getWarnaAvatarAttribute(): string
    {
        $warna = ['#8671c9', '#2f6f73', '#b5574b', '#a07a2c', '#1b2340', '#5b7b3a'];

        return $warna[$this->user_id % count($warna)];
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id', 'user_id');
    }
}
