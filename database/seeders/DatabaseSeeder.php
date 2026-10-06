<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(['nim_nip' => 'ADM001'], [
            'role' => 'Admin',
            'name' => 'Administrator',
            'class' => null,
            'email' => 'admin.demo@smartreading.test',
            'phone' => '081234567890',
            'password' => Hash::make('admin12345'),
        ]);

        User::firstOrCreate(['nim_nip' => '2024001'], [
            'role' => 'Peminjam',
            'borrower_type' => User::SISWA,
            'name' => 'Peminjam Demo',
            'class' => 'XII-A',
            'email' => 'peminjam.demo@smartreading.test',
            'phone' => '081298765432',
            'password' => Hash::make('peminjam123'),
        ]);

        User::firstOrCreate(['nim_nip' => '198501012010011001'], [
            'role' => 'Peminjam',
            'borrower_type' => User::TENAGA_PENDIDIK,
            'name' => 'Guru Demo',
            'job_title' => 'Guru Biologi',
            'email' => 'guru.demo@smartreading.test',
            'phone' => '081211112222',
            'password' => Hash::make('pendidik123'),
        ]);

        $fiksi = Category::firstOrCreate(['category_name' => 'Fiksi']);
        $teknologi = Category::firstOrCreate(['category_name' => 'Teknologi']);

        $andrea = Author::firstOrCreate(['author_name' => 'Andrea Hirata']);
        $martin = Author::firstOrCreate(['author_name' => 'Robert C. Martin']);

        $laskar = Book::firstOrCreate(['title' => 'Laskar Pelangi'], [
            'category_id' => $fiksi->category_id,
            'publisher' => 'Bentang Pustaka',
            'publication_year' => 2005,
            'description' => 'Kisah perjuangan anak-anak Belitung untuk memperoleh pendidikan.',
            'location' => 'Rak F-01',
            'stock' => 5,
            'available_stock' => 5,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);

        $cleanCode = Book::firstOrCreate(['title' => 'Clean Code'], [
            'category_id' => $teknologi->category_id,
            'publisher' => 'Prentice Hall',
            'publication_year' => 2008,
            'description' => 'Panduan menulis kode yang bersih, mudah dibaca, dan mudah dirawat.',
            'location' => 'Rak T-01',
            'stock' => 3,
            'available_stock' => 3,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);

        // penulis buku disimpan di tabel book_authors (satu buku bisa punya beberapa penulis)
        $laskar->authors()->syncWithoutDetaching([$andrea->author_id => ['urutan' => 1]]);
        $cleanCode->authors()->syncWithoutDetaching([$martin->author_id => ['urutan' => 1]]);
    }
}
