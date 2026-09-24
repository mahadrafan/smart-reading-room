<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name', 100)->unique();
            $table->string('description')->nullable();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id('author_id');
            $table->string('author_name', 100)->unique();
            $table->text('biography')->nullable();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id('book_id');
            $table->foreignId('category_id')->nullable()->constrained('categories', 'category_id')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('authors', 'author_id')->nullOnDelete();
            $table->string('title', 200);
            $table->string('publisher', 100)->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->text('description')->nullable();
            $table->string('location', 100)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('available_stock')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('cover_image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id('loan_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books', 'book_id')->restrictOnDelete();
            $table->timestamp('request_date')->useCurrent();
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status', 50)->default('Menunggu')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('admin_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('action', 50);
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('books');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
    }
};
