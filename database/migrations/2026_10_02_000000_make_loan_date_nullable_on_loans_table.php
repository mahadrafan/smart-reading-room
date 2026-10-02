<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * loan_date (tanggal pinjam) baru diisi saat admin mengubah status menjadi Dipinjam,
     * jadi pengajuan yang masih Menunggu / Dikonfirmasi belum punya tanggal pinjam.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->date('loan_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // isi tanggal pinjam yang kosong dengan tanggal pengajuan sebelum dibuat wajib lagi
        DB::table('loans')->whereNull('loan_date')->update(['loan_date' => DB::raw('DATE(request_date)')]);

        Schema::table('loans', function (Blueprint $table) {
            $table->date('loan_date')->nullable(false)->change();
        });
    }
};
