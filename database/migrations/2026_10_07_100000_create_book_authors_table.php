<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// satu buku bisa ditulis lebih dari satu penulis: relasi Buku–Penulis menjadi M:N lewat tabel book_authors.
// penulis lama di books.author_id dipindah ke book_authors (urutan 1), lalu kolom author_id dihapus.
return new class extends Migration
{
    // data penulis dibaca dulu sebelum tabel diubah: di SQLite, mengubah tabel books berarti membangun ulang
    // tabel itu, dan baris yang merujuk ke books bisa ikut terhapus (cascade)
    public function up(): void
    {
        $penulisLama = DB::table('books')->whereNotNull('author_id')->get(['book_id', 'author_id']);

        $fk = $this->namaForeignKey('books', 'author_id');
        Schema::table('books', function (Blueprint $table) use ($fk) {
            $table->dropForeign($fk ?? ['author_id']);
        });
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('author_id');
        });

        Schema::create('book_authors', function (Blueprint $table) {
            $this->kolomSeperti($table, 'book_id', 'books', 'book_id');
            $this->kolomSeperti($table, 'author_id', 'authors', 'author_id');
            // urutan penulis di buku (1 = penulis pertama), dipakai saat nama penulis ditampilkan
            $table->unsignedTinyInteger('urutan')->default(1);

            $table->primary(['book_id', 'author_id']);
            $table->foreign('book_id')->references('book_id')->on('books')->cascadeOnDelete();
            // penulis yang masih dipakai buku tidak boleh terhapus
            $table->foreign('author_id')->references('author_id')->on('authors')->restrictOnDelete();
        });

        foreach ($penulisLama as $baris) {
            DB::table('book_authors')->insert([
                'book_id' => $baris->book_id, 'author_id' => $baris->author_id, 'urutan' => 1,
            ]);
        }
    }

    public function down(): void
    {
        // penulis pertama tiap buku dikembalikan ke books.author_id
        $penulisPertama = DB::table('book_authors')->orderBy('urutan')->get()->unique('book_id');

        Schema::dropIfExists('book_authors');

        Schema::table('books', function (Blueprint $table) {
            $this->kolomSeperti($table, 'author_id', 'authors', 'author_id')->nullable()->after('category_id');
            $table->foreign('author_id')->references('author_id')->on('authors')->nullOnDelete();
        });

        foreach ($penulisPertama as $baris) {
            DB::table('books')->where('book_id', $baris->book_id)->update(['author_id' => $baris->author_id]);
        }
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

    // nama foreign key bisa berbeda (books_ibfk_2 dari dump, books_author_id_foreign dari migration)
    private function namaForeignKey(string $tabel, string $kolom): ?string
    {
        $fk = collect(Schema::getForeignKeys($tabel))->first(fn ($f) => $f['columns'] === [$kolom]);

        return $fk['name'] ?? null;
    }
};
