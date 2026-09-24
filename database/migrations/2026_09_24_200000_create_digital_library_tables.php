<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id('review_id');
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained('books', 'book_id')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('review_text');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['user_id', 'book_id']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id('notification_id');
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('loan_id')->nullable()->constrained('loans', 'loan_id')->nullOnDelete();
                $table->string('type', 50)->nullable();
                $table->text('message')->nullable();
                $table->timestamp('sent_at')->useCurrent();
                $table->string('status', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('ebooks');
    }
};
