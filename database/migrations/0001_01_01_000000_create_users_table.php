<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// tabel users: admin dan peminjam (siswa / tenaga pendidik) dalam satu tabel, dibedakan kolom role.
// struktur sama dengan basdat_final.sql (sesi login & cache memakai file, jadi tidak ada tabel sessions/cache).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('user_id', true);
            $table->enum('role', ['Peminjam', 'Admin'])->default('Peminjam');
            // Siswa (NIS + kelas) atau Tenaga Pendidik (NIP + jabatan); admin NULL
            $table->enum('borrower_type', ['Siswa', 'Tenaga Pendidik'])->nullable();
            $table->string('nim_nip', 20)->unique('uk_users_nim_nip');
            $table->string('name', 100);
            $table->string('class', 20)->nullable();
            $table->string('job_title', 50)->nullable();
            $table->string('email', 100)->unique('uk_users_email');
            $table->string('password');
            // disimpan dengan kode negara, mis. +6281234567890
            $table->string('phone', 20)->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
