<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'authors';
    protected $primaryKey = 'author_id';
    public $timestamps = false;

    protected $fillable = ['author_name', 'biography'];

    // satu penulis bisa menulis banyak buku, satu buku bisa punya banyak penulis (tabel book_authors)
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_authors', 'author_id', 'book_id');
    }
}
