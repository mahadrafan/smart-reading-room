<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// menyamakan ERD fisik dengan ERD konseptual:
// - setiap buku wajib punya tepat satu kategori (books.category_id NOT NULL, kategori yang dipakai tidak bisa dihapus)
// - satu peminjam hanya boleh satu ulasan per buku (UNIQUE reviews.user_id + book_id)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign($this->foreignKey('books', 'category_id'));
        });

        Schema::table('books', function (Blueprint $table) {
            $this->kolomSeperti($table, 'category_id', 'categories', 'category_id')->nullable(false)->change();
        });

        Schema::table('books', function (Blueprint $table) {
            $table->foreign('category_id', 'fk_books_category_id')
                ->references('category_id')->on('categories')->restrictOnDelete();
        });

        // instalasi baru dari migration sudah punya unique ini; dump lama belum
        if (! $this->adaUniqueUlasan()) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['user_id', 'book_id'], 'uk_reviews_user_book');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('reviews', 'uk_reviews_user_book')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropUnique('uk_reviews_user_book');
            });
        }

        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign($this->foreignKey('books', 'category_id'));
        });

        Schema::table('books', function (Blueprint $table) {
            $this->kolomSeperti($table, 'category_id', 'categories', 'category_id')->nullable()->change();
        });

        Schema::table('books', function (Blueprint $table) {
            $table->foreign('category_id', 'fk_books_category_id')
                ->references('category_id')->on('categories');
        });
    }

    // SQLite hanya bisa menghapus foreign key lewat nama kolomnya; MySQL lewat namanya
    // (books_ibfk_1 dari dump, books_category_id_foreign dari migration, fk_books_category_id dari migration ini)
    private function foreignKey(string $tabel, string $kolom): string|array
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return [$kolom];
        }

        return collect(Schema::getForeignKeys($tabel))->first(fn ($f) => $f['columns'] === [$kolom])['name'];
    }

    private function adaUniqueUlasan(): bool
    {
        return collect(Schema::getIndexes('reviews'))
            ->contains(fn ($i) => $i['unique'] && ! $i['primary'] && $i['columns'] === ['user_id', 'book_id']);
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
