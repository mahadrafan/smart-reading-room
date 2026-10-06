<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // jenis peminjam: siswa mengisi NIS + kelas, tenaga pendidik mengisi NIP + jabatan/mapel
    const SISWA = 'Siswa';
    const TENAGA_PENDIDIK = 'Tenaga Pendidik';

    // nama tabel dan primary key mengikuti database basdat
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    // tabel users hanya punya created_at (tidak ada updated_at)
    public $timestamps = false;

    // kolom yang boleh diisi lewat User::create()
    protected $fillable = [
        'role',
        'borrower_type',
        'nim_nip',
        'name',
        'class',
        'job_title',
        'email',
        'password',
        'phone',
    ];

    // kolom yang disembunyikan kalau data user diubah jadi array / json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isTenagaPendidik(): bool
    {
        return $this->borrower_type === self::TENAGA_PENDIDIK;
    }

    // label nomor induk: NIS untuk siswa, NIP untuk tenaga pendidik
    public function getLabelNomorIndukAttribute(): string
    {
        return $this->isTenagaPendidik() ? 'NIP' : 'NIS';
    }

    // label & isi keterangan peminjam: kelas untuk siswa, jabatan/mapel untuk tenaga pendidik
    public function getLabelKeteranganAttribute(): string
    {
        return $this->isTenagaPendidik() ? 'Jabatan / mapel' : 'Kelas';
    }

    public function getKeteranganAttribute(): ?string
    {
        return $this->isTenagaPendidik() ? $this->job_title : $this->class;
    }

    // inisial untuk avatar ulasan: huruf depan dua kata pertama nama, misalnya "Budi Santoso" -> "BS"
    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim($this->name ?: 'P'));

        return mb_strtoupper(mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    }

    // warna avatar tetap untuk setiap user
    public function getWarnaAvatarAttribute(): string
    {
        $warna = ['#8b5e34', '#2f6f73', '#b5574b', '#a07a2c', '#3b2a1e', '#5b7b3a'];

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
