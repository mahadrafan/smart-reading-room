<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// zona waktu aplikasi diganti dari UTC ke Asia/Jakarta (WIB).
// kolom waktu yang dulu diisi PHP (now()) tersimpan dalam UTC, sedangkan kolom yang diisi MySQL
// (CURRENT_TIMESTAMP) sudah WIB. migration ini menggeser kolom yang diisi PHP +7 jam agar semuanya WIB.
return new class extends Migration
{
    private array $kolom = [
        'loans'         => ['approved_at', 'fine_paid_at'],
        'notifications' => ['sent_at'],
        'books'         => ['updated_at'],
    ];

    public function up(): void
    {
        $this->geser(7);
    }

    public function down(): void
    {
        $this->geser(-7);
    }

    private function geser(int $jam): void
    {
        foreach ($this->kolom as $tabel => $daftarKolom) {
            foreach ($daftarKolom as $kolom) {
                $nilaiBaru = DB::getDriverName() === 'sqlite'
                    ? DB::raw("datetime($kolom, '" . ($jam >= 0 ? '+' : '') . "$jam hours')")
                    : DB::raw("DATE_ADD($kolom, INTERVAL $jam HOUR)");

                DB::table($tabel)->whereNotNull($kolom)->update([$kolom => $nilaiBaru]);
            }
        }
    }
};
