<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// email pengingat pengembalian buku ke peminjam (masing-masing dikirim sekali per peminjaman):
// 'H-1' = sehari sebelum batas kembali, 'Hari-H' = di hari batas kembali (masa pinjam habis),
// 'Terlambat' = saat buku pertama kali terlambat, 'Seminggu' = saat terlambat seminggu dan denda belum lunas
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
            subject: match ($this->jenis) {
                'H-1'      => 'Pengingat: besok batas pengembalian buku "' . $judul . '"',
                'Hari-H'   => 'Hari ini batas pengembalian buku "' . $judul . '"',
                'Seminggu' => 'Buku "' . $judul . '" sudah terlambat seminggu lebih',
                default    => 'Buku "' . $judul . '" terlambat dikembalikan',
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pengingat');
    }
}
