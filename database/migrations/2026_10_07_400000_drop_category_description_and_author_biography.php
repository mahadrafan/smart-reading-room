<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// categories.description dan authors.biography tidak pernah ditampilkan ke peminjam
// (hanya catatan di halaman admin), jadi kedua kolom dihapus.
// down() hanya membuat kolomnya kembali (kosong); isi lamanya tidak ikut kembali.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('biography');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('category_name');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->text('biography')->nullable()->after('author_name');
        });
    }
};
