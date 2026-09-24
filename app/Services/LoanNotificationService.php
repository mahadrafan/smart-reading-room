<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LoanNotificationService
{
    public function send(Loan $loan, string $type, string $subject, string $message): UserNotification
    {
        $status = 'Terkirim';

        try {
            if ($loan->user?->email) {
                Mail::raw($message, function ($mail) use ($loan, $subject) {
                    $mail->to($loan->user->email, $loan->user->name)->subject($subject);
                });
            } else {
                $status = 'Gagal';
            }
        } catch (Throwable $exception) {
            $status = 'Gagal';
            Log::warning('Email notifikasi peminjaman gagal dikirim.', [
                'loan_id' => $loan->loan_id,
                'error' => $exception->getMessage(),
            ]);
        }

        return UserNotification::create([
            'user_id' => $loan->user_id,
            'loan_id' => $loan->loan_id,
            'type' => $type,
            'message' => $message,
            'sent_at' => now(),
            'status' => $status,
        ]);
    }
}
