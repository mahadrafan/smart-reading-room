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

    // jenis email pengingat nilai kolom type pada notifikasi di web (dicatat bersamaan dengan email yang terkirim).
    // H-1 dan Terlambat memakai 'Pengingat' (dibedakan lewat tanggal kirim), dua jenis lain punya type sendiri
    const TIPE_EMAIL = [
        'H-1'       => 'Pengingat',
        'Hari-H'    => 'Batas Kembali',
        'Terlambat' => 'Pengingat',
        'Seminggu'  => 'Terlambat 7 Hari',
    ];
    // semua type notifikasi hasil email pengingat, ditutup (dianggap dibaca) saat buku kembali / denda lunas
    const TIPE_PENGINGAT = ['Pengingat', 'Batas Kembali', 'Terlambat 7 Hari'];
    // email "Seminggu": dikirim sekali setelah buku terlambat sekian hari dan denda belum lunas
    const HARI_PENGINGAT_AKHIR = 7;

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

    // pengingat lewat email (dijalankan terjadwal setiap hari oleh command pengingat:email), masing-masing sekali per peminjaman:
    // - H-1: buku yang sedang dipinjam dan batas kembalinya besok
    // - Hari-H: buku yang sedang dipinjam dan batas kembalinya hari ini (masa pinjam habis)
    // - Terlambat: saat buku pertama kali terdeteksi melewati batas kembali dan dendanya belum lunas
    // - Seminggu: saat buku sudah terlambat 7 hari atau lebih dan dendanya masih belum lunas
    // setiap email yang berhasil terkirim juga dicatat sebagai notifikasi di web (lihat TIPE_EMAIL), sekaligus penanda
    // agar tidak terkirim dua kali. email yang gagal terkirim tidak dicatat, jadi dicoba lagi pada jadwal berikutnya.
    public static function kirimPengingatEmail(): array
    {
        $hasil = ['H-1' => 0, 'Hari-H' => 0, 'Terlambat' => 0, 'Seminggu' => 0, 'gagal' => 0];

        $dipinjam = fn () => Loan::where('status', 'Dipinjam');
        $daftar = [
            'H-1'       => $dipinjam()->whereDate('due_date', today()->addDay()),
            'Hari-H'    => $dipinjam()->whereDate('due_date', today()),
            'Terlambat' => $dipinjam()->whereNull('fine_paid_at')->whereDate('due_date', '<', today()),
            'Seminggu'  => $dipinjam()->whereNull('fine_paid_at')
                ->whereDate('due_date', '<=', today()->subDays(self::HARI_PENGINGAT_AKHIR)),
        ];

        foreach ($daftar as $jenis => $query) {
            foreach ($query->with(['user', 'book'])->get() as $loan) {
                $sudahAda = self::where('user_id', $loan->user_id)->where('loan_id', $loan->loan_id)
                    ->where('type', self::TIPE_EMAIL[$jenis]);
                // H-1 dan Terlambat sama-sama bertype 'Pengingat': dibedakan dari tanggal kirim (sebelum / sesudah batas kembali)
                if ($jenis === 'H-1') {
                    $sudahAda->whereDate('sent_at', '<', $loan->due_date);
                } elseif ($jenis === 'Terlambat') {
                    $sudahAda->whereDate('sent_at', '>', $loan->due_date);
                }
                if ($sudahAda->exists() || ! $loan->user?->email) {
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
                $batas = $loan->due_date->format('d/m/Y');
                $denda = 'Rp' . number_format(config('app.fine_per_day'), 0, ',', '.');
                $pesan = match ($jenis) {
                    'H-1'       => 'Buku "' . $judul . '" harus dikembalikan besok, ' . $batas . '.',
                    'Hari-H'    => 'Hari ini batas kembali buku "' . $judul . '" (' . $batas . '). Kembalikan hari ini agar tidak terkena denda '
                        . $denda . ' per hari.',
                    'Terlambat' => 'Buku "' . $judul . '" sudah melewati batas kembali (' . $batas . '). Denda ' . $denda
                        . ' per hari berjalan sampai buku dikembalikan.',
                    'Seminggu'  => 'Buku "' . $judul . '" sudah terlambat ' . $loan->hari_terlambat . ' hari dan dendanya belum lunas. Denda saat ini Rp'
                        . number_format($loan->jumlah_denda, 0, ',', '.') . ' dan terus bertambah ' . $denda . ' per hari.',
                };

                self::create([
                    'user_id' => $loan->user_id,
                    'loan_id' => $loan->loan_id,
                    'type'    => self::TIPE_EMAIL[$jenis],
                    'message' => $pesan . ' Pengingat ini juga dikirim ke email ' . $loan->user->email . '.',
                    'sent_at' => now(),
                    'status'  => self::BELUM_DIBACA,
                ]);
                $hasil[$jenis]++;
            }
        }

        return $hasil;
    }

    // notifikasi yang sudah tidak berlaku dianggap dibaca agar tidak terus muncul di pop up siapa pun penerimanya,
    // mis. "Pengajuan baru" setelah pengajuan diproses / dibatalkan, atau "denda belum lunas" setelah denda lunas
    public static function tandaiSelesai(int $loanId, array $jenis): void
    {
        self::where('loan_id', $loanId)->whereIn('type', $jenis)->belumDibaca()->update(['status' => self::DIBACA]);
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
        if (in_array($tipe, self::TIPE_PENGINGAT, true)) {
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
