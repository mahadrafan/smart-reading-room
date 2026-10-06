<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// peminjam dibedakan menjadi Siswa (NIS + kelas) dan Tenaga Pendidik (NIP + jabatan/mapel).
// admin tidak punya jenis peminjam (NULL). peminjam lama dianggap Siswa karena dulu hanya ada isian kelas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('borrower_type', ['Siswa', 'Tenaga Pendidik'])->nullable()->after('role');
            $table->string('job_title', 50)->nullable()->after('class');
        });

        DB::table('users')->where('role', 'Peminjam')->update(['borrower_type' => 'Siswa']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['borrower_type', 'job_title']);
        });
    }
};
