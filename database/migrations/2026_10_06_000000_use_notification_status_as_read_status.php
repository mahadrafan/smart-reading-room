<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // kolom status notifikasi sekarang berarti "Belum Dibaca" / "Dibaca" (untuk pop up notifikasi baru).
    // Notifikasi lama ("Terkirim"/"Gagal" dari fitur email yang sudah dihapus) dianggap sudah dibaca,
    // supaya peminjam tidak langsung mendapat pop up berisi semua notifikasi lama.
    public function up(): void
    {
        DB::table('notifications')->update(['status' => 'Dibaca']);
    }

    public function down(): void
    {
        DB::table('notifications')->update(['status' => 'Terkirim']);
    }
};
