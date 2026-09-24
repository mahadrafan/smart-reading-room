<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Loan;
use App\Models\UserNotification;
use App\Services\LoanNotificationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('loans:send-overdue-reminders', function (LoanNotificationService $notifications) {
    $loans = Loan::where('status', 'Dipinjam')
        ->whereDate('due_date', '<', today())
        ->with(['user', 'book'])
        ->get();

    $sent = 0;
    foreach ($loans as $loan) {
        $alreadySent = UserNotification::where('loan_id', $loan->loan_id)
            ->where('type', 'Terlambat')
            ->whereDate('sent_at', today())
            ->exists();

        if (! $alreadySent) {
            $notifications->send(
                $loan,
                'Terlambat',
                'Peringatan keterlambatan pengembalian buku',
                'Buku "' . $loan->book->title . '" terlambat ' . $loan->hari_terlambat . ' hari. Denda saat ini Rp' . number_format($loan->jumlah_denda, 0, ',', '.') . '. Segera hubungi atau datangi perpustakaan.'
            );
            $sent++;
        }
    }

    $this->info($sent . ' pengingat keterlambatan diproses.');
})->purpose('Kirim pengingat email harian untuk peminjaman yang terlambat');

Schedule::command('loans:send-overdue-reminders')->dailyAt('08:00');
