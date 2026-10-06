<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// tabel perpustakaan, struktur sama dengan basdat_final.sql:
// categories, authors, books, book_authors (buku M:N penulis), loans, notifications, reviews, admin_logs
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->integer('category_id', true);
            $table->string('category_name', 100)->unique('uk_categories_name');
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->integer('author_id', true);
            $table->string('author_name', 100)->unique('uk_authors_name');
        });

        Schema::create('books', function (Blueprint $table) {
            $table->integer('book_id', true);
            // setiap buku wajib punya tepat satu kategori
            $table->integer('category_id');
            $table->string('title', 200);
            $table->string('publisher', 100)->nullable();
            $table->year('publication_year')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('location', 100)->nullable();
            $table->integer('stock')->nullable()->default(0);
            $table->integer('available_stock')->nullable()->default(0);
            // 0 = dihapus dari katalog (tab Nonaktif), tetap disimpan untuk riwayat
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->dateTime('updated_at')->nullable();

            // kategori yang masih dipakai buku tidak bisa dihapus
            $table->foreign('category_id', 'fk_books_category_id')->references('category_id')->on('categories')->restrictOnDelete();
            $table->foreign('created_by', 'fk_books_created_by')->references('user_id')->on('users');
            $table->foreign('updated_by', 'fk_books_updated_by')->references('user_id')->on('users');
        });

        Schema::create('book_authors', function (Blueprint $table) {
            $table->integer('book_id');
            $table->integer('author_id');
            // urutan penulis di buku (1 = penulis pertama), dipakai saat nama penulis ditampilkan
            $table->unsignedTinyInteger('urutan')->default(1);

            $table->primary(['book_id', 'author_id']);
            $table->foreign('book_id', 'book_authors_book_id_foreign')->references('book_id')->on('books')->cascadeOnDelete();
            // penulis yang masih dipakai buku tidak boleh terhapus
            $table->foreign('author_id', 'book_authors_author_id_foreign')->references('author_id')->on('authors')->restrictOnDelete();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->integer('loan_id', true);
            $table->integer('user_id');
            $table->integer('book_id');
            $table->dateTime('request_date')->useCurrent();
            // tanggal pinjam baru diisi saat buku diambil (status Dipinjam)
            $table->date('loan_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('return_date')->nullable();
            $table->enum('status', ['Menunggu', 'Dikonfirmasi', 'Dipinjam', 'Gagal', 'Dikembalikan'])->default('Menunggu');
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamp('fine_paid_at')->nullable();
            $table->integer('fine_paid_by')->nullable();

            $table->foreign('user_id', 'loans_ibfk_1')->references('user_id')->on('users');
            $table->foreign('book_id', 'loans_ibfk_2')->references('book_id')->on('books');
            $table->foreign('approved_by', 'fk_loans_approved_by')->references('user_id')->on('users');
            // admin yang tercatat mengonfirmasi pembayaran denda tidak boleh terhapus
            $table->foreign('fine_paid_by', 'fk_loans_fine_paid_by')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->integer('notification_id', true);
            $table->integer('user_id');
            $table->integer('loan_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->text('message')->nullable();
            $table->dateTime('sent_at')->nullable()->useCurrent();
            // "Belum Dibaca" / "Dibaca" (pop up notifikasi baru)
            $table->string('status', 20)->nullable();

            $table->foreign('user_id', 'notifications_ibfk_1')->references('user_id')->on('users');
            $table->foreign('loan_id', 'notifications_ibfk_2')->references('loan_id')->on('loans');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->integer('review_id', true);
            $table->integer('user_id');
            $table->integer('book_id');
            $table->integer('rating')->nullable();
            $table->text('review_text')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();

            // satu peminjam hanya boleh satu ulasan per buku
            $table->unique(['user_id', 'book_id'], 'uk_reviews_user_book');
            $table->foreign('user_id', 'reviews_ibfk_1')->references('user_id')->on('users');
            $table->foreign('book_id', 'reviews_ibfk_2')->references('book_id')->on('books');
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->integer('log_id', true);
            $table->integer('admin_id');
            $table->string('action', 50);
            $table->string('table_name', 50);
            $table->integer('record_id')->nullable();
            $table->string('description')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('admin_id', 'fk_logs_admin')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('book_authors');
        Schema::dropIfExists('books');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
    }
};
