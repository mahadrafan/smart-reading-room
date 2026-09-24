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
