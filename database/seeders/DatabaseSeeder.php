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
        $admin = User::updateOrCreate(['nim_nip' => 'ADMIN001'], [
            'role' => 'Admin',
            'name' => 'Administrator',
            'class' => null,
            'email' => 'admin@smartreading.test',
            'phone' => '081234567890',
            'password' => Hash::make('admin12345'),
        ]);

        User::updateOrCreate(['nim_nip' => '2026001'], [
            'role' => 'Peminjam',
            'name' => 'Peminjam Demo',
            'class' => 'XII-A',
            'email' => 'peminjam@smartreading.test',
            'phone' => '081298765432',
            'password' => Hash::make('peminjam123'),
        ]);

        $fiksi = Category::firstOrCreate(
            ['category_name' => 'Fiksi'],
            ['description' => 'Novel dan karya cerita fiksi.']
        );
        $teknologi = Category::firstOrCreate(
            ['category_name' => 'Teknologi'],
            ['description' => 'Buku komputer, teknologi, dan pemrograman.']
        );

        $andrea = Author::firstOrCreate(
            ['author_name' => 'Andrea Hirata'],
            ['biography' => 'Penulis Indonesia yang dikenal melalui novel Laskar Pelangi.']
        );
        $martin = Author::firstOrCreate(
            ['author_name' => 'Robert C. Martin'],
            ['biography' => 'Penulis dan praktisi rekayasa perangkat lunak.']
        );

        Book::firstOrCreate(['title' => 'Laskar Pelangi'], [
            'category_id' => $fiksi->category_id,
            'author_id' => $andrea->author_id,
            'publisher' => 'Bentang Pustaka',
            'publication_year' => 2005,
            'description' => 'Kisah perjuangan anak-anak Belitung untuk memperoleh pendidikan.',
            'location' => 'Rak F-01',
            'stock' => 5,
            'available_stock' => 5,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);

        Book::firstOrCreate(['title' => 'Clean Code'], [
            'category_id' => $teknologi->category_id,
            'author_id' => $martin->author_id,
            'publisher' => 'Prentice Hall',
            'publication_year' => 2008,
            'description' => 'Panduan menulis kode yang bersih, mudah dibaca, dan mudah dirawat.',
            'location' => 'Rak T-01',
            'stock' => 3,
            'available_stock' => 3,
            'is_active' => true,
            'created_by' => $admin->user_id,
        ]);
    }
}
