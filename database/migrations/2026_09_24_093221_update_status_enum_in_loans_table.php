<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temporarily change status to VARCHAR to allow status mapping
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loans MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Menunggu'");
        }

        // 2. Migrate old status values to new status values
        DB::table('loans')->where('status', 'Disetujui')->update(['status' => 'Dikonfirmasi']);
        DB::table('loans')->where('status', 'Ditolak')->update(['status' => 'Gagal']);

        // 3. Set status column to the new 5-value ENUM
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('Menunggu', 'Dikonfirmasi', 'Dipinjam', 'Gagal', 'Dikembalikan') NOT NULL DEFAULT 'Menunggu'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loans MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Menunggu'");
        }
        DB::table('loans')->where('status', 'Dikonfirmasi')->update(['status' => 'Disetujui']);
        DB::table('loans')->where('status', 'Gagal')->update(['status' => 'Ditolak']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('Menunggu', 'Disetujui', 'Ditolak', 'Dikembalikan') NOT NULL DEFAULT 'Menunggu'");
        }
    }
};
