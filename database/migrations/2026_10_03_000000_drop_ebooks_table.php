<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// fitur e-book sudah dihapus, jadi tabelnya tidak dipakai lagi
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ebooks');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ebooks')) {
            Schema::create('ebooks', function (Blueprint $table) {
                $table->id('ebook_id');
                $table->foreignId('category_id')->nullable()->constrained('categories', 'category_id')->nullOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('authors', 'author_id')->nullOnDelete();
                $table->string('title', 200);
                $table->string('publisher', 100)->nullable();
                $table->unsignedSmallInteger('publication_year')->nullable();
                $table->text('description')->nullable();
                $table->string('file_url')->nullable();
                $table->string('access_status', 20)->default('Aktif');
            });
        }
    }
};
