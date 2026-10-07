<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// email pengingat pengembalian buku ke peminjam:
// 'H-1' = sehari sebelum batas kembali, 'Terlambat' = sekali saat buku pertama kali terlambat
class PengingatPengembalian extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Loan $loan, public string $jenis)
    {
    }

    public function envelope(): Envelope
    {
        $judul = $this->loan->book->title ?? 'buku';

        return new Envelope(
            // nama pengirim selalu nama aplikasi, walaupun MAIL_FROM_NAME tidak diisi di .env (bawaan Laravel: "Example")
            from: new Address(config('mail.from.address'), config('app.name')),
            subject: $this->jenis === 'H-1'
                ? 'Pengingat: besok batas pengembalian buku "' . $judul . '"'
                : 'Buku "' . $judul . '" terlambat dikembalikan',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pengingat');
    }
}
