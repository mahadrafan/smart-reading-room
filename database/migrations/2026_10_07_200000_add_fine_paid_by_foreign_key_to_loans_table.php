<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// loans.fine_paid_by (admin yang mengonfirmasi pembayaran denda) merujuk ke users.user_id,
// sama seperti approved_by. tipenya disamakan dengan users.user_id lalu diberi foreign key.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $this->kolomSeperti($table, 'fine_paid_by', 'users', 'user_id')->nullable()->change();
        });

        Schema::table('loans', function (Blueprint $table) {
            // admin yang tercatat mengonfirmasi pembayaran denda tidak boleh terhapus
            $table->foreign('fine_paid_by', 'fk_loans_fine_paid_by')
                ->references('user_id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // SQLite hanya bisa menghapus foreign key lewat nama kolomnya
            $table->dropForeign(Schema::getConnection()->getDriverName() === 'sqlite' ? ['fine_paid_by'] : 'fk_loans_fine_paid_by');
        });

        // MySQL membuat index otomatis untuk foreign key dan index itu tidak ikut terhapus
        if (Schema::hasIndex('loans', 'fk_loans_fine_paid_by')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropIndex('fk_loans_fine_paid_by');
            });
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedBigInteger('fine_paid_by')->nullable()->change();
        });
    }

    // kolom dengan tipe sama persis dengan kolom yang dirujuk (int dari dump MySQL, bigint unsigned dari migration)
    private function kolomSeperti(Blueprint $table, string $nama, string $tabel, string $kolom)
    {
        $tipe = strtolower(collect(Schema::getColumns($tabel))->firstWhere('name', $kolom)['type'] ?? 'bigint unsigned');
        $unsigned = str_contains($tipe, 'unsigned');

        return str_contains($tipe, 'bigint')
            ? $table->bigInteger($nama, false, $unsigned)
            : $table->integer($nama, false, $unsigned);
    }
};
