<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // jenis peminjam: siswa mengisi NIS + kelas, tenaga pendidik mengisi NIP + jabatan
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

    // label & isi keterangan peminjam: kelas untuk siswa, jabatan untuk tenaga pendidik
    public function getLabelKeteranganAttribute(): string
    {
        return $this->isTenagaPendidik() ? 'Jabatan' : 'Kelas';
    }

    public function getKeteranganAttribute(): ?string
    {
        return $this->isTenagaPendidik() ? $this->job_title : $this->class;
    }

    // ---------------------------------------------------------
    // NO. TELEPON
    // disimpan lengkap dengan kode negara, mis. "+628123456789". nomor lama tanpa kode ("0812...") dianggap +62.
    // ---------------------------------------------------------

    const KODE_INDONESIA = '62';

    // pilihan kode negara di form daftar & profil
    const KODE_NEGARA = [
        '62'  => 'Indonesia',
        '60'  => 'Malaysia',
        '65'  => 'Singapura',
        '673' => 'Brunei Darussalam',
        '670' => 'Timor Leste',
        '66'  => 'Thailand',
        '63'  => 'Filipina',
        '84'  => 'Vietnam',
        '61'  => 'Australia',
        '81'  => 'Jepang',
        '82'  => 'Korea Selatan',
        '86'  => 'Tiongkok',
        '91'  => 'India',
        '966' => 'Arab Saudi',
        '971' => 'Uni Emirat Arab',
        '44'  => 'Inggris',
        '1'   => 'Amerika Serikat / Kanada',
    ];

    // nomor HP Indonesia tanpa 0 di depan: 8[1-9] lalu 7-10 digit (0812... ditulis 10-13 digit)
    const POLA_HP_INDONESIA = '/^8[1-9]\d{7,10}$/';

    // rapikan isian form jadi [kode, nomor]: buang selain angka dan 0 di depan.
    // nomor yang ditempel lengkap ("+60 12...", atau "62812..." saat +62 dipilih) ikut dipisah dari kodenya.
    public static function rapikanTelepon(?string $kode, ?string $nomor): array
    {
        $tulisan = trim((string) $nomor);
        if (str_starts_with($tulisan, '+')) {
            [$kodeTertulis, $sisa] = self::pisahTelepon($tulisan);
            if ($kodeTertulis !== null) {
                [$kode, $tulisan] = [$kodeTertulis, $sisa];
            }
        }

        $angka = preg_replace('/\D/', '', $tulisan);
        if ((string) $kode === self::KODE_INDONESIA && preg_match('/^62(8\d+)$/', $angka, $cocok)) {
            $angka = $cocok[1];
        }

        return [(string) $kode, ltrim($angka, '0')];
    }

    public static function gabungTelepon(string $kode, string $nomor): string
    {
        return '+' . $kode . $nomor;
    }

    // aturan validasi phone_code + phone (phone sudah dirapikan); +62 wajib nomor HP agar bisa dihubungi lewat WhatsApp
    public static function aturanTelepon(string $kode): array
    {
        return [
            'phone_code' => ['required', 'in:' . implode(',', array_keys(self::KODE_NEGARA))],
            'phone'      => ['required', 'regex:/^\d{6,14}$/', function ($atribut, $nilai, $gagal) use ($kode) {
                if ($kode === self::KODE_INDONESIA && ! preg_match(self::POLA_HP_INDONESIA, $nilai)) {
                    $gagal('No. telepon harus nomor HP, contoh 81234567890.');
                }
            }],
        ];
    }

    public static function pesanTelepon(): array
    {
        return [
            'phone_code.*' => 'Pilih kode negara dari daftar.',
            'phone.regex'  => 'No. telepon hanya berisi 6–14 angka.',
        ];
    }

    // pisah nomor jadi [kode, nomor]: "+628123..." -> ["62", "8123..."], "0812..." (data lama) -> ["62", "812..."].
    // kode null bila nomor berawalan + tetapi kodenya tidak ada di daftar
    public static function pisahTelepon(?string $telepon): array
    {
        $telepon = trim((string) $telepon);
        $angka = preg_replace('/\D/', '', $telepon);

        if (! str_starts_with($telepon, '+')) {
            if (preg_match('/^62(8\d+)$/', $angka, $cocok)) {
                $angka = $cocok[1];
            }

            return [self::KODE_INDONESIA, ltrim($angka, '0')];
        }

        // kode terpanjang dicek lebih dulu (mis. 673 sebelum 6x)
        $daftarKode = array_map('strval', array_keys(self::KODE_NEGARA));
        usort($daftarKode, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($daftarKode as $kode) {
            if (str_starts_with($angka, $kode)) {
                return [$kode, substr($angka, strlen($kode))];
            }
        }

        return [null, $angka];
    }

    // untuk mengisi form profil
    public function getKodeTeleponAttribute(): string
    {
        return self::pisahTelepon($this->phone)[0] ?? self::KODE_INDONESIA;
    }

    public function getNomorTeleponAttribute(): string
    {
        return self::pisahTelepon($this->phone)[1];
    }

    // nomor untuk ditampilkan: "+62 8123456789"; nomor lama tanpa kode ditampilkan apa adanya
    public function getTeleponTampilAttribute(): ?string
    {
        if (! str_starts_with((string) $this->phone, '+')) {
            return $this->phone;
        }
        [$kode, $nomor] = self::pisahTelepon($this->phone);

        return $kode !== null ? '+' . $kode . ' ' . $nomor : $this->phone;
    }

    // tautan chat WhatsApp: +62 812... / 0812... / 812... -> https://wa.me/62812...
    // null bila nomornya tidak wajar (mis. "1"), agar admin tidak diarahkan ke chat yang salah
    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }
        [$kode, $nomor] = self::pisahTelepon($this->phone);

        $wajar = $kode === self::KODE_INDONESIA
            ? preg_match(self::POLA_HP_INDONESIA, $nomor)
            : $kode !== null && preg_match('/^\d{6,14}$/', $nomor);

        return $wajar ? 'https://wa.me/' . $kode . $nomor : null;
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
