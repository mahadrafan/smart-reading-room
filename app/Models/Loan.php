<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = 'loans';
    protected $primaryKey = 'loan_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'book_id', 'loan_date', 'due_date', 'return_date',
        'status', 'approved_by', 'approved_at', 'fine_paid_at', 'fine_paid_by',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'loan_date'    => 'date',
        'due_date'     => 'date',
        'return_date'  => 'date',
        'approved_at'  => 'datetime',
        'fine_paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'book_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function finePaidBy()
    {
        return $this->belongsTo(User::class, 'fine_paid_by', 'user_id');
    }

    public function getDendaLunasAttribute(): bool
    {
        return $this->jumlah_denda > 0 && $this->fine_paid_at !== null;
    }

    // nomor peminjaman, contoh: LOAN-20260921-003
    public function getKodeAttribute()
    {
        $tanggal = $this->request_date ? $this->request_date->format('Ymd') : '00000000';
        return 'LOAN-' . $tanggal . '-' . str_pad($this->loan_id, 3, '0', STR_PAD_LEFT);
    }

    // Accessor untuk tenggat waktu pengambilan buku (2 hari setelah dikonfirmasi admin)
    public function getTenggatPengambilanAttribute()
    {
        return $this->approved_at ? $this->approved_at->copy()->addDays(2) : null;
    }

    // Accessor apakah tenggat pengambilan sudah kadaluarsa (lebih dari 2 hari)
    public function getIsExpiredPengambilanAttribute()
    {
        if ($this->status !== 'Dikonfirmasi' || !$this->approved_at) {
            return false;
        }
        return now()->gt($this->tenggat_pengambilan);
    }

    public function getHariTerlambatAttribute(): int
    {
        if (! in_array($this->status, ['Dipinjam', 'Dikembalikan'], true) || ! $this->due_date) {
            return 0;
        }

        $tanggalAkhir = $this->status === 'Dikembalikan' && $this->return_date
            ? $this->return_date
            : today();

        if (! $this->due_date->lt($tanggalAkhir)) {
            return 0;
        }

        return (int) $this->due_date->diffInDays($tanggalAkhir);
    }

    public function getJumlahDendaAttribute(): int
    {
        return $this->hari_terlambat * config('library.fine_per_day');
    }

    // Method untuk mengeksekusi otomatis pengubahan status Dikonfirmasi yang hangus (> 2 hari) menjadi Gagal & mengembalikan stok buku
    public static function prosesTenggatPengambilan()
    {
        $expiredLoans = self::where('status', 'Dikonfirmasi')
            ->whereNotNull('approved_at')
            ->get()
            ->filter(function ($loan) {
                return $loan->is_expired_pengambilan;
            });

        foreach ($expiredLoans as $loan) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($loan) {
                // Pastikan status belum berubah di sesi/thread lain
                $freshLoan = self::where('status', 'Dikonfirmasi')->find($loan->loan_id);
                if ($freshLoan) {
                    $freshLoan->status = 'Gagal';
                    $freshLoan->save();

                    if ($freshLoan->book) {
                        $freshLoan->book->increment('available_stock');
                    }

                    AdminLog::catat(
                        'Gagal Otomatis',
                        'loans',
                        $freshLoan->loan_id,
                        'Peminjaman "' . ($freshLoan->book->title ?? '-') . '" oleh ' . ($freshLoan->user->name ?? '-') . ' otomatis Gagal karena melewati tenggat waktu 2 hari pengambilan.',
                        $freshLoan->approved_by
                    );

                    app(\App\Services\LoanNotificationService::class)->send(
                        $freshLoan->load(['user', 'book']),
                        'Gagal',
                        'Peminjaman gagal karena tenggat pengambilan lewat',
                        'Peminjaman buku "' . ($freshLoan->book->title ?? '-') . '" dibatalkan otomatis karena melewati tenggat pengambilan.'
                    );
                }
            });
        }
    }
}
