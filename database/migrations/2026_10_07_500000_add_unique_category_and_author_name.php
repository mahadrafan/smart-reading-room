<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// nama kategori dan nama penulis tidak boleh kembar. aplikasi sudah mengeceknya,
// sekarang database ikut menjaga (instalasi dari migration lama sudah punya unique ini; dump belum).
return new class extends Migration
{
    private const UNIK = [
        'categories' => ['category_name', 'uk_categories_name'],
        'authors'    => ['author_name', 'uk_authors_name'],
    ];

    public function up(): void
    {
        foreach (self::UNIK as $tabel => [$kolom, $nama]) {
            if (! $this->adaUnique($tabel, $kolom)) {
                Schema::table($tabel, function (Blueprint $table) use ($kolom, $nama) {
                    $table->unique($kolom, $nama);
                });
            }
        }
    }

    public function down(): void
    {
        // hanya menghapus unique yang dibuat migration ini
        foreach (self::UNIK as $tabel => [, $nama]) {
            if (Schema::hasIndex($tabel, $nama)) {
                Schema::table($tabel, function (Blueprint $table) use ($nama) {
                    $table->dropUnique($nama);
                });
            }
        }
    }

    private function adaUnique(string $tabel, string $kolom): bool
    {
        return collect(Schema::getIndexes($tabel))
            ->contains(fn ($i) => $i['unique'] && ! $i['primary'] && $i['columns'] === [$kolom]);
    }
};
